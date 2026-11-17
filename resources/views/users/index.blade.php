@extends('admin.layouts.dashboard')

@section('content')
    <div class="row">
        <div class="col-12 px-4">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold fs-5">Users</span>
                        <span class="text-muted small">Manage your store team and access</span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="dt-scope">
                        <div id="usersFilters" class="dt-filter-card mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-3">
                                    <label class="form-label mb-1" for="userRole">Role</label>
                                    <select id="userRole" class="form-select form-select-sm dt-filter" data-filter-key="type">
                                        <option value="">All roles</option>
                                        @foreach (config('access.roles') as $role => $details)
                                            <option value="{{ $role }}">{{ $details['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label mb-1" for="userActive">Status</label>
                                    <select id="userActive" class="form-select form-select-sm dt-filter" data-filter-key="is_active">
                                        <option value="">All statuses</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label mb-1" for="fromDate">Created from</label>
                                    <input id="fromDate" type="date" class="form-control form-control-sm dt-filter" data-filter-key="from_date">
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label mb-1" for="toDate">Created to</label>
                                    <input id="toDate" type="date" class="form-control form-control-sm dt-filter" data-filter-key="to_date">
                                </div>
                                <div class="col-12 col-md-2 d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-primary w-100 js-dt-apply">Apply</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 js-dt-reset">Reset</button>
                                </div>
                            </div>
                        </div>

                    <table
                        id="dt-users"
                        class="table table-hover align-middle w-100 js-yajra-dt"
                        data-dt-key="users"
                        data-dt-responsive-control="true"
                        data-dt-filter-scope="#usersFilters"
                        data-page-length="25"
                        data-dt-order='[[2,"asc"]]'
                        data-dt-exports='[
                            {"format":"csv","label":"CSV"},
                            {"format":"xlsx","label":"Excel"},
                            {"format":"pdf","lang":"en","label":"PDF"},
                            {"format":"pdf","lang":"bn","label":"PDF (BN)"}
                        ]'
                        data-dt-html-columns='["type","is_active","actions"]'
                        data-dt-columns='[
                            {"data":"DT_RowIndex","name":"DT_RowIndex","title":"SN","orderable":false,"searchable":false,"responsivePriority":1},
                            {"data":"name","name":"name","title":"Name","responsivePriority":2},
                            {"data":"email","name":"email","title":"Email","responsivePriority":50},
                            {"data":"type","name":"type","title":"Role","responsivePriority":60},
                            {"data":"is_active","name":"is_active","title":"Active","responsivePriority":70}
                        ]'>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
