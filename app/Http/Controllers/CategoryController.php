<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Csv\CsvService;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
        private CsvService $csvService,
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('categories.view') || $user->can('products.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $query = ProductCategory::query()
            ->where('company_id', $company->id)
            ->withCount('products')
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', (bool) $status))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name');

        $categories = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => ProductCategory::where('company_id', $company->id)->count(),
            'active' => ProductCategory::where('company_id', $company->id)->where('is_active', true)->count(),
            'inactive' => ProductCategory::where('company_id', $company->id)->where('is_active', false)->count(),
        ];

        return view('admin.categories.index', compact(
            'company',
            'categories',
            'stats',
            'status',
            'search'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('categories.create') || $user->can('products.create'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('product_categories', 'name')->where('company_id', $company->id),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = ProductCategory::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Category added successfully.',
                'data' => $category,
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Category added successfully.');
    }

    public function show(Request $request, ProductCategory $category): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $this->tenant->company($user);
        abort_if($category->company_id !== $company->id, 404);

        return response()->json($category);
    }

    public function update(Request $request, ProductCategory $category): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('categories.update') || $user->can('products.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($category->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('product_categories', 'name')
                    ->where('company_id', $company->id)
                    ->ignore($category->id),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => $category,
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function toggleStatus(Request $request, ProductCategory $category): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('categories.update') || $user->can('products.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($category->company_id !== $company->id, 404);

        $category->update(['is_active' => ! $category->is_active]);

        $msg = $category->is_active ? 'Category activated successfully.' : 'Category deactivated successfully.';

        return redirect()->route('categories.index')->with('success', $msg);
    }

    public function destroy(Request $request, ProductCategory $category): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('categories.delete') || $user->can('products.delete'), 403);

        $company = $this->tenant->company($user);
        abort_if($category->company_id !== $company->id, 404);

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('categories.view') || $user->can('products.view'), 403);

        $company = $this->tenant->company($user);
        $categories = ProductCategory::where('company_id', $company->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $headers = ['ID', 'Category Name', 'Status', 'Products Count', 'Created At'];
        $rows = [];
        foreach ($categories as $c) {
            $rows[] = [
                $c->id,
                $c->name,
                $c->is_active ? 'Active' : 'Inactive',
                $c->products_count,
                $c->created_at?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->csvService->export('categories_' . date('Y_m_d_His') . '.csv', $headers, $rows);
    }

    public function sampleCsv(): StreamedResponse
    {
        $headers = ['name', 'is_active'];
        $rows = [
            ['Helmets', '1'],
            ['Electronics', '1'],
            ['Groceries', '1'],
            ['Beverages', '0'],
        ];

        return $this->csvService->export('categories_sample_template.csv', $headers, $rows);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('categories.create') || $user->can('products.create'), 403);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $company = $this->tenant->company($user);
        $records = $this->csvService->parse($request->file('csv_file'));

        if (empty($records)) {
            return redirect()->back()->with('error', __('messages.csv_no_valid_rows'));
        }

        $created = 0;
        $skipped = 0;

        foreach ($records as $row) {
            $name = trim($row['name'] ?? $row['category_name'] ?? $row['category'] ?? '');
            if ($name === '') {
                $skipped++;
                continue;
            }

            $exists = ProductCategory::where('company_id', $company->id)->where('name', $name)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $isActive = !isset($row['is_active']) || in_array(strtolower(trim((string)$row['is_active'])), ['1', 'true', 'active', 'yes'], true);

            ProductCategory::create([
                'company_id' => $company->id,
                'name' => $name,
                'is_active' => $isActive,
            ]);

            $created++;
        }

        return redirect()->route('categories.index')->with('success', __('messages.csv_import_success', [
            'created' => $created,
            'skipped' => $skipped,
        ]));
    }
}
