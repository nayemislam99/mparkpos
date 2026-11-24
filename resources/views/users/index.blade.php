@extends('admin.layouts.dashboard')

@section('title', __('messages.user_management') . ' | ' . config('app.name', 'M POS'))

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
            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold text-dark">{{ __('messages.users') }}</h4>
                    <small class="text-muted fw-semibold">
                        Manage your store team and access &bull; {{ $stats['total'] }} {{ __('messages.users') }} &bull; <span class="text-success">{{ $stats['active'] }} {{ __('messages.active') }}</span>
                    </small>
                </div>
                <div>
                    @can('users.create')
                        <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary px-3 py-2 fw-semibold shadow-sm" style="border-radius: 8px;">
                            <i class="bi bi-person-plus-fill me-1"></i> {{ __('messages.add_new_user') }}
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card-body px-4 pb-4 pt-3">
                <div class="dt-scope">
                    <div id="usersFilters" class="dt-filter-card mb-3 p-3 rounded-3 bg-light border">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold small mb-1" for="userRole">{{ __('messages.table_role') }}</label>
                                <select id="userRole" class="form-select form-select-sm dt-filter" data-filter-key="type">
                                    <option value="">{{ __('messages.all_roles') }}</option>
                                    @foreach ($roles as $roleKey => $details)
                                        <option value="{{ $roleKey }}">{{ $details['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold small mb-1" for="userActive">{{ __('messages.table_status') }}</label>
                                <select id="userActive" class="form-select form-select-sm dt-filter" data-filter-key="is_active">
                                    <option value="">{{ __('messages.all_statuses') }}</option>
                                    <option value="1">{{ __('messages.active') }}</option>
                                    <option value="0">{{ __('messages.inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label fw-semibold small mb-1" for="fromDate">{{ __('messages.created_from') }}</label>
                                <input id="fromDate" type="date" class="form-control form-control-sm dt-filter" data-filter-key="from_date">
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label fw-semibold small mb-1" for="toDate">{{ __('messages.created_to') }}</label>
                                <input id="toDate" type="date" class="form-control form-control-sm dt-filter" data-filter-key="to_date">
                            </div>
                            <div class="col-12 col-md-2 d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary w-100 js-dt-apply fw-semibold">{{ __('messages.apply') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 js-dt-reset fw-semibold">{{ __('messages.reset') }}</button>
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
                        data-dt-order='[[1,"asc"]]'
                        data-dt-html-columns='["name_display","role_display","branch_display","status_display","actions"]'
                        data-dt-columns='[
                            {"data":"DT_RowIndex","name":"DT_RowIndex","title":"SN","orderable":false,"searchable":false,"responsivePriority":1},
                            {"data":"name_display","name":"name","title":"{{ __('messages.table_name') }}","responsivePriority":2},
                            {"data":"email","name":"email","title":"{{ __('messages.table_email') }}","responsivePriority":30},
                            {"data":"role_display","name":"type","title":"{{ __('messages.table_role') }}","responsivePriority":40},
                            {"data":"branch_display","name":"branch_id","title":"{{ __('messages.table_branch') }}","responsivePriority":50},
                            {"data":"status_display","name":"is_active","title":"{{ __('messages.table_status') }}","responsivePriority":60},
                            {"data":"actions","name":"actions","title":"{{ __('messages.table_actions') }}","orderable":false,"searchable":false,"responsivePriority":1}
                        ]'>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
