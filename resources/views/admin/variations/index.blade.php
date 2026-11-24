@extends('admin.layouts.dashboard')

@section('title', 'Variations | ' . config('app.name', 'Micro POS'))

@section('content')
<div class="row">
    <div class="col-12 px-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            {{-- Header matching Screenshot 11 --}}
            <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold text-body">Variations</h4>
                    <small class="text-body-secondary fw-semibold">
                        {{ $stats['total'] }} variations, {{ $stats['total_items'] }} items
                    </small>
                </div>
                <div>
                    @can('products.create')
                        <button type="button" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addVariationModal">
                            <i class="bi bi-plus-lg me-1"></i> Add variation
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Tabs & Search bar --}}
            <div class="card-body pb-0 pt-3 border-bottom bg-body-tertiary">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('variations.index') }}"
                           class="btn btn-sm {{ $status === null || $status === '' ? 'btn-primary' : 'btn-outline-secondary' }} px-3 py-1 fw-semibold">
                            All <span class="badge bg-body-tertiary text-body ms-1">{{ $stats['total'] }}</span>
                        </a>
                        <a href="{{ route('variations.index', ['status' => '1', 'q' => $search]) }}"
                           class="btn btn-sm {{ $status === '1' ? 'btn-primary' : 'btn-outline-secondary' }} px-3 py-1 fw-semibold">
                            Active <span class="badge bg-body-tertiary text-body ms-1">{{ $stats['active'] }}</span>
                        </a>
                        <a href="{{ route('variations.index', ['status' => '0', 'q' => $search]) }}"
                           class="btn btn-sm {{ $status === '0' ? 'btn-primary' : 'btn-outline-secondary' }} px-3 py-1 fw-semibold">
                            Inactive <span class="badge bg-body-tertiary text-body ms-1">{{ $stats['inactive'] }}</span>
                        </a>
                    </div>
                    <div style="min-width: 250px;">
                        <form action="{{ route('variations.index') }}" method="GET" class="input-group input-group-sm">
                            @if($status !== null)
                                <input type="hidden" name="status" value="{{ $status }}">
                            @endif
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search variation or item">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            @if($search !== '')
                                <a href="{{ route('variations.index', ['status' => $status]) }}" class="btn btn-outline-danger">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            {{-- Variations Card Grid matching Screenshot 11 --}}
            <div class="card-body p-4 bg-body-tertiary">
                <div class="row g-4">
                    @forelse($variations as $v)
                        @php
                            $items = is_array($v->items) ? $v->items : [];
                        @endphp
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm" style="border-radius: 10px;">
                                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <h5 class="mb-0 fw-bold text-body">{{ $v->name }}</h5>
                                        @if($v->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Inactive</span>
                                        @endif
                                        <small class="text-body-secondary fw-semibold">({{ count($items) }} items)</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-teal text-white edit-var-btn"
                                            data-id="{{ $v->id }}"
                                            data-name="{{ $v->name }}"
                                            data-items="{{ implode(', ', $items) }}"
                                            data-active="{{ $v->is_active ? 1 : 0 }}"
                                            data-bs-toggle="modal" data-bs-target="#editVariationModal">
                                        Edit
                                    </button>
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        @forelse($items as $item)
                                            <span class="badge bg-body-tertiary text-body border px-3 py-2 fs-6 fw-normal rounded-3">
                                                {{ $item }}
                                            </span>
                                        @empty
                                            <span class="text-muted small">No items yet.</span>
                                        @endforelse

                                        <button type="button" class="btn btn-sm btn-outline-primary border-dashed rounded-3 px-3 py-1 fw-semibold add-item-btn"
                                                data-id="{{ $v->id }}"
                                                data-name="{{ $v->name }}"
                                                data-bs-toggle="modal" data-bs-target="#addItemModal">
                                            <i class="bi bi-plus"></i> Add item
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="bi bi-tags fs-1 d-block mb-2 text-secondary"></i>
                            No variations defined yet.
                        </div>
                    @endforelse
                </div>
            </div>

            @if($variations->hasPages())
                <div class="card-footer bg-white py-3 border-top d-flex justify-content-end">
                    {{ $variations->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Add Variation Modal --}}
<div class="modal fade" id="addVariationModal" tabindex="-1" aria-labelledby="addVariationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="{{ route('variations.store') }}">
            @csrf
            <div class="modal-header border-bottom bg-body-tertiary">
                <h5 class="modal-title fw-bold text-body" id="addVariationModalLabel">
                    <i class="bi bi-diagram-3-fill me-1 text-primary"></i> Add Variation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="varNameInput">Variation Name <span class="text-danger">*</span></label>
                    <input type="text" id="varNameInput" name="name" class="form-control" required placeholder="e.g. Size, Color, Capacity">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="varItemsInput">Items (comma separated)</label>
                    <textarea id="varItemsInput" name="items_input" class="form-control" rows="3" placeholder="e.g. S, M, L, XL, XXL or Red, Blue, Black"></textarea>
                    <small class="text-body-secondary">Separate multiple values by comma.</small>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="varActiveSwitch" name="is_active" value="1" checked>
                    <label class="form-check-label fw-semibold text-body" for="varActiveSwitch">Active</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Save Variation</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Variation Modal --}}
