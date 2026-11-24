<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CrmController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('users.view') || $user->can('customers.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get();

        $selectedBranchId = $request->query('branch_id');
        $showDeactivatedOnly = $request->boolean('deactivated');
        $search = trim((string) $request->query('q', ''));

        $query = User::query()
            ->where('company_id', $company->id)
            ->where('type', '!=', 'super_admin')
            ->with('branch')
            ->when($selectedBranchId, fn ($q) => $q->where('branch_id', $selectedBranchId))
            ->when($showDeactivatedOnly, fn ($q) => $q->where('is_active', false))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id');

        $crmUsers = $query->paginate(25)->withQueryString();
        $crmCount = User::where('company_id', $company->id)->where('type', '!=', 'super_admin')->count();
        $deactivatedCount = User::where('company_id', $company->id)->where('type', '!=', 'super_admin')->where('is_active', false)->count();

        $rolesConfig = config('access.roles', []);
        $customRoles = \App\Models\CompanyRolePermission::where('company_id', $company->id)
            ->pluck('role')
            ->toArray();
        $allRoles = [];
        foreach ($rolesConfig as $k => $cfg) {
            if ($k !== 'super_admin') {
                $allRoles[$k] = $cfg['label'];
            }
        }
        foreach ($customRoles as $cr) {
            if (! isset($allRoles[$cr])) {
                $allRoles[$cr] = \Illuminate\Support\Str::title(str_replace('_', ' ', $cr));
            }
        }

        return view('admin.crm.index', compact(
            'company',
            'branches',
            'selectedBranchId',
            'showDeactivatedOnly',
            'search',
            'crmUsers',
            'crmCount',
            'deactivatedCount',
            'allRoles'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('users.create') || $user->can('customers.create'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->where('company_id', $company->id),
            ],
            'password' => ['required', 'string', 'min:6'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'role' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $newUser = User::create([
            'company_id' => $company->id,
            'branch_id' => ! empty($validated['branch_id']) ? $validated['branch_id'] : null,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'],
            'address' => $validated['address'] ?? null,
            'password' => Hash::make($validated['password']),
            'type' => $validated['role'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'force_password_change' => false,
            'created_by' => $user->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'CRM user added successfully.',
                'data' => $newUser,
            ]);
        }

        return redirect()->route('crm.index')->with('success', 'CRM user added successfully.');
    }

    public function show(Request $request, User $crmUser): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $this->tenant->company($user);
        abort_if($crmUser->company_id !== $company->id, 404);

        return response()->json([
            'user' => $crmUser->load('branch'),
            'role_label' => config("access.roles.{$crmUser->type}.label", $crmUser->type),
        ]);
    }

    public function update(Request $request, User $crmUser): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('users.update') || $user->can('customers.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($crmUser->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')
                    ->where('company_id', $company->id)
                    ->ignore($crmUser->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'role' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'],
            'address' => $validated['address'] ?? null,
            'branch_id' => ! empty($validated['branch_id']) ? $validated['branch_id'] : null,
            'type' => $validated['role'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'updated_by' => $user->id,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $crmUser->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'CRM user updated successfully.',
                'data' => $crmUser,
            ]);
        }

        return redirect()->route('crm.index')->with('success', 'CRM user updated successfully.');
    }

    public function toggleStatus(Request $request, User $crmUser): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('users.update') || $user->can('customers.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($crmUser->company_id !== $company->id, 404);

        $newStatus = ! $crmUser->is_active;
        $crmUser->update([
            'is_active' => $newStatus,
            'deactivated_at' => $newStatus ? null : now(),
            'deactivated_by' => $newStatus ? null : $user->id,
            'updated_by' => $user->id,
        ]);

        $msg = $newStatus ? 'User activated successfully.' : 'User deactivated successfully.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('crm.index')->with('success', $msg);
    }

    public function loginAs(Request $request, User $crmUser): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $this->tenant->company($user);
        abort_if($crmUser->company_id !== $company->id, 404);
        abort_unless($user->isSuperAdmin() || $user->isStoreAdmin(), 403);

        Auth::login($crmUser);

        return redirect()->route('dashboard.index')->with('success', "Logged in as {$crmUser->name}");
    }
}
