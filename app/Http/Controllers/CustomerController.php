<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    private function customerTypes(): array
    {
        return [
            'regular' => __('messages.type_regular'),
            'retail' => __('messages.type_retail'),
            'wholesale' => __('messages.type_wholesale'),
            'dealer' => __('messages.type_dealer'),
            'corporate' => __('messages.type_corporate'),
            'walk_in' => __('messages.type_walk_in'),
            'vip' => __('messages.type_vip'),
        ];
    }

    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->can('customers.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Customer::where('company_id', $company->id)->count(),
            'active' => Customer::where('company_id', $company->id)->where('is_active', true)->count(),
            'total_opening_due' => (float) Customer::where('company_id', $company->id)->sum('opening_balance'),
            'total_advance' => (float) Customer::where('company_id', $company->id)->sum('advance_balance'),
        ];

        // Calculate total company sales due
        $salesDue = (float) DB::table('sales')
            ->where('company_id', $company->id)
            ->where('payment_status', '!=', 'paid')
            ->where('status', 'completed')
            ->sum('due_total');

        $stats['total_outstanding_due'] = $stats['total_opening_due'] + $salesDue;
        $customerTypes = $this->customerTypes();

        return view('admin.customers.index', compact('company', 'branches', 'stats', 'customerTypes'));
    }

    public function create(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->can('customers.create'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get();

        $customerGroups = [
            'retail' => 'Retail Customer',
            'wholesale' => 'Wholesale Customer',
            'dealer' => 'Dealer',
            'corporate' => 'Corporate',
        ];
        $customerTypes = $this->customerTypes();

        return view('admin.customers.create', compact('company', 'branches', 'customerGroups', 'customerTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->can('customers.create'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => [
                'required',
                'string',
                'max:40',
                Rule::unique('customers', 'phone')->where(fn ($q) => $q->where('company_id', $company->id)->whereNull('deleted_at')),
            ],
            'email' => ['nullable', 'email', 'max:190'],
            'nid' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'customer_group' => ['nullable', 'string', 'max:50'],
            'customer_type' => ['nullable', 'string', 'max:50'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'due_limit' => ['nullable', 'numeric', 'min:0'],
            'advance_balance' => ['nullable', 'numeric', 'min:0'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('company_id', $company->id)->whereNull('deleted_at')),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable'],
        ]);

        Customer::create([
            'company_id' => $company->id,
            'branch_id' => ! empty($validated['branch_id']) ? $validated['branch_id'] : null,
            'name' => $validated['name'],
            'phone' => trim($validated['phone']),
            'email' => ! empty($validated['email']) ? strtolower(trim($validated['email'])) : null,
            'nid' => $validated['nid'] ?? null,
            'address' => $validated['address'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city' => $validated['city'] ?? null,
            'customer_group' => $validated['customer_group'] ?? 'retail',
            'customer_type' => $validated['customer_type'] ?? 'regular',
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'due_limit' => $validated['due_limit'] ?? 0,
            'advance_balance' => $validated['advance_balance'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'created_by' => $user->id,
        ]);

        return redirect()->route('customers.index')->with('success', __('messages.customer_created'));
    }

    public function show(Customer $customer, Request $request): JsonResponse|View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->can('customers.view'), 403);
        abort_if($customer->company_id !== $user->company_id, 403);

        $customer->load(['branch', 'payments.receiver']);
        $outstandingDue = $customer->outstanding_due;

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'nid' => $customer->nid,
                'address' => $customer->address,
                'address_line_2' => $customer->address_line_2,
                'city' => $customer->city,
                'customer_group' => $customer->customer_group,
                'customer_type' => $customer->customer_type,
                'opening_balance' => (float) $customer->opening_balance,
                'advance_balance' => (float) $customer->advance_balance,
                'due_limit' => (float) $customer->due_limit,
                'outstanding_due' => $outstandingDue,
                'is_active' => $customer->is_active,
            ]);
        }

        return view('admin.customers.show', compact('customer', 'outstandingDue'));
    }

    public function edit(Customer $customer, Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->can('customers.update'), 403);
        abort_if($customer->company_id !== $user->company_id, 403);

        $company = $this->tenant->company($user);

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get();

        $customerGroups = [
            'retail' => 'Retail Customer',
            'wholesale' => 'Wholesale Customer',
            'dealer' => 'Dealer',
            'corporate' => 'Corporate',
        ];
        $customerTypes = $this->customerTypes();

        return view('admin.customers.edit', compact('customer', 'company', 'branches', 'customerGroups', 'customerTypes'));
    }

    public function update(Customer $customer, Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->can('customers.update'), 403);
        abort_if($customer->company_id !== $user->company_id, 403);

        $company = $this->tenant->company($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => [
                'required',
                'string',
                'max:40',
                Rule::unique('customers', 'phone')
                    ->ignore($customer->id)
                    ->where(fn ($q) => $q->where('company_id', $company->id)->whereNull('deleted_at')),
            ],
            'email' => ['nullable', 'email', 'max:190'],
            'nid' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'customer_group' => ['nullable', 'string', 'max:50'],
            'customer_type' => ['nullable', 'string', 'max:50'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'due_limit' => ['nullable', 'numeric', 'min:0'],
            'advance_balance' => ['nullable', 'numeric', 'min:0'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('company_id', $company->id)->whereNull('deleted_at')),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable'],
        ]);

        $customer->update([
            'branch_id' => ! empty($validated['branch_id']) ? $validated['branch_id'] : null,
            'name' => $validated['name'],
            'phone' => trim($validated['phone']),
            'email' => ! empty($validated['email']) ? strtolower(trim($validated['email'])) : null,
            'nid' => $validated['nid'] ?? null,
            'address' => $validated['address'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city' => $validated['city'] ?? null,
            'customer_group' => $validated['customer_group'] ?? 'retail',
            'customer_type' => $validated['customer_type'] ?? 'regular',
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'due_limit' => $validated['due_limit'] ?? 0,
            'advance_balance' => $validated['advance_balance'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('customers.index')->with('success', __('messages.customer_updated'));
    }

    public function toggleStatus(Customer $customer, Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->can('customers.update'), 403);
        abort_if($customer->company_id !== $user->company_id, 403);

        $customer->update([
            'is_active' => ! $customer->is_active,
            'updated_by' => $user->id,
        ]);

        return back()->with('success', __('messages.customer_status_toggled'));
    }

    public function destroy(Customer $customer, Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->can('customers.delete'), 403);
        abort_if($customer->company_id !== $user->company_id, 403);

        $customer->delete();

        return back()->with('success', __('messages.customer_deleted'));
    }

    public function collectDue(Customer $customer, Request $request): RedirectResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->can('sales.collect_payment') || $user->can('customers.update'), 403);
        abort_if($customer->company_id !== $user->company_id, 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'in:cash,bank,bkash,nagad,rocket,card,other'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('company_id', $user->company_id)->whereNull('deleted_at')),
            ],
        ]);

        $collectedAmount = (float) $validated['amount'];
        $remaining = $collectedAmount;
        $branchId = ! empty($validated['branch_id']) ? $validated['branch_id'] : ($customer->branch_id ?? $user->branch_id);

        DB::transaction(function () use ($customer, $user, $collectedAmount, &$remaining, $validated, $branchId): void {
            // 1. Record in customer_payments
            CustomerPayment::create([
                'company_id' => $customer->company_id,
                'branch_id' => $branchId,
                'customer_id' => $customer->id,
                'received_by' => $user->id,
                'amount' => $collectedAmount,
                'method' => $validated['method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'note' => $validated['note'] ?? null,
                'paid_at' => now(),
            ]);

            // 2. First deduct from customer opening_balance if positive
            if ($customer->opening_balance > 0) {
                $deductFromOpening = min((float) $customer->opening_balance, $remaining);
                $customer->opening_balance = (float) $customer->opening_balance - $deductFromOpening;
                $remaining -= $deductFromOpening;
            }

            // 3. Settle unpaid sales invoices if remaining > 0
            if ($remaining > 0) {
                $unpaidSales = Sale::where('company_id', $customer->company_id)
                    ->where('customer_id', $customer->id)
                    ->where('payment_status', '!=', 'paid')
                    ->where('status', 'completed')
                    ->orderBy('sold_at', 'asc')
                    ->get();

                foreach ($unpaidSales as $sale) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $due = (float) $sale->due_total;
                    $pay = min($due, $remaining);

                    $sale->paid_total = (float) $sale->paid_total + $pay;
                    $sale->due_total = (float) $sale->due_total - $pay;
                    $sale->payment_status = $sale->due_total <= 0 ? 'paid' : 'partial';
                    $sale->save();

                    DB::table('sale_payments')->insert([
                        'company_id' => $customer->company_id,
                        'sale_id' => $sale->id,
                        'received_by' => $user->id,
                        'method' => $validated['method'],
                        'amount' => $pay,
                        'reference' => $validated['reference_no'] ?? null,
                        'note' => 'Customer due collection: ' . ($validated['note'] ?? ''),
                        'paid_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $remaining -= $pay;
                }
            }

            // 4. Any leftover amount goes to customer advance_balance
            if ($remaining > 0) {
                $customer->advance_balance = (float) $customer->advance_balance + $remaining;
            }

            $customer->save();
        });

        $message = __('messages.due_collected_success', ['amount' => number_format($collectedAmount, 2), 'name' => $customer->name]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'new_outstanding_due' => $customer->fresh()->outstanding_due,
            ]);
        }

        return back()->with('success', $message);
    }
}
