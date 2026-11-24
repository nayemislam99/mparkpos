<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('branches.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => $branches->count(),
            'active' => $branches->where('is_active', true)->count(),
            'head_office' => $branches->where('is_head_office', true)->first(),
        ];

        return view('admin.branches.index', compact('company', 'branches', 'stats'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('branches.create'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->where('company_id', $company->id),
            ],
            'branch_bin' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_head_office' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isHeadOffice = (bool) ($validated['is_head_office'] ?? false);

        if ($isHeadOffice) {
            Branch::query()
                ->where('company_id', $company->id)
                ->where('is_head_office', true)
                ->update(['is_head_office' => false]);
        }

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'branch_bin' => $validated['branch_bin'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_head_office' => $isHeadOffice,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by' => $user->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.branch_created'),
                'data' => $branch,
            ]);
        }

        return redirect()->route('branches.index')->with('success', __('messages.branch_created'));
    }

    public function show(Request $request, Branch $branch): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('branches.view'), 403);

        $company = $this->tenant->company($user);
        abort_if($branch->company_id !== $company->id, 404);

        return response()->json($branch);
    }

    public function update(Request $request, Branch $branch): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('branches.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($branch->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')
                    ->where('company_id', $company->id)
                    ->ignore($branch->id),
            ],
            'branch_bin' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_head_office' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isHeadOffice = (bool) ($validated['is_head_office'] ?? false);

        if ($isHeadOffice && ! $branch->is_head_office) {
            Branch::query()
                ->where('company_id', $company->id)
                ->where('is_head_office', true)
                ->update(['is_head_office' => false]);
        }

        $branch->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'branch_bin' => $validated['branch_bin'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_head_office' => $isHeadOffice,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'updated_by' => $user->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.branch_updated'),
                'data' => $branch,
            ]);
        }

        return redirect()->route('branches.index')->with('success', __('messages.branch_updated'));
    }

    public function destroy(Request $request, Branch $branch): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('branches.delete'), 403);

        $company = $this->tenant->company($user);
        abort_if($branch->company_id !== $company->id, 404);

        if ($branch->is_head_office) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.cannot_delete_head_office'),
                ], 422);
            }

            return redirect()->route('branches.index')->with('error', __('messages.cannot_delete_head_office'));
        }

        $branch->update([
            'deactivated_by' => $user->id,
            'deactivated_at' => now(),
            'is_active' => false,
        ]);
        $branch->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.branch_deleted'),
            ]);
        }

        return redirect()->route('branches.index')->with('success', __('messages.branch_deleted'));
    }
}
