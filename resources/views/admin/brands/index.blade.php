@extends('admin.layouts.dashboard')

@section('title', __('messages.brands') . ' | ' . config('app.name', 'Micro POS'))

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
            <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold text-body">{{ __('messages.brands') }}</h4>
                    <small class="text-body-secondary fw-semibold">
                        {{ __('messages.brands_count_active', ['total' => $stats['total'], 'active' => $stats['active']]) }}
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-teal text-white px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#csvBrandModal">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('messages.csv_import_export') }}
                    </button>
                    @can('products.create')
                        <button type="button" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                            <i class="bi bi-plus-lg me-1"></i> {{ __('messages.add_brand') }}
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Body with Custom DataTable --}}
            <div class="card-body px-4 pb-4 pt-3">
                <div class="dt-scope">
                    {{-- Tabs matching UI --}}
                    <div id="brandFilters" class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-dark px-3 py-1 fw-semibold js-brand-tab active" data-status="" style="border-radius: 6px;">
                                {{ __('messages.tab_all') }} <span class="badge bg-white text-dark ms-1">{{ $stats['total'] }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold js-brand-tab" data-status="1" style="border-radius: 6px;">
                                {{ __('messages.tab_active') }} <span class="badge bg-white text-dark ms-1">{{ $stats['active'] }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold js-brand-tab" data-status="0" style="border-radius: 6px;">
                                {{ __('messages.tab_inactive') }} <span class="badge bg-white text-dark ms-1">{{ $stats['inactive'] }}</span>
                            </button>
                            <input type="hidden" id="brandActiveFilter" class="dt-filter" data-filter-key="is_active" value="">
                        </div>
                    </div>

                    <table
                        id="dt-brands"
                        class="table table-hover align-middle w-100 js-yajra-dt"
                        data-dt-key="brands"
                        data-dt-responsive-control="true"
                        data-dt-filter-scope="#brandFilters"
                        data-page-length="25"
                        data-dt-order='[[1,"asc"]]'
                        data-dt-html-columns='["brand_display","products_display","status_display","date_display","actions"]'
                        data-dt-columns='[
                            {"data":"DT_RowIndex","name":"DT_RowIndex","title":"{{ __('messages.si') }}","orderable":false,"searchable":false,"responsivePriority":1},
                            {"data":"brand_display","name":"name","title":"{{ __('messages.table_brand') }}","responsivePriority":2},
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

{{-- Add Brand Modal --}}
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="{{ route('brands.store') }}">
            @csrf
            <div class="modal-header border-bottom bg-body-tertiary">
                <h5 class="modal-title fw-bold text-body" id="addBrandModalLabel">
                    <i class="bi bi-tag-fill me-1 text-primary"></i> {{ __('messages.add_new_brand') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="brandNameInput">{{ __('messages.brand_name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="brandNameInput" name="name" class="form-control" required placeholder="{{ __('messages.brand_name_placeholder') }}">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="brandActiveSwitch" name="is_active" value="1" checked>
                    <label class="form-check-label fw-semibold text-body" for="brandActiveSwitch">{{ __('messages.active') }}</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">{{ __('messages.save_brand') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Brand Modal --}}
<div class="modal fade" id="editBrandModal" tabindex="-1" aria-labelledby="editBrandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" id="editBrandForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom bg-body-tertiary">
                <h5 class="modal-title fw-bold text-body" id="editBrandModalLabel">
                    <i class="bi bi-pencil-square me-1 text-primary"></i> {{ __('messages.edit_brand') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="editBrandNameInput">{{ __('messages.brand_name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="editBrandNameInput" name="name" class="form-control" required>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="editBrandActiveSwitch" name="is_active" value="1">
                    <label class="form-check-label fw-semibold text-body" for="editBrandActiveSwitch">{{ __('messages.active') }}</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-teal text-white btn-sm px-4 fw-semibold">{{ __('messages.update_brand') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- CSV Import / Export Modal --}}
<div class="modal fade" id="csvBrandModal" tabindex="-1" aria-labelledby="csvBrandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-body-tertiary">
                <h5 class="modal-title fw-bold text-body" id="csvBrandModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet-fill text-teal me-1"></i> {{ __('messages.csv_import_export') }} - {{ __('messages.brands') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Export Section --}}
                <div class="p-3 mb-4 rounded-3 border bg-body-tertiary">
                    <h6 class="fw-bold text-body mb-1">
                        <i class="bi bi-download text-primary me-1"></i> {{ __('messages.csv_export_title') }}
                    </h6>
                    <p class="text-body-secondary small mb-3">{{ __('messages.csv_export_desc') }}</p>
                    <a href="{{ route('brands.export.csv') }}" class="btn btn-outline-primary btn-sm fw-semibold">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i> {{ __('messages.download_csv_file') }}
                    </a>
                </div>

                {{-- Import Section --}}
                <div class="p-3 rounded-3 border bg-body-tertiary">
                    <h6 class="fw-bold text-body mb-1">
                        <i class="bi bi-upload text-teal me-1"></i> {{ __('messages.csv_import_title') }}
                    </h6>
                    <p class="text-body-secondary small mb-2">{{ __('messages.csv_import_desc') }}</p>
                    <div class="mb-3">
                        <a href="{{ route('brands.sample.csv') }}" class="btn btn-link btn-sm p-0 text-decoration-none">
                            <i class="bi bi-file-earmark-text me-1"></i> {{ __('messages.download_sample_template') }}
                        </a>
                    </div>
                    <form action="{{ route('brands.import.csv') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-body small" for="brandCsvFile">
                                {{ __('messages.choose_csv_file') }} <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="csv_file" id="brandCsvFile" accept=".csv" required class="form-control form-control-sm">
                        </div>
                        <button type="submit" class="btn btn-teal text-white btn-sm px-4 fw-semibold shadow-sm">
                            <i class="bi bi-upload me-1"></i> {{ __('messages.upload_and_import') }}
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    // Open edit brand modal
    $(document).on('click', '.edit-brand-btn', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var active = $(this).data('active');

        $('#editBrandForm').attr('action', '{{ url('/dashboard/brands') }}/' + id);
        $('#editBrandNameInput').val(name);
        $('#editBrandActiveSwitch').prop('checked', parseInt(active) === 1);

        var modal = new bootstrap.Modal(document.getElementById('editBrandModal'));
        modal.show();
    });

    // Tab filtering (All, Active, Inactive)
    $(document).on('click', '.js-brand-tab', function () {
        $('.js-brand-tab').removeClass('btn-dark active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-dark active');

        var status = $(this).data('status');
        $('#brandActiveFilter').val(status);

        var dt = $('#dt-brands').DataTable();
        dt.ajax.reload();
    });
});
</script>
@endpush
