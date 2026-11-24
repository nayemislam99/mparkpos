<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarcodeSearchController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function search(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $this->tenant->company($user, $request->query('company_id'));

        $queryTerm = trim((string) $request->query('q', ''));
        $branchId = $request->query('branch_id');
        $isExact = $request->boolean('exact');
        $limit = max(1, min((int) $request->query('limit', 25), 60));

        if ($queryTerm === '' && ! $request->has('all')) {
            return response()->json(['data' => []]);
        }

        // Build query for Products
        $query = Product::query()
            ->where('products.company_id', $company->id)
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->leftJoin('product_categories', function ($join): void {
                $join->on('product_categories.id', '=', 'products.category_id')
                    ->on('product_categories.company_id', '=', 'products.company_id');
            })
            ->leftJoin('brands', function ($join): void {
                $join->on('brands.id', '=', 'products.brand_id')
                    ->on('brands.company_id', '=', 'products.company_id');
            })
            ->leftJoin('unit_types', function ($join): void {
                $join->on('unit_types.id', '=', 'products.unit_id')
                    ->on('unit_types.company_id', '=', 'products.company_id');
            })
            ->leftJoin('branch_stocks', function ($join) use ($company, $branchId): void {
                $join->on('branch_stocks.product_id', '=', 'products.id')
                    ->where('branch_stocks.company_id', '=', $company->id);

                if (! empty($branchId)) {
                    $join->where('branch_stocks.branch_id', '=', (int) $branchId);
                }
            })
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.barcode',
                'products.sale_price',
                'products.cost_price',
                'products.track_stock',
                'products.alert_quantity',
                'products.image',
                'products.product_type',
                DB::raw('COALESCE(brands.name, products.brand) as brand_name'),
                'product_categories.name as category_name',
                DB::raw('COALESCE(unit_types.name, products.unit) as unit_name'),
                DB::raw('COALESCE(SUM(branch_stocks.quantity), 0) as current_stock'),
            ])
            ->groupBy([
                'products.id',
                'products.name',
                'products.sku',
                'products.barcode',
                'products.sale_price',
                'products.cost_price',
                'products.track_stock',
                'products.alert_quantity',
                'products.image',
                'products.product_type',
                'brands.name',
                'products.brand',
                'product_categories.name',
                'unit_types.name',
                'products.unit',
            ]);

        if ($queryTerm !== '') {
            if ($isExact) {
                // Exact scan match by Barcode or SKU
                $query->where(function ($sub) use ($queryTerm): void {
                    $sub->where('products.barcode', $queryTerm)
                        ->orWhere('products.sku', $queryTerm);
                });
            } else {
                // Fuzzy/partial match
                $query->where(function ($sub) use ($queryTerm): void {
                    $sub->where('products.barcode', $queryTerm)
                        ->orWhere('products.sku', $queryTerm)
                        ->orWhere('products.barcode', 'like', "%{$queryTerm}%")
                        ->orWhere('products.sku', 'like', "%{$queryTerm}%")
                        ->orWhere('products.name', 'like', "%{$queryTerm}%")
                        ->orWhere('brands.name', 'like', "%{$queryTerm}%")
                        ->orWhere('products.brand', 'like', "%{$queryTerm}%")
                        ->orWhere('product_categories.name', 'like', "%{$queryTerm}%");
                });
            }
        }

        $products = $query->limit($limit)->get();

        // Also check ProductVariation for exact or fuzzy barcode/sku matches
        $variationMatches = collect();
        if ($queryTerm !== '') {
            $vQuery = DB::table('product_variations')
                ->join('products', 'products.id', '=', 'product_variations.product_id')
                ->where('product_variations.company_id', $company->id)
                ->where('products.company_id', $company->id)
                ->where('products.is_active', true)
                ->whereNull('products.deleted_at')
                ->select([
                    'product_variations.id as variation_id',
                    'product_variations.product_id',
                    'product_variations.name as variation_name',
                    'product_variations.barcode as variation_barcode',
                    'product_variations.sku as variation_sku',
                    'product_variations.cost_price as variation_cost_price',
                    'product_variations.sale_price as variation_sale_price',
                    'products.name as product_name',
                    'products.unit',
                ]);

            if ($isExact) {
                $vQuery->where(function ($sub) use ($queryTerm): void {
                    $sub->where('product_variations.barcode', $queryTerm)
                        ->orWhere('product_variations.sku', $queryTerm);
                });
            } else {
                $vQuery->where(function ($sub) use ($queryTerm): void {
                    $sub->where('product_variations.barcode', 'like', "%{$queryTerm}%")
                        ->orWhere('product_variations.sku', 'like', "%{$queryTerm}%")
                        ->orWhere('product_variations.name', 'like', "%{$queryTerm}%");
                });
            }

            $variationMatches = $vQuery->limit(15)->get();
        }

        $formatted = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'variation_id' => null,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'brand' => $p->brand_name ?: '—',
                'category' => $p->category_name ?: '—',
                'unit' => $p->unit_name ?: 'pcs',
                'sale_price' => (float) $p->sale_price,
                'cost_price' => (float) $p->cost_price,
                'stock' => (float) $p->current_stock,
                'track_stock' => (bool) $p->track_stock,
                'alert_quantity' => (float) $p->alert_quantity,
                'image' => $p->image ? asset($p->image) : null,
                'product_type' => $p->product_type,
                'is_variation' => false,
            ];
        });

        // Add variation items if any
        foreach ($variationMatches as $v) {
            $formatted->push([
                'id' => $v->product_id,
                'variation_id' => $v->variation_id,
                'name' => "{$v->product_name} ({$v->variation_name})",
                'sku' => $v->variation_sku ?: '—',
                'barcode' => $v->variation_barcode ?: '—',
                'brand' => '—',
                'category' => '—',
                'unit' => $v->unit ?: 'pcs',
                'sale_price' => (float) $v->variation_sale_price,
                'cost_price' => (float) $v->variation_cost_price,
                'stock' => 0.0,
                'track_stock' => true,
                'alert_quantity' => 0.0,
                'image' => null,
                'product_type' => 'variable',
                'is_variation' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'count' => $formatted->count(),
            'data' => $formatted->values(),
        ]);
    }
}
