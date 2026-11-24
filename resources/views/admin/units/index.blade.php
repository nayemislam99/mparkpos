@extends('admin.layouts.dashboard')

@section('title', __('messages.unit_types') . ' | ' . config('app.name', 'Micro POS'))

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
                    <h4 class="mb-0 fw-bold text-body">{{ __('messages.unit_types') }}</h4>
                    <small class="text-body-secondary fw-semibold">
                        {{ __('messages.unit_types_count_active', ['total' => $stats['total'], 'active' => $stats['active']]) }}
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-teal text-white px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#csvUnitModal">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('messages.csv_import_export') }}
                    </button>
                    @can('products.create')
                        <button type="button" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUnitTypeModal">
                            <i class="bi bi-plus-lg me-1"></i> {{ __('messages.add_unit_type') }}
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Body with Custom DataTable --}}
            <div class="card-body px-4 pb-4 pt-3">
                <div class="dt-scope">
                    {{-- Tabs matching UI --}}
                    <div id="unitTypeFilters" class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-dark px-3 py-1 fw-semibold js-unit-tab active" data-status="" style="border-radius: 6px;">
                                {{ __('messages.tab_all') }} <span class="badge bg-white text-dark ms-1">{{ $stats['total'] }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold js-unit-tab" data-status="1" style="border-radius: 6px;">
                                {{ __('messages.tab_active') }} <span class="badge bg-white text-dark ms-1">{{ $stats['active'] }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold js-unit-tab" data-status="0" style="border-radius: 6px;">
                                {{ __('messages.tab_inactive') }} <span class="badge bg-white text-dark ms-1">{{ $stats['inactive'] }}</span>
                            </button>
                            <input type="hidden" id="unitActiveFilter" class="dt-filter" data-filter-key="is_active" value="">
                        </div>
                    </div>

                    <table
                        id="dt-unit-types"
                        class="table table-hover align-middle w-100 js-yajra-dt"
                        data-dt-key="unit_types"
                        data-dt-responsive-control="true"
                        data-dt-filter-scope="#unitTypeFilters"
                        data-page-length="25"
                        data-dt-order='[[1,"asc"]]'
                        data-dt-html-columns='["unit_display","code_display","status_display","date_display","actions"]'
                        data-dt-columns='[
                            {"data":"DT_RowIndex","name":"DT_RowIndex","title":"{{ __('messages.si') }}","orderable":false,"searchable":false,"responsivePriority":1},
                            {"data":"unit_display","name":"name","title":"{{ __('messages.table_unit') }}","responsivePriority":2},
                            {"data":"code_display","name":"code","title":"{{ __('messages.table_code') }}","responsivePriority":30},
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

{{-- Add Unit Type Modal --}}
<div class="modal fade" id="addUnitTypeModal" tabindex="-1" aria-labelledby="addUnitTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="{{ route('unit-types.store') }}">
            @csrf
            <div class="modal-header border-bottom bg-body-tertiary">
                <h5 class="modal-title fw-bold text-body" id="addUnitTypeModalLabel">
                    <i class="bi bi-box-seam me-1 text-primary"></i> {{ __('messages.add_new_unit_type') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="unitNameInput">{{ __('messages.unit_name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="unitNameInput" name="name" class="form-control" required placeholder="{{ __('messages.unit_name_placeholder') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="unitCodeInput">{{ __('messages.unit_code') }}</label>
                    <input type="text" id="unitCodeInput" name="code" class="form-control" placeholder="{{ __('messages.unit_code_placeholder') }}">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="unitActiveSwitch" name="is_active" value="1" checked>
                    <label class="form-check-label fw-semibold text-body" for="unitActiveSwitch">{{ __('messages.active') }}</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">{{ __('messages.save_unit_type') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Unit Type Modal --}}
<div class="modal fade" id="editUnitTypeModal" tabindex="-1" aria-labelledby="editUnitTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" id="editUnitTypeForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom bg-body-tertiary">
                <h5 class="modal-title fw-bold text-body" id="editUnitTypeModalLabel">
                    <i class="bi bi-pencil-square me-1 text-primary"></i> {{ __('messages.edit_unit_type') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="editUnitNameInput">{{ __('messages.unit_name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="editUnitNameInput" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body" for="editUnitCodeInput">{{ __('messages.unit_code') }}</label>
                    <input type="text" id="editUnitCodeInput" name="code" class="form-control">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="editUnitActiveSwitch" name="is_active" value="1">
                    <label class="form-check-label fw-semibold text-body" for="editUnitActiveSwitch">{{ __('messages.active') }}</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-teal text-white btn-sm px-4 fw-semibold">{{ __('messages.update_unit_type') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- CSV Import / Export Modal --}}
<div class="modal fade" id="csvUnitModal" tabindex="-1" aria-labelledby="csvUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-body-tertiary">
                <h5 class="modal-title fw-bold text-body" id="csvUnitModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet-fill text-teal me-1"></i> {{ __('messages.csv_import_export') }} - {{ __('messages.unit_types') }}
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
                    <a href="{{ route('unit-types.export.csv') }}" class="btn btn-outline-primary btn-sm fw-semibold">
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
                        <a href="{{ route('unit-types.sample.csv') }}" class="btn btn-link btn-sm p-0 text-decoration-none">
                            <i class="bi bi-file-earmark-text me-1"></i> {{ __('messages.download_sample_template') }}
                        </a>
                    </div>
                    <form action="{{ route('unit-types.import.csv') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-body small" for="unitCsvFile">
                                {{ __('messages.choose_csv_file') }} <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="csv_file" id="unitCsvFile" accept=".csv" required class="form-control form-control-sm">
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
    // Open edit unit modal
    $(document).on('click', '.edit-unit-btn', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var code = $(this).data('code');
        var active = $(this).data('active');

        $('#editUnitTypeForm').attr('action', '{{ url('/dashboard/unit-types') }}/' + id);
        $('#editUnitNameInput').val(name);
        $('#editUnitCodeInput').val(code || '');
        $('#editUnitActiveSwitch').prop('checked', parseInt(active) === 1);

        var modal = new bootstrap.Modal(document.getElementById('editUnitTypeModal'));
        modal.show();
    });

    // Tab filtering (All, Active, Inactive)
    $(document).on('click', '.js-unit-tab', function () {
        $('.js-unit-tab').removeClass('btn-dark active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-dark active');

        var status = $(this).data('status');
        $('#unitActiveFilter').val(status);

        var dt = $('#dt-unit-types').DataTable();
        dt.ajax.reload();
    });
});
</script>
@endpush
