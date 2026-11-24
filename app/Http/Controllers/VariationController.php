<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Variation;
use App\Services\Pos\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VariationController extends Controller
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.view'), 403);

        $company = $this->tenant->company($user, $request->query('company_id'));
        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $query = Variation::query()
            ->where('company_id', $company->id)
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', (bool) $status))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('items', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $variations = $query->paginate(24)->withQueryString();

        $allVariations = Variation::where('company_id', $company->id)->get();
        $totalItems = $allVariations->sum(fn ($v) => is_array($v->items) ? count($v->items) : 0);

        $stats = [
            'total' => $allVariations->count(),
            'active' => $allVariations->where('is_active', true)->count(),
            'inactive' => $allVariations->where('is_active', false)->count(),
            'total_items' => $totalItems,
        ];

        return view('admin.variations.index', compact(
            'company',
            'variations',
            'stats',
            'status',
            'search'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.create'), 403);

        $company = $this->tenant->company($user, $request->input('company_id'));

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('variations', 'name')->where('company_id', $company->id),
            ],
            'items_input' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $items = [];
        if (! empty($validated['items_input'])) {
            $items = array_values(array_unique(array_filter(array_map('trim', explode(',', $validated['items_input'])))));
        }

        $variation = Variation::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'items' => $items,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Variation created successfully.',
                'data' => $variation,
            ]);
        }

        return redirect()->route('variations.index')->with('success', 'Variation created successfully.');
    }

    public function show(Request $request, Variation $variation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $this->tenant->company($user);
        abort_if($variation->company_id !== $company->id, 404);

        return response()->json($variation);
    }

    public function update(Request $request, Variation $variation): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($variation->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('variations', 'name')
                    ->where('company_id', $company->id)
                    ->ignore($variation->id),
            ],
            'items_input' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $items = $variation->items ?? [];
        if (isset($validated['items_input'])) {
            $items = array_values(array_unique(array_filter(array_map('trim', explode(',', $validated['items_input'])))));
        }

        $variation->update([
            'name' => $validated['name'],
            'items' => $items,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Variation updated successfully.',
                'data' => $variation,
            ]);
        }

        return redirect()->route('variations.index')->with('success', 'Variation updated successfully.');
    }

    public function addItem(Request $request, Variation $variation): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($variation->company_id !== $company->id, 404);

        $validated = $request->validate([
            'item' => ['required', 'string', 'max:60'],
        ]);

        $items = $variation->items ?? [];
        $newItem = trim($validated['item']);
        if (! in_array($newItem, $items, true)) {
            $items[] = $newItem;
            $variation->update(['items' => $items]);
        }

        return redirect()->route('variations.index')->with('success', "Item '{$newItem}' added to {$variation->name}.");
    }

    public function toggleStatus(Request $request, Variation $variation): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('products.update'), 403);

        $company = $this->tenant->company($user);
        abort_if($variation->company_id !== $company->id, 404);

        $variation->update(['is_active' => ! $variation->is_active]);

        $msg = $variation->is_active ? 'Variation activated.' : 'Variation deactivated.';

        return redirect()->route('variations.index')->with('success', $msg);
    }
}
