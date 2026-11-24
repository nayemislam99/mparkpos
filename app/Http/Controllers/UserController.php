<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Services\Pos\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->can('users.view'), 403);

        $company = $this->tenant->company($currentUser, $request->query('company_id'));

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => User::where('company_id', $company->id)->count(),
            'active' => User::where('company_id', $company->id)->where('is_active', true)->count(),
            'inactive' => User::where('company_id', $company->id)->where('is_active', false)->count(),
        ];

        $roles = config('access.roles', []);
        if ($currentUser->type !== 'super_admin') {
            unset($roles['super_admin']);
        }

        return view('users.index', compact('company', 'branches', 'stats', 'roles'));
    }

    public function create(Request $request): View
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->can('users.create'), 403);

        $company = $this->tenant->company($currentUser, $request->query('company_id'));

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get();

        $roles = config('access.roles', []);
        if ($currentUser->type !== 'super_admin') {
            unset($roles['super_admin']);
        }

        return view('users.create', compact('company', 'branches', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->can('users.create'), 403);

        $company = $this->tenant->company($currentUser, $request->input('company_id'));

        $allowedRoles = array_keys(config('access.roles', []));
        if ($currentUser->type !== 'super_admin') {
            $allowedRoles = array_diff($allowedRoles, ['super_admin']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('company_id', $company->id)->whereNull('deleted_at')),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in($allowedRoles)],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('company_id', $company->id)->whereNull('deleted_at')),
            ],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'is_active' => ['nullable'],
        ]);

        User::create([
            'company_id' => $company->id,
            'branch_id' => ! empty($validated['branch_id']) ? $validated['branch_id'] : null,
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'type' => $validated['type'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'force_password_change' => false,
            'created_by' => $currentUser->id,
        ]);

        return redirect()->route('users.index')->with('success', __('messages.user_created'));
    }

    public function edit(User $user, Request $request): View
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->can('users.update'), 403);

        abort_if($user->company_id !== $currentUser->company_id, 403);

        if ($user->type === 'super_admin' && $currentUser->type !== 'super_admin') {
            abort(403, 'Cannot edit super admin.');
        }

        $company = $this->tenant->company($currentUser);

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get();

        $roles = config('access.roles', []);
        if ($currentUser->type !== 'super_admin') {
            unset($roles['super_admin']);
        }

        return view('users.edit', compact('user', 'company', 'branches', 'roles'));
    }

    public function update(User $user, Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->can('users.update'), 403);

        abort_if($user->company_id !== $currentUser->company_id, 403);

        if ($user->type === 'super_admin' && $currentUser->type !== 'super_admin') {
            abort(403, 'Cannot edit super admin.');
        }

        $allowedRoles = array_keys(config('access.roles', []));
        if ($currentUser->type !== 'super_admin') {
            $allowedRoles = array_diff($allowedRoles, ['super_admin']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id)
                    ->where(fn ($q) => $q->where('company_id', $user->company_id)->whereNull('deleted_at')),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in($allowedRoles)],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('company_id', $user->company_id)->whereNull('deleted_at')),
            ],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'is_active' => ['nullable'],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'type' => $validated['type'],
            'branch_id' => ! empty($validated['branch_id']) ? $validated['branch_id'] : null,
            'updated_by' => $currentUser->id,
        ];

        // Do not allow self-deactivation
        if ((int) $user->id !== (int) $currentUser->id && $user->type !== 'super_admin') {
            $userData['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;
        }

        if (! empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        return redirect()->route('users.index')->with('success', __('messages.user_updated'));
    }

    public function toggleStatus(User $user, Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->can('users.update'), 403);

        abort_if($user->company_id !== $currentUser->company_id, 403);

        if ((int) $user->id === (int) $currentUser->id) {
            return back()->with('error', __('messages.cannot_deactivate_self'));
        }

        if ($user->type === 'super_admin') {
            return back()->with('error', __('messages.cannot_modify_super_admin'));
        }

        $newStatus = ! $user->is_active;
        $user->update([
            'is_active' => $newStatus,
            'deactivated_by' => $newStatus ? null : $currentUser->id,
            'deactivated_at' => $newStatus ? null : now(),
            'updated_by' => $currentUser->id,
        ]);

        return back()->with('success', __('messages.user_status_toggled'));
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser->can('users.delete'), 403);

        abort_if($user->company_id !== $currentUser->company_id, 403);

        if ((int) $user->id === (int) $currentUser->id) {
            return back()->with('error', __('messages.cannot_delete_self'));
        }

        if ($user->type === 'super_admin') {
            return back()->with('error', __('messages.cannot_modify_super_admin'));
        }

        $user->delete();

        return back()->with('success', __('messages.user_deleted'));
    }
}
