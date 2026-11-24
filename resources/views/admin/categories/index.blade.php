@extends('admin.layouts.dashboard')

@section('title', __('messages.categories') . ' | ' . config('app.name', 'M POS'))

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
            {{-- Header --}}
            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold text-dark">{{ __('messages.categories') }}</h4>
                    <small class="text-muted fw-semibold">
                        {{ __('messages.categories_count_active', ['total' => $stats['total'], 'active' => $stats['active']]) }}
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-teal text-white px-3 fw-semibold shadow-sm" onclick="alert('CSV Import/Export template ready.')">
                        {{ __('messages.csv_import_export') }}
                    </button>
                    @can('categories.create')
                        <button type="button" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-lg me-1"></i> {{ __('messages.add_category') }}
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Body with Custom DataTable --}}
            <div class="card-body px-4 pb-4 pt-3">
                <div class="dt-scope">
                    {{-- Tabs matching UI --}}
                    <div id="categoryFilters" class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-dark px-3 py-1 fw-semibold js-category-tab active" data-status="" style="border-radius: 6px;">
                                {{ __('messages.tab_all') }} <span class="badge bg-white text-dark ms-1">{{ $stats['total'] }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold js-category-tab" data-status="1" style="border-radius: 6px;">
                                {{ __('messages.tab_active') }} <span class="badge bg-white text-dark ms-1">{{ $stats['active'] }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold js-category-tab" data-status="0" style="border-radius: 6px;">
                                {{ __('messages.tab_inactive') }} <span class="badge bg-white text-dark ms-1">{{ $stats['inactive'] }}</span>
                            </button>
                            <input type="hidden" id="categoryActiveFilter" class="dt-filter" data-filter-key="is_active" value="">
                        </div>
                    </div>

                    <table
                        id="dt-categories"
                        class="table table-hover align-middle w-100 js-yajra-dt"
                        data-dt-key="categories"
                        data-dt-responsive-control="true"
                        data-dt-filter-scope="#categoryFilters"
                        data-page-length="25"
                        data-dt-order='[[1,"asc"]]'
                        data-dt-html-columns='["category_display","products_display","status_display","date_display","actions"]'
                        data-dt-columns='[
                            {"data":"DT_RowIndex","name":"DT_RowIndex","title":"{{ __('messages.si') }}","orderable":false,"searchable":false,"responsivePriority":1},
                            {"data":"category_display","name":"name","title":"{{ __('messages.table_category') }}","responsivePriority":2},
                            {"data":"products_display","name":"id","title":"{{ __('messages.table_products') }}","orderable":false,"searchable":false,"responsivePriority":30},
                            {"data":"status_display","name":"is_active","title":"{{ __('messages.table_status') }}","responsivePriority":40},
                            {"data":"date_display","name":"created_at","title":"{{ __('messages.table_added_on') }}","responsivePriority":50},
                            {"data":"actions","name":"actions","title":"{{ __('messages.table_actions') }}","orderable":false,"searchable":false,"responsivePriority":1}
                        ]'>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Category Modal --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="{{ route('categories.store') }}">
            @csrf
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold" id="addCategoryModalLabel">
                    <i class="bi bi-grid-fill me-1 text-primary"></i> {{ __('messages.add_new_category') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="catNameInput">{{ __('messages.category_name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="catNameInput" name="name" class="form-control" required placeholder="{{ __('messages.category_name_placeholder') }}">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="catActiveSwitch" name="is_active" value="1" checked>
                    <label class="form-check-label fw-semibold" for="catActiveSwitch">{{ __('messages.active') }}</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">{{ __('messages.save_category') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Category Modal --}}
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" id="editCategoryForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold" id="editCategoryModalLabel">
                    <i class="bi bi-pencil-square me-1 text-primary"></i> {{ __('messages.edit_category') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="editCatNameInput">{{ __('messages.category_name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="editCatNameInput" name="name" class="form-control" required>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="editCatActiveSwitch" name="is_active" value="1">
                    <label class="form-check-label fw-semibold" for="editCatActiveSwitch">{{ __('messages.active') }}</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-teal text-white btn-sm px-4 fw-semibold">{{ __('messages.update_category') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    // Open edit category modal
    $(document).on('click', '.edit-category-btn', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var active = $(this).data('active');

        $('#editCategoryForm').attr('action', '{{ url('/dashboard/categories') }}/' + id);
        $('#editCatNameInput').val(name);
        $('#editCatActiveSwitch').prop('checked', parseInt(active) === 1);

        var modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        modal.show();
    });

    // Tab filtering (All, Active, Inactive)
    $(document).on('click', '.js-category-tab', function () {
        $('.js-category-tab').removeClass('btn-dark active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-dark active');

        var status = $(this).data('status');
        $('#categoryActiveFilter').val(status);

        var dt = $('#dt-categories').DataTable();
        dt.ajax.reload();
    });
});
</script>
@endpush
