@extends('admin.layouts.dashboard')

@section('title', __('messages.add_new_user') . ' | ' . config('app.name', 'Micro POS'))

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

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="text-decoration-none">{{ __('messages.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('users.index') }}" class="text-decoration-none">{{ __('messages.users') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('messages.create') }}</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0 text-body">{{ __('messages.add_new_user') }}</h4>
            </div>
            <div>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold shadow-sm" style="border-radius: 8px;">
                    <i class="bi bi-arrow-left me-1"></i> {{ __('messages.back_to_list') }}
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-bold text-body">
                    <i class="bi bi-person-badge-fill text-primary me-2"></i>{{ __('messages.user_details') }}
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        {{-- Name --}}
                        <div class="col-12 col-md-6">
                            <label for="userNameInput" class="form-label fw-semibold">
                                {{ __('messages.user_name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="userNameInput"
                                   name="name"
                                   value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   required
                                   placeholder="e.g. John Doe">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="col-12 col-md-6">
                            <label for="userEmailInput" class="form-label fw-semibold">
                                {{ __('messages.user_email') }} <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   id="userEmailInput"
                                   name="email"
                                   value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   required
                                   placeholder="name@example.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div class="col-12 col-md-6">
                            <label for="userPhoneInput" class="form-label fw-semibold">
                                {{ __('messages.user_phone') }}
                            </label>
                            <input type="text"
                                   id="userPhoneInput"
                                   name="phone"
                                   value="{{ old('phone') }}"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="01700000000">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="col-12 col-md-6">
                            <label for="userRoleSelect" class="form-label fw-semibold">
                                {{ __('messages.user_role') }} <span class="text-danger">*</span>
                            </label>
                            <select id="userRoleSelect"
                                    name="type"
                                    class="form-select @error('type') is-invalid @enderror"
                                    required>
                                <option value="">{{ __('messages.select_role_placeholder') }}</option>
                                @foreach ($roles as $roleKey => $roleData)
                                    <option value="{{ $roleKey }}" {{ old('type') === $roleKey ? 'selected' : '' }}>
                                        {{ $roleData['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Branch --}}
                        <div class="col-12 col-md-6">
                            <label for="userBranchSelect" class="form-label fw-semibold">
                                {{ __('messages.user_branch') }}
                            </label>
                            <select id="userBranchSelect"
                                    name="branch_id"
                                    class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">{{ __('messages.select_branch_placeholder') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} {{ $branch->is_head_office ? '(' . __('messages.head_office') . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">{{ __('messages.all_branches_default') }} if unselected.</small>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Address --}}
                        <div class="col-12 col-md-6">
                            <label for="userAddressInput" class="form-label fw-semibold">
                                {{ __('messages.user_address') }}
                            </label>
                            <input type="text"
                                   id="userAddressInput"
                                   name="address"
                                   value="{{ old('address') }}"
                                   class="form-control @error('address') is-invalid @enderror"
                                   placeholder="House, Street, Area, City">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="col-12 col-md-6">
                            <label for="userPasswordInput" class="form-label fw-semibold">
                                {{ __('messages.password') }} <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   id="userPasswordInput"
                                   name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required
                                   autocomplete="new-password">
                            <small class="text-muted">{{ __('messages.password_help_create') }}</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password Confirmation --}}
                        <div class="col-12 col-md-6">
                            <label for="userPasswordConfirmInput" class="form-label fw-semibold">
                                {{ __('messages.password_confirmation') }} <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   id="userPasswordConfirmInput"
                                   name="password_confirmation"
                                   class="form-control"
                                   required
                                   autocomplete="new-password">
                        </div>

                        {{-- Status Switch --}}
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="userActiveSwitch"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="userActiveSwitch">
                                    {{ __('messages.active') }} ({{ __('messages.user_status') }})
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary px-4 fw-semibold" style="border-radius: 8px;">
                            {{ __('messages.cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm" style="border-radius: 8px;">
                            <i class="bi bi-check2-circle me-1"></i> {{ __('messages.create_user') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
