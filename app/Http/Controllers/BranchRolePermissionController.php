<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CompanyRolePermission;
use App\Models\User;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BranchRolePermissionController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('roles.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));

        // Default system roles
        $rolesConfig = collect(config('access.roles', []))
            ->reject(fn ($config, $key) => $key === 'super_admin');

        // Custom company roles
        $customRoleRecords = CompanyRolePermission::query()
            ->where('company_id', $company->id)
            ->whereNull('branch_id')
            ->get();

        $roleList = [];
        foreach ($rolesConfig as $key => $details) {
            $custom = $customRoleRecords->firstWhere('role', $key);
            $roleList[] = [
                'key' => $key,
                'name' => $details['label'],
                'is_custom' => (bool) $custom,
                'permissions' => $custom?->permissions ?? $details['permissions'],
            ];
        }

        // Add any purely custom roles created for this tenant
        foreach ($customRoleRecords as $custom) {
            if (! $rolesConfig->has($custom->role)) {
                $roleList[] = [
                    'key' => $custom->role,
                    'name' => Str::title(str_replace('_', ' ', $custom->role)),
                    'is_custom' => true,
                    'permissions' => $custom->permissions ?? [],
                ];
            }
        }

        $rolesCount = count($roleList);
        $permissionGroups = config('access.permission_groups', []);

        return view('admin.branch_roles.index', compact(
            'company',
            'roleList',
            'rolesCount',
            'permissionGroups'
        ));
    }

    public function storeRole(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('roles.update'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:50'],
        ]);

        $roleKey = Str::slug($validated['role_name'], '_');

        CompanyRolePermission::updateOrCreate(
            [
                'company_id' => $company->id,
                'branch_id' => null,
                'role' => $roleKey,
            ],
            [
                'permissions' => ['dashboard.view'],
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role created successfully.',
                'role' => $roleKey,
            ]);
        }

        return redirect()->route('branch-roles.index')->with('success', 'Role created successfully.');
    }

    public function getPermissions(Request $request, string $role): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('roles.view'), 403);

        $company = $this->tenant->company($user);

        $custom = CompanyRolePermission::query()
            ->where('company_id', $company->id)
            ->whereNull('branch_id')
            ->where('role', $role)
            ->first();

        $activePermissions = $custom?->permissions
            ?? config("access.roles.{$role}.permissions", ['dashboard.view']);

        return response()->json([
            'role' => $role,
            'permissions' => $activePermissions,
            'is_custom' => (bool) $custom,
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('roles.update'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:50'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $permissions = $validated['permissions'] ?? [];

        CompanyRolePermission::updateOrCreate(
            [
                'company_id' => $company->id,
                'branch_id' => null,
                'role' => $validated['role'],
            ],
            [
                'permissions' => array_values(array_unique($permissions)),
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.permissions_saved'),
            ]);
        }

        return redirect()->route('branch-roles.index')->with('success', __('messages.permissions_saved'));
    }

    public function reset(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('roles.update'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));
        $role = $request->input('role');

        CompanyRolePermission::query()
            ->where('company_id', $company->id)
            ->whereNull('branch_id')
            ->where('role', $role)
            ->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.permissions_reset'),
            ]);
        }

        return redirect()->route('branch-roles.index')->with('success', __('messages.permissions_reset'));
    }
}