<div class="modal fade" id="editVariationModal" tabindex="-1" aria-labelledby="editVariationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" id="editVariationForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom bg-body-tertiary">
                <h5 class="modal-title fw-bold text-body" id="editVariationModalLabel">Edit Variation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="editVarNameInput">Variation Name <span class="text-danger">*</span></label>
                    <input type="text" id="editVarNameInput" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="editVarItemsInput">Items (comma separated)</label>
                    <textarea id="editVarItemsInput" name="items_input" class="form-control" rows="3"></textarea>
                    <small class="text-body-secondary">Separate multiple values by comma.</small>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="editVarActiveSwitch" name="is_active" value="1">
                    <label class="form-check-label fw-semibold text-body" for="editVarActiveSwitch">Active</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal text-white btn-sm px-4 fw-semibold">Update Variation</button>
            </div>
        </form>
    </div>
</div>

{{-- Quick Add Item Modal --}}
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form class="modal-content border-0 shadow" id="addItemForm" method="POST" action="">
            @csrf
            <div class="modal-header border-bottom bg-body-tertiary">
                <h6 class="modal-title fw-bold text-body" id="addItemModalTitle">Add Item</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <label class="form-label fw-semibold small text-body" for="singleItemInput">Item Value <span class="text-danger">*</span></label>
                <input type="text" id="singleItemInput" name="item" class="form-control" required placeholder="e.g. 44, XL, Golden">
            </div>
            <div class="modal-footer border-top bg-body-tertiary p-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Add</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editBtns = document.querySelectorAll('.edit-var-btn');
    const editForm = document.getElementById('editVariationForm');
    const editName = document.getElementById('editVarNameInput');
    const editItems = document.getElementById('editVarItemsInput');
    const editActive = document.getElementById('editVarActiveSwitch');

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            editForm.action = `/dashboard/variations/${id}`;
            editName.value = this.getAttribute('data-name');
            editItems.value = this.getAttribute('data-items');
            editActive.checked = this.getAttribute('data-active') === '1';
        });
    });

    const addItemBtns = document.querySelectorAll('.add-item-btn');
    const addItemForm = document.getElementById('addItemForm');
    const addItemTitle = document.getElementById('addItemModalTitle');
    const singleInput = document.getElementById('singleItemInput');

    addItemBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            addItemForm.action = `/dashboard/variations/${id}/add-item`;
            addItemTitle.textContent = `Add item to ${name}`;
            singleInput.value = '';
            setTimeout(() => singleInput.focus(), 300);
        });
    });
});
</script>
@endpush
