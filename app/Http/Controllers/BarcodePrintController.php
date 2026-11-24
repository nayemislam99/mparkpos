<?php

namespace App\Http\Controllers;

use App\Models\BarcodePrintLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarcodePrintController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function printPage(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $branches = Branch::where('company_id', $company->id)->where('is_active', true)->get();

        return view('admin.barcodes.print', compact('company', 'branches'));
    }

    public function logPrint(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.view'), 403);

        $company = $this->tenant->company($user);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variation_id' => ['nullable', 'integer'],
            'items.*.variant_name' => ['nullable', 'string', 'max:120'],
            'items.*.variation' => ['nullable', 'string', 'max:120'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.lot' => ['nullable', 'string', 'max:60'],
        ]);

        foreach ($validated['items'] as $item) {
            BarcodePrintLog::create([
                'company_id' => $company->id,
                'branch_id' => $validated['branch_id'] ?? null,
                'product_id' => $item['product_id'],
                'product_variation_id' => $item['product_variation_id'] ?? null,
                'printed_by' => $user->id,
                'lot' => $item['lot'] ?? null,
                'variant_name' => $item['variant_name'] ?? null,
                'variation' => $item['variation'] ?? null,
                'quantity' => (int) $item['quantity'],
                'price' => (float) $item['price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Barcode print logged successfully.',
        ]);
    }

    public function report(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $branches = Branch::where('company_id', $company->id)->get();
        $users = User::where('company_id', $company->id)->get();

        $fromDate = $request->query('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());
        $search = trim((string) $request->query('q', ''));
        $branchId = $request->query('branch_id');
        $printedById = $request->query('printed_by');
        $variantOnly = $request->boolean('variant_only');

        $query = BarcodePrintLog::query()
            ->where('barcode_print_logs.company_id', $company->id)
            ->with(['product', 'branch', 'printedBy'])
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($printedById, fn ($q) => $q->where('printed_by', $printedById))
            ->when($variantOnly, fn ($q) => $q->whereNotNull('product_variation_id'))
            ->when($search !== '', function ($q) use ($search): void {
                $q->whereHas('product', function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id');

        $logs = $query->paginate(25)->withQueryString();

        $stats = [
            'total_stickers' => (int) BarcodePrintLog::where('company_id', $company->id)->sum('quantity'),
            'total_products' => BarcodePrintLog::where('company_id', $company->id)->distinct('product_id')->count('product_id'),
            'print_jobs' => BarcodePrintLog::where('company_id', $company->id)->count(),
            'print_lines' => $logs->total(),
        ];

        return view('admin.barcodes.report', compact(
            'company',
            'branches',
            'users',
            'logs',
            'stats',
            'fromDate',
            'toDate',
            'search',
            'branchId',
            'printedById',
            'variantOnly'
        ));
    }
}
