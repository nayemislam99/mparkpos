<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use App\Models\User;
use App\Services\Csv\CsvService;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnitTypeController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
        private CsvService $csvService,
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

    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.view') || $user->can('units.view'), 403);

        $company = $this->tenant->company($user);
        $units = UnitType::where('company_id', $company->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $headers = ['ID', 'Unit Name', 'Unit Code', 'Status', 'Products Count', 'Created At'];
        $rows = [];
        foreach ($units as $u) {
            $rows[] = [
                $u->id,
                $u->name,
                $u->code ?? '',
                $u->is_active ? 'Active' : 'Inactive',
                $u->products_count,
                $u->created_at?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->csvService->export('unit_types_' . date('Y_m_d_His') . '.csv', $headers, $rows);
    }

    public function sampleCsv(): StreamedResponse
    {
        $headers = ['name', 'code', 'is_active'];
        $rows = [
            ['Piece', 'pcs', '1'],
            ['Kilogram', 'kg', '1'],
            ['Box', 'box', '1'],
            ['Liter', 'ltr', '1'],
        ];

        return $this->csvService->export('unit_types_sample_template.csv', $headers, $rows);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.create') || $user->can('units.create'), 403);

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
            $name = trim($row['name'] ?? $row['unit_name'] ?? $row['unit'] ?? '');
            if ($name === '') {
                $skipped++;
                continue;
            }

            $exists = UnitType::where('company_id', $company->id)->where('name', $name)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $code = trim($row['code'] ?? $row['unit_code'] ?? '');
            $isActive = !isset($row['is_active']) || in_array(strtolower(trim((string)$row['is_active'])), ['1', 'true', 'active', 'yes'], true);

            UnitType::create([
                'company_id' => $company->id,
                'name' => $name,
                'code' => $code !== '' ? $code : null,
                'is_active' => $isActive,
            ]);

            $created++;
        }

        return redirect()->route('unit-types.index')->with('success', __('messages.csv_import_success', [
            'created' => $created,
            'skipped' => $skipped,
        ]));
    }
}
