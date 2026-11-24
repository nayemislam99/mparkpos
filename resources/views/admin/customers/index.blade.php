@extends('admin.layouts.dashboard')

@section('title', __('messages.customer_list') . ' | ' . config('app.name', 'Micro POS'))

@push('css')
<style>
  #dt-customers th, #dt-customers td {
    vertical-align: middle;
  }
  #dt-customers td.text-nowrap, #dt-customers th.text-nowrap {
    white-space: nowrap !important;
  }
  #dt-customers td:last-child {
    white-space: nowrap !important;
    min-width: 175px;
  }
  .customer-metric-card {
    border-radius: 12px;
    border: 1px solid var(--cui-border-color, #e2e8f0);
    background: var(--cui-card-bg, #ffffff);
    transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }
  .customer-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.08);
  }
  .dt-scope .dt-filter-card {
    border-radius: 12px;
    background: var(--cui-tertiary-bg, #f8fafc);
    border: 1px solid var(--cui-border-color, #e2e8f0) !important;
  }
  [data-coreui-theme="dark"] .customer-metric-card {
    background: #1e222d !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
  }
  [data-coreui-theme="dark"] .dt-scope .dt-filter-card {
    background: #1e222d !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
  }
  [data-coreui-theme="dark"] .dt-scope .dt-filter-card .form-label {
    color: #f8fafc !important;
    font-weight: 600;
  }
  [data-coreui-theme="dark"] .dt-scope .dt-filter-card .form-select,
  [data-coreui-theme="dark"] .dt-scope .dt-filter-card .form-control {
    background-color: #161922 !important;
    border-color: rgba(255, 255, 255, 0.18) !important;
    color: #f8fafc !important;
  }
  [data-coreui-theme="dark"] .dt-scope .dt-filter-card .form-select option {
    background-color: #161922 !important;
    color: #f8fafc !important;
  }
</style>
@endpush

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

        {{-- Top Summary Metric Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="customer-metric-card p-3 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-body-secondary fw-semibold small text-uppercase">{{ __('messages.total_customers') }}</span>
                            <h3 class="fw-bold mb-0 text-body mt-1">{{ number_format($stats['total']) }}</h3>
                            <small class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>{{ $stats['active'] }} {{ __('messages.active') }}</small>
                        </div>
                        <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary fs-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="customer-metric-card p-3 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-body-secondary fw-semibold small text-uppercase">{{ __('messages.total_due_amount') }}</span>
                            <h3 class="fw-bold mb-0 text-danger mt-1">৳ {{ number_format($stats['total_outstanding_due'], 2) }}</h3>
                            <small class="text-body-secondary">{{ __('messages.opening_due_part') }}: ৳ {{ number_format($stats['total_opening_due'], 2) }}</small>
                        </div>
                        <div class="rounded-circle p-3 bg-danger bg-opacity-10 text-danger fs-3">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="customer-metric-card p-3 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-body-secondary fw-semibold small text-uppercase">{{ __('messages.total_advance_balance') }}</span>
                            <h3 class="fw-bold mb-0 text-success mt-1">৳ {{ number_format($stats['total_advance'], 2) }}</h3>
                            <small class="text-body-secondary">{{ __('messages.available_advance') }}</small>
                        </div>
                        <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success fs-3">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Table Card --}}
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold text-body">{{ __('messages.customer_list') }}</h4>
                    <small class="text-muted fw-semibold">
                        {{ $stats['total'] }} {{ __('messages.customers') }} &bull; <span class="text-success">{{ $stats['active'] }} {{ __('messages.active') }}</span>
                    </small>
                </div>
                <div>
                    @can('customers.create')
                        <a href="{{ route('customers.create') }}" class="btn btn-sm btn-primary px-3 py-2 fw-semibold shadow-sm" style="border-radius: 8px;">
                            <i class="bi bi-person-plus-fill me-1"></i> {{ __('messages.add_customer') }}
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card-body px-4 pb-4 pt-3">
                <div class="dt-scope">
                    {{-- Filters --}}
                    <div id="customersFilters" class="dt-filter-card mb-3 p-3 rounded-3 border">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-2">
                                <label class="form-label fw-semibold small mb-1" for="customerBranch">{{ __('messages.table_branch') }}</label>
                                <select id="customerBranch" class="form-select form-select-sm dt-filter" data-filter-key="branch_id">
                                    <option value="">{{ __('messages.all_branches') }}</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label fw-semibold small mb-1" for="customerTypeFilter">{{ __('messages.customer_type') }}</label>
                                <select id="customerTypeFilter" class="form-select form-select-sm dt-filter" data-filter-key="customer_type">
                                    <option value="">{{ __('messages.all_customer_types') }}</option>
                                    @foreach ($customerTypes as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label fw-semibold small mb-1" for="customerActive">{{ __('messages.table_status') }}</label>
                                <select id="customerActive" class="form-select form-select-sm dt-filter" data-filter-key="is_active">
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

                    {{-- Custom DataTable --}}
                    <table
                        id="dt-customers"
                        class="table table-hover align-middle w-100 js-yajra-dt"
                        data-dt-key="customers"
                        data-dt-responsive-control="true"
                        data-dt-filter-scope="#customersFilters"
                        data-page-length="25"
                        data-dt-order='[[1,"asc"]]'
                        data-dt-html-columns='["customer_display","nid_city_display","due_display","advance_display","due_limit_display","branch_display","status_display","actions"]'
                        data-dt-columns='[
                            {"data":"DT_RowIndex","name":"DT_RowIndex","title":"{{ __('messages.si') }}","orderable":false,"searchable":false,"responsivePriority":1},
                            {"data":"customer_display","name":"name","title":"{{ __('messages.customer_name') }}","responsivePriority":2},
                            {"data":"nid_city_display","name":"nid","title":"{{ __('messages.nid_city') }}","responsivePriority":50},
                            {"data":"due_display","name":"opening_balance","title":"{{ __('messages.outstanding_due_label') }}","responsivePriority":30},
                            {"data":"advance_display","name":"advance_balance","title":"{{ __('messages.advance_balance_label') }}","responsivePriority":40},
                            {"data":"due_limit_display","name":"due_limit","title":"{{ __('messages.due_limit_label') }}","responsivePriority":70},
                            {"data":"branch_display","name":"branch_id","title":"{{ __('messages.table_branch') }}","responsivePriority":60},
                            {"data":"status_display","name":"is_active","title":"{{ __('messages.table_status') }}","responsivePriority":80},
                            {"data":"actions","name":"actions","title":"{{ __('messages.table_actions') }}","orderable":false,"searchable":false,"responsivePriority":1,"className":"text-nowrap text-end"}
                        ]'>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Collect Due Modal --}}
<div class="modal fade" id="collectDueModal" tabindex="-1" aria-labelledby="collectDueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" id="collectDueForm" method="POST" action="">
            @csrf
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark" id="collectDueModalLabel">
                    <i class="bi bi-cash-stack text-success me-2"></i>{{ __('messages.collect_due') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Customer Info Callout --}}
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">{{ __('messages.customer_name') }}:</span>
                        <span class="fw-bold text-dark fs-6" id="modalCustomerName">—</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">{{ __('messages.customer_phone') }}:</span>
                        <span class="text-dark fw-semibold" id="modalCustomerPhone">—</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="fw-bold text-danger">{{ __('messages.outstanding_due_label') }}:</span>
                        <span class="fw-bold text-danger fs-5" id="modalCustomerDue">৳ 0.00</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="collectAmountInput" class="form-label fw-semibold">
                        {{ __('messages.collection_amount') }} <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">৳</span>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               id="collectAmountInput"
                               name="amount"
                               class="form-control form-control-lg fw-bold text-success"
                               required
                               placeholder="0.00">
                        <button type="button" class="btn btn-outline-secondary" id="fillFullDueBtn">
                            {{ __('messages.pay_full_due') }}
                        </button>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="collectMethodSelect" class="form-label fw-semibold">
                            {{ __('messages.payment_method') }} <span class="text-danger">*</span>
                        </label>
                        <select id="collectMethodSelect" name="method" class="form-select" required>
                            <option value="cash" selected>{{ __('messages.method_cash') }}</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                            <option value="bank">{{ __('messages.method_bank') }}</option>
                            <option value="card">{{ __('messages.method_card') }}</option>
                            <option value="other">{{ __('messages.method_other') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="collectRefInput" class="form-label fw-semibold">
                            {{ __('messages.reference_no') }}
                        </label>
                        <input type="text" id="collectRefInput" name="reference_no" class="form-control" placeholder="TrxID / Cheque #">
                    </div>
                </div>

                <div class="mb-2">
                    <label for="collectNoteInput" class="form-label fw-semibold">
                        {{ __('messages.notes_label') }}
                    </label>
                    <input type="text" id="collectNoteInput" name="note" class="form-control" placeholder="e.g. Monthly settlement">
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold text-white">
                    <i class="bi bi-check2-circle me-1"></i> {{ __('messages.confirm_collection') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    let currentCustomerDue = 0;

    // Open Collect Due Modal from list button
    $(document).on('click', '.js-collect-due-btn', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const phone = $(this).data('phone');
        const due = parseFloat($(this).data('due')) || 0;

        currentCustomerDue = due;

        $('#modalCustomerName').text(name);
        $('#modalCustomerPhone').text(phone || '—');
        $('#modalCustomerDue').text('৳ ' + due.toFixed(2));
        $('#collectAmountInput').val(due > 0 ? due.toFixed(2) : '');
        $('#collectDueForm').attr('action', '{{ url('/dashboard/customers') }}/' + id + '/collect-due');

        const modal = new bootstrap.Modal(document.getElementById('collectDueModal'));
        modal.show();
    });

    // Fill Full Due button
    $('#fillFullDueBtn').on('click', function () {
        if (currentCustomerDue > 0) {
            $('#collectAmountInput').val(currentCustomerDue.toFixed(2));
        }
    });
});
</script>
@endpush
