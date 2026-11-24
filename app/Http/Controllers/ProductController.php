<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariation;
use App\Models\UnitType;
use App\Models\User;
use App\Models\Variation;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');
        $brandId = $request->query('brand_id');
        $status = $request->query('status');

        $query = Product::query()
            ->where('company_id', $company->id)
            ->with(['category', 'brandModel', 'unitType', 'branchStocks'])
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', (bool) $status))
            ->orderBy('name');

        $products = $query->paginate(25)->withQueryString();

        $categories = ProductCategory::where('company_id', $company->id)->where('is_active', true)->get();
        $brands = Brand::where('company_id', $company->id)->where('is_active', true)->get();

        $stats = [
            'total' => Product::where('company_id', $company->id)->count(),
            'active' => Product::where('company_id', $company->id)->where('is_active', true)->count(),
            'low_stock' => Product::where('company_id', $company->id)
                ->where('track_stock', true)
                ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM branch_stocks WHERE branch_stocks.product_id = products.id) <= products.alert_quantity')
                ->count(),
        ];

        return view('admin.products.index', compact(
            'company',
            'products',
            'categories',
            'brands',
            'stats',
            'search',
            'categoryId',
            'brandId',
            'status'
        ));
    }

    public function create(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.create'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $brands = Brand::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $categories = ProductCategory::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $unitTypes = UnitType::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $variations = Variation::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('company_id', $company->id)->where('is_active', true)->get();

        return view('admin.products.create', compact(
            'company',
            'brands',
            'categories',
            'unitTypes',
            'variations',
            'branches'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.create'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->where('company_id', $company->id)],
            'category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')->where('company_id', $company->id)],
            'unit_id' => ['nullable', 'integer', Rule::exists('unit_types', 'id')->where('company_id', $company->id)],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'sku' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('products', 'sku')->where('company_id', $company->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->where('company_id', $company->id),
            ],
            'alert_quantity' => ['nullable', 'numeric', 'min:0'],
            'has_warranty' => ['nullable', 'boolean'],
            'discount_status' => ['nullable', 'string', Rule::in(['no', 'flat', 'percent'])],
            'vat_status' => ['nullable', 'boolean'],
            'it_status' => ['nullable', 'boolean'],
            'product_type' => ['nullable', 'string', Rule::in(['simple', 'variable', 'multiple_variation'])],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'alternative_labels' => ['nullable', 'array'],
            'alternative_prices' => ['nullable', 'array'],
            'variations' => ['nullable', 'array'],
            'initial_stock' => ['nullable', 'numeric', 'min:0'],
            'initial_branch_id' => ['nullable', 'integer'],
        ]);

        // Auto-generate SKU / Barcode if blank
        $sku = ! empty($validated['sku'])
            ? $validated['sku']
            : 'SKU-'.strtoupper(Str::random(6));

        $barcode = ! empty($validated['barcode'])
            ? $validated['barcode']
            : (string) rand(100000000000, 999999999999);

        // Handle Image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        // Process alternative prices
        $altPrices = [];
        if (! empty($validated['alternative_labels'])) {
            foreach ($validated['alternative_labels'] as $idx => $label) {
                $price = $validated['alternative_prices'][$idx] ?? null;
                if ($label && $price !== null && $price !== '') {
                    $altPrices[] = [
                        'label' => trim($label),
                        'price' => (float) $price,
                    ];
                }
            }
        }

        // Lookup brand/unit name strings for fallback
        $brandModel = ! empty($validated['brand_id']) ? Brand::find($validated['brand_id']) : null;
        $unitModel = ! empty($validated['unit_id']) ? UnitType::find($validated['unit_id']) : null;

        $product = Product::create([
            'company_id' => $company->id,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'unit_id' => $validated['unit_id'] ?? null,
            'name' => $validated['name'],
            'brand' => $brandModel?->name,
            'sku' => $sku,
            'barcode' => $barcode,
            'unit' => $unitModel?->name ?? 'pcs',
            'cost_price' => (float) $validated['cost_price'],
            'sale_price' => (float) $validated['sale_price'],
            'track_stock' => true,
            'is_active' => true,
            'alert_quantity' => (float) ($validated['alert_quantity'] ?? 5),
            'has_warranty' => (bool) ($validated['has_warranty'] ?? false),
            'discount_status' => $validated['discount_status'] ?? 'no',
            'vat_status' => (bool) ($validated['vat_status'] ?? false),
            'it_status' => (bool) ($validated['it_status'] ?? false),
            'product_type' => $validated['product_type'] ?? 'simple',
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
            'alternative_prices' => $altPrices,
        ]);

        // Process product variations if any
        if (! empty($validated['variations']) && is_array($validated['variations'])) {
            foreach ($validated['variations'] as $v) {
                if (! empty($v['name'])) {
                    ProductVariation::create([
                        'company_id' => $company->id,
                        'product_id' => $product->id,
                        'name' => $v['name'],
                        'cost_price' => (float) ($v['cost_price'] ?? $product->cost_price),
                        'sale_price' => (float) ($v['sale_price'] ?? $product->sale_price),
                        'barcode' => $v['barcode'] ?? ($barcode.'-'.rand(10, 99)),
                        'sku' => $v['sku'] ?? ($sku.'-'.rand(10, 99)),
                    ]);
                }
            }
        }

        // Add initial branch stock if provided
        if (! empty($validated['initial_stock']) && (float) $validated['initial_stock'] > 0) {
            $branchId = ! empty($validated['initial_branch_id'])
                ? $validated['initial_branch_id']
                : Branch::where('company_id', $company->id)->where('is_head_office', true)->value('id');

            if ($branchId) {
                BranchStock::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'branch_id' => $branchId,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => (float) $validated['initial_stock'],
                    ]
                );
            }
        }

        if ($request->input('action') === 'save_and_add') {
            return redirect()->route('products.create')->with('success', 'Product added. You can add another now.');
        }

        return redirect()->route('products.index')->with('success', 'Product added successfully.');
    }

    public function toggleStatus(Request $request, Product $product): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($product->company_id !== $company->id, 404);

        $product->update(['is_active' => ! $product->is_active]);

        $msg = $product->is_active ? 'Product activated successfully.' : 'Product deactivated successfully.';

        return redirect()->route('products.index')->with('success', $msg);
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.delete'), 403);

        $company = $this->tenant->company($user);
        abort_if($product->company_id !== $company->id, 404);

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    public function generateSku(): JsonResponse
    {
        return response()->json([
            'sku' => 'SKU-'.strtoupper(Str::random(8)),
        ]);
    }

    public function generateBarcode(): JsonResponse
    {
        return response()->json([
            'barcode' => (string) rand(100000000000, 999999999999),
        ]);
    }
}
