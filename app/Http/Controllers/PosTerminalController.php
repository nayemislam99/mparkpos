<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\Pos\PosCheckoutService;
use App\Services\Pos\TenantContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PosTerminalController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
        private PosCheckoutService $checkout,
    ) {}

    public function page()
    {
        return view('pos.terminal');
    }

    public function context(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $this->tenant->company($user, $request->query('company_id'));
        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_head_office']);

        $branch = $this->tenant->branch($company, $user, $request->query('branch_id'));

        return response()->json([
            'company' => ['id' => $company->id, 'name' => $company->trade_name ?: $company->name],
            'companies' => $user->type === 'super_admin'
                ? Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'trade_name'])
                : [],
            'branches' => $branches,
            'branch_id' => $branch->id,
            'user' => ['id' => $user->id, 'name' => $user->name],
            'payment_methods' => [
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'card', 'label' => 'Card'],
                ['value' => 'mobile_banking', 'label' => 'Mobile banking'],
            ],
            'permissions' => [
                'discount' => $user->can('sales.discount'),
                'create_customer' => $user->can('customers.create'),
                'collect_due' => $user->can('sales.collect_payment'),
            ],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'branch_id' => ['required', 'integer'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);
        $company = $this->tenant->company($user, $data['company_id'] ?? null);
        $branch = $this->tenant->branch($company, $user, $data['branch_id']);
        $term = trim($data['q'] ?? '');

        $query = DB::table('products')
            ->leftJoin('product_categories', function ($join): void {
                $join->on('product_categories.id', '=', 'products.category_id')
                    ->on('product_categories.company_id', '=', 'products.company_id');
            })
            ->leftJoin('branch_stocks', function ($join) use ($branch): void {
                $join->on('branch_stocks.product_id', '=', 'products.id')
                    ->where('branch_stocks.company_id', '=', $branch->company_id)
                    ->where('branch_stocks.branch_id', '=', $branch->id);
            })
            ->where('products.company_id', $company->id)
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->select([
                'products.id', 'products.name', 'products.brand', 'products.sku', 'products.barcode',
                'products.unit', 'products.sale_price', 'products.track_stock',
                'product_categories.name as category',
                DB::raw('COALESCE(branch_stocks.quantity, 0) as stock'),
            ])
            ->orderBy('products.name')
            ->limit($term === '' ? 60 : 40);

        if ($term !== '') {
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('products.barcode', $term)
                    ->orWhere('products.sku', $term)
                    ->orWhere('products.name', 'like', '%'.$term.'%')
                    ->orWhere('products.brand', 'like', '%'.$term.'%')
                    ->orWhere('product_categories.name', 'like', '%'.$term.'%')
                    ->orWhere('products.barcode', 'like', '%'.$term.'%');
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function customers(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);
        $company = $this->tenant->company($user, $data['company_id'] ?? null);
        $term = trim($data['q'] ?? '');

        $customers = DB::table('customers')
            ->leftJoin('sales', function ($join): void {
                $join->on('sales.customer_id', '=', 'customers.id')
                    ->on('sales.company_id', '=', 'customers.company_id')
                    ->where('sales.payment_status', '!=', 'paid')
                    ->where('sales.status', '=', 'completed');
            })
            ->where('customers.company_id', $company->id)
            ->where('customers.is_active', true)
            ->whereNull('customers.deleted_at')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $builder) use ($term): void {
                    $builder->where('customers.name', 'like', '%'.$term.'%')
                        ->orWhere('customers.phone', 'like', '%'.$term.'%')
                        ->orWhere('customers.email', 'like', '%'.$term.'%');
                });
            })
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.email')
            ->orderBy('customers.name')
            ->limit(30)
            ->get([
                'customers.id', 'customers.name', 'customers.phone', 'customers.email',
                DB::raw('COALESCE(SUM(sales.due_total), 0) as outstanding_due'),
            ]);

        return response()->json(['data' => $customers]);
    }

    public function storeCustomer(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'company_id' => ['nullable', 'integer'],
        ]);
        $company = $this->tenant->company($user, $data['company_id'] ?? null);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('customers', 'phone')->where('company_id', $company->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $now = now();
        $customerId = DB::table('customers')->insertGetId([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'data' => DB::table('customers')->where('company_id', $company->id)->where('id', $customerId)->first(),
        ], 201);
    }

    public function dueInvoices(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'customer_id' => ['required', 'integer'],
        ]);
        $company = $this->tenant->company($user, $data['company_id'] ?? null);

        $invoices = DB::table('sales')
            ->where('company_id', $company->id)
            ->where('customer_id', $data['customer_id'])
            ->where('status', 'completed')
            ->where('due_total', '>', 0)
            ->orderBy('sold_at')
            ->limit(50)
            ->get([
                'id', 'invoice_number', 'sold_at', 'grand_total', 'paid_total', 'due_total',
            ]);

        return response()->json(['data' => $invoices]);
    }

    public function checkout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'branch_id' => ['required', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'items.*.discount_type' => ['nullable', Rule::in(['flat', 'percent'])],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'order_discount_type' => ['nullable', Rule::in(['flat', 'percent'])],
            'order_discount_value' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'payments' => ['sometimes', 'array', 'max:3'],
            'payments.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'payments.*.method' => ['required', Rule::in(['cash', 'card', 'mobile_banking'])],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
            'payment.amount' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'payment.method' => ['required_with:payment.amount', Rule::in(['cash', 'card', 'mobile_banking'])],
            'payment.reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        if (isset($data['payments'])) {
            $methods = array_column($data['payments'], 'method');
            if (count($methods) !== count(array_unique($methods))) {
                throw ValidationException::withMessages([
                    'payments' => 'Each payment method can be added only once.',
                ]);
            }
        }
        foreach ($data['items'] as $index => $item) {
            if (($item['discount_type'] ?? 'flat') === 'percent' && (float) ($item['discount_value'] ?? 0) > 100) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount_value" => 'Percentage discounts cannot exceed 100%.',
                ]);
            }
        }
        if (($data['order_discount_type'] ?? 'flat') === 'percent' && (float) ($data['order_discount_value'] ?? 0) > 100) {
            throw ValidationException::withMessages([
                'order_discount_value' => 'Percentage discounts cannot exceed 100%.',
            ]);
        }
        if ((float) ($data['order_discount_value'] ?? 0) > 0) {
            abort_unless($user->can('sales.discount'), 403);
        }
        foreach ($data['items'] as $item) {
            if ((float) ($item['discount_value'] ?? 0) > 0) {
                abort_unless($user->can('sales.discount'), 403);
            }
        }

        $company = $this->tenant->company($user, $data['company_id'] ?? null);
        $branch = $this->tenant->branch($company, $user, $data['branch_id']);

        return response()->json([
            'message' => 'Sale completed.',
            'data' => $this->checkout->checkout($user, $company, $branch, $data),
        ], 201);
    }

    public function collectInvoicePayment(Request $request, int $sale): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'method' => ['required', Rule::in(['cash', 'card', 'mobile_banking'])],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);
        $company = $this->tenant->company($user, $data['company_id'] ?? null);

        return response()->json([
            'message' => 'Invoice payment recorded.',
            'data' => $this->checkout->collectInvoicePayment($user, $company, $sale, $data),
        ]);
    }
}
