@extends('admin.layouts.dashboard')

@section('title', 'All Branch | ' . config('app.name', 'M POS'))

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
                <div class="card-header bg-transparent border-0 d-flex flex-wrap align-items-center justify-content-between pt-4 px-4 pb-2">
                    <div>
                        <h3 class="fw-bold mb-0">All Branch</h3>
                        <p class="text-body-secondary small mb-0">{{ $stats['total'] }} branches</p>
                    </div>
                    @can('branches.create')
                        <button type="button" class="btn btn-success-pos d-flex align-items-center gap-1 px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addBranchModal" style="border-radius: 8px;">
                            <i class="bi bi-plus-lg"></i> Add New Branch
                        </button>
                    @endcan
                </div>

                <div class="card-body px-4 pb-4">
                    <div class="dt-scope">
                        {{-- Filter Tabs and Search Bar (matching screenshot) --}}
                        <div id="branchFilters" class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-dark px-3 py-1 fw-semibold js-branch-tab active" data-status="" style="border-radius: 6px;">
                                    All {{ $stats['total'] }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold js-branch-tab" data-status="1" style="border-radius: 6px;">
                                    Active {{ $stats['active'] }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold js-branch-tab" data-status="0" style="border-radius: 6px;">
                                    Deactive {{ $stats['total'] - $stats['active'] }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1 fw-semibold js-branch-tab" data-status="pending" style="border-radius: 6px;">
                                    Pending 0
                                </button>
                                <input type="hidden" id="branchActiveFilter" class="dt-filter" data-filter-key="is_active" value="">
                            </div>
                        </div>

                        <table
                            id="dt-branches"
                            class="table table-hover align-middle w-100 js-yajra-dt"
                            data-dt-key="branches"
                            data-dt-responsive-control="true"
                            data-dt-filter-scope="#branchFilters"
                            data-page-length="25"
                            data-dt-order='[[1,"asc"]]'
                            data-dt-html-columns='["branch_display","address_display","is_active","actions"]'
                            data-dt-columns='[
                                {"data":"DT_RowIndex","name":"DT_RowIndex","title":"SI","orderable":false,"searchable":false,"responsivePriority":1},
                                {"data":"branch_display","name":"name","title":"Branch","responsivePriority":2},
                                {"data":"address_display","name":"address","title":"Address","responsivePriority":30},
                                {"data":"is_active","name":"is_active","title":"Status","responsivePriority":40},
                                {"data":"actions","name":"actions","title":"Actions","orderable":false,"searchable":false,"responsivePriority":1}
                            ]'>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Branch Modal --}}
    <div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <form id="addBranchForm" method="POST" action="{{ route('branches.store') }}">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="addBranchModalLabel">
                            <i class="bi bi-shop me-1 text-primary"></i> Add New Branch
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="branchName">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" id="branchName" name="name" class="form-control" required placeholder="e.g. Shopno">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="branchCode">Branch Code</label>
                                <input type="text" id="branchCode" name="code" class="form-control" placeholder="e.g. BR-01">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="branchPhone">Phone Number</label>
                                <input type="text" id="branchPhone" name="phone" class="form-control" placeholder="e.g. +8801977235615">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="branchBin">Branch BIN</label>
                                <input type="text" id="branchBin" name="branch_bin" class="form-control" placeholder="Tax / BIN">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="branchEmail">Email Address</label>
                                <input type="email" id="branchEmail" name="email" class="form-control" placeholder="branch@store.com">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="branchAddress">Address</label>
                            <textarea id="branchAddress" name="address" rows="2" class="form-control" placeholder="e.g. mirpur-10, Shah-Ali plaza, Level-11, House-1205"></textarea>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-2">
                            <div>
                                <label class="form-check-label fw-semibold d-block" for="isHeadOffice">Head Office</label>
                                <small class="text-body-secondary">Main administrative branch.</small>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="isHeadOffice" name="is_head_office" value="1">
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                            <div>
                                <label class="form-check-label fw-semibold d-block" for="isBranchActive">Status</label>
                                <small class="text-body-secondary">Set branch to Active.</small>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="isBranchActive" name="is_active" value="1" checked>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success-pos px-4">Save Branch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Branch Modal --}}
    <div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="editBranchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <form id="editBranchForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="editBranchModalLabel">
                            <i class="bi bi-pencil-square me-1 text-primary"></i> Edit Branch
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" id="editBranchId">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="editBranchName">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" id="editBranchName" name="name" class="form-control" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="editBranchCode">Branch Code</label>
                                <input type="text" id="editBranchCode" name="code" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="editBranchPhone">Phone Number</label>
                                <input type="text" id="editBranchPhone" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="editBranchBin">Branch BIN</label>
                                <input type="text" id="editBranchBin" name="branch_bin" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="editBranchEmail">Email Address</label>
                                <input type="email" id="editBranchEmail" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="editBranchAddress">Address</label>
                            <textarea id="editBranchAddress" name="address" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-2">
                            <div>
                                <label class="form-check-label fw-semibold d-block" for="editIsHeadOffice">Head Office</label>
                                <small class="text-body-secondary">Main administrative branch.</small>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="editIsHeadOffice" name="is_head_office" value="1">
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                            <div>
                                <label class="form-check-label fw-semibold d-block" for="editIsActive">Active Status</label>
                                <small class="text-body-secondary">Set branch to Active or Deactive.</small>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="editIsActive" name="is_active" value="1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-teal px-4">Update Branch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    window.branchNameRenderer = function(data, type, row) {
        var name = row.name || '';
        var phone = row.phone || '';
        var html = '<div class="fw-bold text-dark">' + name + '</div>';
        if (phone) {
            html += '<div class="text-body-secondary small">' + phone + '</div>';
        }
        return html;
    };

    window.branchAddressRenderer = function(data, type, row) {
        return row.address ? '<span class="text-body-secondary">' + row.address + '</span>' : '<span class="text-muted">-</span>';
    };

    window.branchActionsRenderer = function(data, type, row) {
        return '<button type="button" class="btn btn-sm btn-teal px-3" onclick="openEditBranchModal(' + row.id + ')" style="border-radius: 6px;">Edit</button>';
    };

    window.openEditBranchModal = function(id) {
        fetch('{{ url('/dashboard/branches') }}/' + id, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(res) { return res.json(); })
        .then(function(branch) {
            document.getElementById('editBranchId').value = branch.id;
            document.getElementById('editBranchName').value = branch.name || '';
            document.getElementById('editBranchCode').value = branch.code || '';
            document.getElementById('editBranchBin').value = branch.branch_bin || '';
            document.getElementById('editBranchPhone').value = branch.phone || '';
            document.getElementById('editBranchEmail').value = branch.email || '';
            document.getElementById('editBranchAddress').value = branch.address || '';
            document.getElementById('editIsHeadOffice').checked = !!branch.is_head_office;
            document.getElementById('editIsActive').checked = !!branch.is_active;

            document.getElementById('editBranchForm').action = '{{ url('/dashboard/branches') }}/' + branch.id;
            var modal = new bootstrap.Modal(document.getElementById('editBranchModal'));
            modal.show();
        })
        .catch(function() {
            alert('Failed to load branch details.');
        });
    };

    // Filter tab handler (All, Active, Deactive, Pending)
    $(document).on('click', '.js-branch-tab', function () {
        $('.js-branch-tab').removeClass('btn-dark active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-dark active');

        var status = $(this).data('status');
        $('#branchActiveFilter').val(status);

        var dt = $('#dt-branches').DataTable();
        dt.ajax.reload();
    });
</script>
@endpush
