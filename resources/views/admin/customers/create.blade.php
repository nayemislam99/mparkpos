@extends('admin.layouts.dashboard')

@section('title', __('messages.add_customer') . ' | ' . config('app.name', 'Micro POS'))

@push('css')
<style>
  .customer-card-section {
    border-radius: 12px;
    border: 1px solid var(--cui-border-color, #e2e8f0);
    background: var(--cui-card-bg, #ffffff);
    margin-bottom: 1.25rem;
    overflow: hidden;
  }
  .customer-card-header {
    background: var(--cui-tertiary-bg, #f8fafc);
    border-bottom: 1px solid var(--cui-border-color, #e2e8f0);
    padding: 0.85rem 1.25rem;
  }
  [data-coreui-theme="dark"] .customer-card-section {
    background: #1e222d !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
  }
  [data-coreui-theme="dark"] .customer-card-header {
    background: #161922 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
  }
  .balance-card-due {
    border-left: 4px solid #dc3545 !important;
  }
  .balance-card-advance {
    border-left: 4px solid #198754 !important;
  }
  .balance-card-limit {
    border-left: 4px solid #0d6efd !important;
  }
  .input-group-text {
    background: var(--cui-tertiary-bg, #f8fafc);
    border-color: var(--cui-border-color, #dee2e6);
    color: var(--cui-body-color);
  }
  [data-coreui-theme="dark"] .input-group-text {
    background: #161922 !important;
    border-color: rgba(255, 255, 255, 0.18) !important;
    color: #f1f5f9 !important;
  }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12 px-4">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>{{ __('messages.fix_errors') ?? 'Please check the form for errors.' }}</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Header Breadcrumb & Actions --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="text-decoration-none text-body-secondary">{{ __('messages.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}" class="text-decoration-none text-body-secondary">{{ __('messages.customers') }}</a></li>
                        <li class="breadcrumb-item active text-body" aria-current="page">{{ __('messages.create') }}</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0 text-body">{{ __('messages.add_customer') }}</h4>
            </div>
            <div>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold shadow-sm" style="border-radius: 8px;">
                    <i class="bi bi-arrow-left me-1"></i> {{ __('messages.back_to_list') }}
                </a>
            </div>
        </div>

        <form action="{{ route('customers.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                {{-- Left Column: Basic Info & Balances --}}
                <div class="col-12 col-lg-7">
                    {{-- 1. Primary Contact Information --}}
                    <div class="customer-card-section shadow-sm">
                        <div class="customer-card-header d-flex align-items-center">
                            <span class="p-1.5 rounded-2 bg-primary bg-opacity-10 text-primary me-2">
                                <i class="bi bi-person-vcard fs-5"></i>
                            </span>
                            <h6 class="mb-0 fw-bold text-body">{{ __('messages.customer_details') }}</h6>
                        </div>
                        <div class="p-3 p-md-4">
                            <div class="row g-3">
                                {{-- Name --}}
                                <div class="col-12 col-md-6">
                                    <label for="customerNameInput" class="form-label fw-semibold small text-body">
                                        {{ __('messages.customer_name') }} <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text"
                                               id="customerNameInput"
                                               name="name"
                                               value="{{ old('name') }}"
                                               class="form-control @error('name') is-invalid @enderror"
                                               required
                                               placeholder="e.g. Moon Ahmed">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Phone --}}
                                <div class="col-12 col-md-6">
                                    <label for="customerPhoneInput" class="form-label fw-semibold small text-body">
                                        {{ __('messages.customer_phone') }} <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                        <input type="text"
                                               id="customerPhoneInput"
                                               name="phone"
                                               value="{{ old('phone') }}"
                                               class="form-control @error('phone') is-invalid @enderror"
                                               required
                                               placeholder="01800000000">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Email --}}
                                <div class="col-12 col-md-6">
                                    <label for="customerEmailInput" class="form-label fw-semibold small text-body">
                                        {{ __('messages.customer_email') }}
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email"
                                               id="customerEmailInput"
                                               name="email"
                                               value="{{ old('email') }}"
                                               class="form-control @error('email') is-invalid @enderror"
                                               placeholder="customer@example.com">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- NID --}}
                                <div class="col-12 col-md-6">
                                    <label for="customerNidInput" class="form-label fw-semibold small text-body">
                                        {{ __('messages.nid_label') }}
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                        <input type="text"
                                               id="customerNidInput"
                                               name="nid"
                                               value="{{ old('nid') }}"
                                               class="form-control @error('nid') is-invalid @enderror"
                                               placeholder="NID Number">
                                        @error('nid')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Financial Balances & Credit --}}
                    <div class="customer-card-section shadow-sm">
                        <div class="customer-card-header d-flex align-items-center">
                            <span class="p-1.5 rounded-2 bg-success bg-opacity-10 text-success me-2">
                                <i class="bi bi-cash-coin fs-5"></i>
                            </span>
                            <h6 class="mb-0 fw-bold text-body">{{ __('messages.balance') }} &amp; {{ __('messages.due_limit_label') }}</h6>
                        </div>
                        <div class="p-3 p-md-4">
                            <div class="row g-3">
                                {{-- Opening Due Balance --}}
                                <div class="col-12 col-md-4">
                                    <div class="p-2.5 rounded-3 border balance-card-due bg-body">
                                        <label for="customerOpeningBalanceInput" class="form-label fw-semibold small text-danger mb-1">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ __('messages.opening_balance_label') }} (৳)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text fw-bold text-danger">৳</span>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   id="customerOpeningBalanceInput"
                                                   name="opening_balance"
                                                   value="{{ old('opening_balance', '0.00') }}"
                                                   class="form-control fw-bold text-danger @error('opening_balance') is-invalid @enderror"
                                                   placeholder="0.00">
                                        </div>
                                        <small class="text-body-secondary d-block mt-1" style="font-size: 0.72rem;">{{ __('messages.opening_balance_help') }}</small>
                                    </div>
                                </div>

                                {{-- Advance Balance --}}
                                <div class="col-12 col-md-4">
                                    <div class="p-2.5 rounded-3 border balance-card-advance bg-body">
                                        <label for="customerAdvanceBalanceInput" class="form-label fw-semibold small text-success mb-1">
                                            <i class="bi bi-wallet2 me-1"></i>{{ __('messages.advance_balance_label') }} (৳)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text fw-bold text-success">৳</span>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   id="customerAdvanceBalanceInput"
                                                   name="advance_balance"
                                                   value="{{ old('advance_balance', '0.00') }}"
                                                   class="form-control fw-bold text-success @error('advance_balance') is-invalid @enderror"
                                                   placeholder="0.00">
                                        </div>
                                        <small class="text-body-secondary d-block mt-1" style="font-size: 0.72rem;">{{ __('messages.advance_balance_help') }}</small>
                                    </div>
                                </div>

                                {{-- Due Limit --}}
                                <div class="col-12 col-md-4">
                                    <div class="p-2.5 rounded-3 border balance-card-limit bg-body">
                                        <label for="customerDueLimitInput" class="form-label fw-semibold small text-primary mb-1">
                                            <i class="bi bi-shield-check me-1"></i>{{ __('messages.due_limit_label') }} (৳)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text fw-bold text-primary">৳</span>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   id="customerDueLimitInput"
                                                   name="due_limit"
                                                   value="{{ old('due_limit', '0.00') }}"
                                                   class="form-control fw-bold text-body @error('due_limit') is-invalid @enderror"
                                                   placeholder="0.00">
                                        </div>
                                        <small class="text-body-secondary d-block mt-1" style="font-size: 0.72rem;">{{ __('messages.due_limit_help') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Classification, Address & Status --}}
                <div class="col-12 col-lg-5">
                    {{-- 3. Customer Classification & Branch --}}
                    <div class="customer-card-section shadow-sm">
                        <div class="customer-card-header d-flex align-items-center">
                            <span class="p-1.5 rounded-2 bg-info bg-opacity-10 text-info me-2">
                                <i class="bi bi-sliders fs-5"></i>
                            </span>
                            <h6 class="mb-0 fw-bold text-body">{{ __('messages.customer_type') }} &amp; {{ __('messages.branch') }}</h6>
                        </div>
                        <div class="p-3 p-md-4">
                            <div class="row g-3">
                                {{-- Customer Type --}}
                                <div class="col-12 col-md-6">
                                    <label for="customerTypeSelect" class="form-label fw-semibold small text-body">
                                        {{ __('messages.customer_type') }}
                                    </label>
                                    <select id="customerTypeSelect"
                                            name="customer_type"
                                            class="form-select @error('customer_type') is-invalid @enderror">
                                        @foreach ($customerTypes as $typeKey => $typeLabel)
                                            <option value="{{ $typeKey }}" {{ old('customer_type', 'regular') === $typeKey ? 'selected' : '' }}>
                                                {{ $typeLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Customer Group --}}
                                <div class="col-12 col-md-6">
                                    <label for="customerGroupSelect" class="form-label fw-semibold small text-body">
                                        {{ __('messages.customer_group') }} <span class="badge bg-secondary-subtle text-body-secondary fw-normal">Optional</span>
                                    </label>
                                    <select id="customerGroupSelect"
                                            name="customer_group"
                                            class="form-select @error('customer_group') is-invalid @enderror">
                                        @foreach ($customerGroups as $groupKey => $groupLabel)
                                            <option value="{{ $groupKey }}" {{ old('customer_group', 'retail') === $groupKey ? 'selected' : '' }}>
                                                {{ $groupLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Branch --}}
                                <div class="col-12">
                                    <label for="customerBranchSelect" class="form-label fw-semibold small text-body">
                                        {{ __('messages.table_branch') }}
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shop"></i></span>
                                        <select id="customerBranchSelect"
                                                name="branch_id"
                                                class="form-select @error('branch_id') is-invalid @enderror">
                                            <option value="">{{ __('messages.all_branches_default') }}</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                                    {{ $branch->name }} {{ $branch->is_head_office ? '(' . __('messages.head_office') . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Address Details --}}
                    <div class="customer-card-section shadow-sm">
                        <div class="customer-card-header d-flex align-items-center">
                            <span class="p-1.5 rounded-2 bg-secondary bg-opacity-10 text-secondary me-2">
                                <i class="bi bi-geo-alt fs-5"></i>
                            </span>
                            <h6 class="mb-0 fw-bold text-body">{{ __('messages.customer_address') }}</h6>
                        </div>
                        <div class="p-3 p-md-4">
                            <div class="row g-3">
                                {{-- Address Line 1 --}}
                                <div class="col-12">
                                    <label for="customerAddressInput" class="form-label fw-semibold small text-body">
                                        {{ __('messages.address_line_1') }}
                                    </label>
                                    <input type="text"
                                           id="customerAddressInput"
                                           name="address"
                                           value="{{ old('address') }}"
                                           class="form-control @error('address') is-invalid @enderror"
                                           placeholder="House, Road, Area">
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Address Line 2 --}}
                                <div class="col-12 col-md-6">
                                    <label for="customerAddress2Input" class="form-label fw-semibold small text-body">
                                        {{ __('messages.address_line_2') }}
                                    </label>
                                    <input type="text"
                                           id="customerAddress2Input"
                                           name="address_line_2"
                                           value="{{ old('address_line_2') }}"
                                           class="form-control @error('address_line_2') is-invalid @enderror"
                                           placeholder="Apt, Floor (Optional)">
                                </div>

                                {{-- City --}}
                                <div class="col-12 col-md-6">
                                    <label for="customerCityInput" class="form-label fw-semibold small text-body">
                                        {{ __('messages.city') }}
                                    </label>
                                    <input type="text"
                                           id="customerCityInput"
                                           name="city"
                                           value="{{ old('city') }}"
                                           class="form-control @error('city') is-invalid @enderror"
                                           placeholder="e.g. Dhaka">
                                </div>

                                {{-- Notes --}}
                                <div class="col-12">
                                    <label for="customerNotesInput" class="form-label fw-semibold small text-body">
                                        {{ __('messages.notes_label') }}
                                    </label>
                                    <input type="text"
                                           id="customerNotesInput"
                                           name="notes"
                                           value="{{ old('notes') }}"
                                           class="form-control @error('notes') is-invalid @enderror"
                                           placeholder="Additional customer details / remarks">
                                </div>

                                {{-- Active Switch --}}
                                <div class="col-12 pt-2 border-top">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="customerActiveSwitch"
                                               name="is_active"
                                               value="1"
                                               {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold text-body" for="customerActiveSwitch">
                                            {{ __('messages.active') }} ({{ __('messages.customer_status') }})
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="col-12 d-flex align-items-center justify-content-end gap-2 pt-2 pb-4">
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary px-4 fw-semibold" style="border-radius: 8px;">
                        {{ __('messages.cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm" style="border-radius: 8px;">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('messages.save_customer') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
