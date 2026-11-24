<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\User;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.view') || $user->can('brands.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $status = $request->query('status'); // null, '1', '0'
        $search = trim((string) $request->query('q', ''));

        $query = Brand::query()
            ->where('company_id', $company->id)
            ->withCount('products')
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', (bool) $status))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name');

        $brands = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => Brand::where('company_id', $company->id)->count(),
            'active' => Brand::where('company_id', $company->id)->where('is_active', true)->count(),
            'inactive' => Brand::where('company_id', $company->id)->where('is_active', false)->count(),
        ];

        return view('admin.brands.index', compact(
            'company',
            'brands',
            'stats',
            'status',
            'search'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.create') || $user->can('brands.create'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('brands', 'name')->where('company_id', $company->id),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $brand = Brand::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Brand added successfully.',
                'data' => $brand,
            ]);
        }

        return redirect()->route('brands.index')->with('success', 'Brand added successfully.');
    }

    public function show(Request $request, Brand $brand): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $this->tenant->company($user);
        abort_if($brand->company_id !== $company->id, 404);

        return response()->json($brand);
    }

    public function update(Request $request, Brand $brand): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.update') || $user->can('brands.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($brand->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('brands', 'name')
                    ->where('company_id', $company->id)
                    ->ignore($brand->id),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $brand->update([
            'name' => $validated['name'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully.',
                'data' => $brand,
            ]);
        }

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
    }

    public function toggleStatus(Request $request, Brand $brand): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.update') || $user->can('brands.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($brand->company_id !== $company->id, 404);

        $brand->update(['is_active' => ! $brand->is_active]);

        $msg = $brand->is_active ? 'Brand activated successfully.' : 'Brand deactivated successfully.';

        return redirect()->route('brands.index')->with('success', $msg);
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.delete') || $user->can('brands.delete'), 403);

        $company = $this->tenant->company($user);
        abort_if($brand->company_id !== $company->id, 404);

        $brand->delete();

        return redirect()->route('brands.index')->with('success', 'Brand deleted successfully.');
    }
}
