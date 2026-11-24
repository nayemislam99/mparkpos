<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use App\Models\User;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitTypeController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.view') || $user->can('units.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $query = UnitType::query()
            ->where('company_id', $company->id)
            ->withCount('products')
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', (bool) $status))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->orderBy('name');

        $unitTypes = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => UnitType::where('company_id', $company->id)->count(),
            'active' => UnitType::where('company_id', $company->id)->where('is_active', true)->count(),
            'inactive' => UnitType::where('company_id', $company->id)->where('is_active', false)->count(),
        ];

        return view('admin.units.index', compact(
            'company',
            'unitTypes',
            'stats',
            'status',
            'search'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.create') || $user->can('units.create'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('unit_types', 'name')->where('company_id', $company->id),
            ],
            'code' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $unitType = UnitType::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Unit type added successfully.',
                'data' => $unitType,
            ]);
        }

        return redirect()->route('unit-types.index')->with('success', 'Unit type added successfully.');
    }

    public function show(Request $request, UnitType $unitType): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $this->tenant->company($user);
        abort_if($unitType->company_id !== $company->id, 404);

        return response()->json($unitType);
    }

    public function update(Request $request, UnitType $unitType): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.update') || $user->can('units.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($unitType->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('unit_types', 'name')
                    ->where('company_id', $company->id)
                    ->ignore($unitType->id),
            ],
            'code' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $unitType->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Unit type updated successfully.',
                'data' => $unitType,
            ]);
        }

        return redirect()->route('unit-types.index')->with('success', 'Unit type updated successfully.');
    }

    public function toggleStatus(Request $request, UnitType $unitType): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.update') || $user->can('units.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($unitType->company_id !== $company->id, 404);

        $unitType->update(['is_active' => ! $unitType->is_active]);

        $msg = $unitType->is_active ? 'Unit type activated successfully.' : 'Unit type deactivated successfully.';

        return redirect()->route('unit-types.index')->with('success', $msg);
    }

    public function destroy(Request $request, UnitType $unitType): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.delete') || $user->can('units.delete'), 403);

        $company = $this->tenant->company($user);
        abort_if($unitType->company_id !== $company->id, 404);

        $unitType->delete();

        return redirect()->route('unit-types.index')->with('success', 'Unit type deleted successfully.');
    }
}
