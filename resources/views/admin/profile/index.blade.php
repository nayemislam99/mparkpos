@extends('admin.layouts.dashboard')

@section('title', __('messages.profile') . ' | ' . config('app.name', 'Micro POS'))

@php
    $isPasswordTab = ($activeTab ?? 'profile') === 'password' || $errors->has('current_password') || $errors->has('new_password');
@endphp

@section('content')
<div class="row">
    <div class="col-12 px-4">
        {{-- Breadcrumb & Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="text-decoration-none">{{ __('messages.dashboard') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('messages.profile') }}</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0 text-body">
                    <i class="bi bi-person-badge text-danger me-2"></i>{{ __('messages.profile') }} &amp; {{ __('messages.security') }}
                </h4>
            </div>
            <div>
                <form action="{{ route('profile.clear-cache') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm px-3 fw-semibold shadow-sm" style="border-radius: 8px;">
                        <i class="bi bi-arrow-repeat me-1"></i> {{ __('messages.clear_cache') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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

        <div class="row g-4 mb-4">
            {{-- Left Column: User Summary Card --}}
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                    <div class="card-body text-center p-4">
                        <div class="position-relative d-inline-block mb-3">
                            <img src="{{ asset('admin/assets/img/avatars/8.jpg') }}"
                                 alt="{{ $user->name }}"
                                 class="rounded-circle img-thumbnail shadow-sm"
                                 style="width: 100px; height: 100px; object-fit: cover;">
                            <span class="position-absolute bottom-0 end-0 p-2 bg-success border border-light rounded-circle">
                                <span class="visually-hidden">Online</span>
                            </span>
                        </div>
                        <h5 class="fw-bold text-body mb-1">{{ $user->name }}</h5>
                        <p class="text-body-secondary small mb-2">{{ $user->email }}</p>
                        <div>
                            <span class="badge bg-danger text-white px-3 py-1 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                                {{ config("access.roles." . $user->type . ".label") ?? ucfirst(str_replace('_', ' ', $user->type)) }}
                            </span>
                        </div>

                        <hr class="my-3 text-secondary opacity-25">

                        <div class="text-start small">
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-body-secondary"><i class="bi bi-building me-1"></i> {{ __('messages.company') }}:</span>
                                <span class="fw-semibold text-body">{{ $user->company->name ?? 'Micro IT Park POS' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-body-secondary"><i class="bi bi-geo-alt me-1"></i> {{ __('messages.assigned_branch') }}:</span>
                                <span class="fw-semibold text-body">{{ $user->branch->name ?? __('messages.company_wide') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-body-secondary"><i class="bi bi-calendar3 me-1"></i> {{ __('messages.joined_date') }}:</span>
                                <span class="fw-semibold text-body">{{ $user->created_at ? $user->created_at->format('d M, Y') : 'N/A' }}</span>
                            </div>
                        </div>

                        <hr class="my-3 text-secondary opacity-25">

                        {{-- Nav pills --}}
                        <div class="nav flex-column nav-pills gap-2" id="profile-tabs" role="tablist">
                            <button class="nav-link text-start d-flex align-items-center py-2 px-3 fw-semibold {{ ! $isPasswordTab ? 'active' : '' }}"
                                    id="profile-info-tab"
                                    data-coreui-toggle="pill"
                                    data-coreui-target="#tab-profile-info"
                                    type="button"
                                    role="tab"
                                    aria-controls="tab-profile-info"
                                    aria-selected="{{ ! $isPasswordTab ? 'true' : 'false' }}"
                                    style="border-radius: 8px;">
                                <i class="bi bi-person me-2 fs-5"></i>
                                <span>{{ __('messages.profile_information') }}</span>
                            </button>
                            <button class="nav-link text-start d-flex align-items-center py-2 px-3 fw-semibold {{ $isPasswordTab ? 'active' : '' }}"
                                    id="change-password-tab"
                                    data-coreui-toggle="pill"
                                    data-coreui-target="#tab-change-password"
                                    type="button"
                                    role="tab"
                                    aria-controls="tab-change-password"
                                    aria-selected="{{ $isPasswordTab ? 'true' : 'false' }}"
                                    style="border-radius: 8px;">
                                <i class="bi bi-key me-2 fs-5"></i>
                                <span>{{ __('messages.change_password') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Tab Panels --}}
            <div class="col-12 col-lg-8">
                <div class="tab-content" id="profile-tabs-content">
                    {{-- Tab 1: Profile Information --}}
                    <div class="tab-pane fade {{ ! $isPasswordTab ? 'show active' : '' }}"
                         id="tab-profile-info"
                         role="tabpanel"
                         aria-labelledby="profile-info-tab"
                         tabindex="0">
                        <div class="card shadow-sm border-0" style="border-radius: 12px;">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h6 class="mb-0 fw-bold text-body">
                                    <i class="bi bi-person-fill text-primary me-2"></i>{{ __('messages.profile_information') }}
                                </h6>
                                <small class="text-body-secondary">{{ __('messages.profile_description') }}</small>
                            </div>
                            <div class="card-body p-4">
                                <form action="{{ route('profile.update') }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label for="profile_name" class="form-label fw-semibold">
                                                {{ __('messages.user_name') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   name="name"
                                                   id="profile_name"
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   value="{{ old('name', $user->name) }}"
                                                   required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label for="profile_email" class="form-label fw-semibold">
                                                {{ __('messages.user_email') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="email"
                                                   name="email"
                                                   id="profile_email"
                                                   class="form-control @error('email') is-invalid @enderror"
                                                   value="{{ old('email', $user->email) }}"
                                                   required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label for="profile_phone" class="form-label fw-semibold">
                                                {{ __('messages.user_phone') }}
                                            </label>
                                            <input type="text"
                                                   name="phone"
                                                   id="profile_phone"
                                                   class="form-control @error('phone') is-invalid @enderror"
                                                   value="{{ old('phone', $user->phone) }}"
                                                   placeholder="01XXXXXXXXX">
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label for="profile_address" class="form-label fw-semibold">
                                                {{ __('messages.user_address') }}
                                            </label>
                                            <input type="text"
                                                   name="address"
                                                   id="profile_address"
                                                   class="form-control @error('address') is-invalid @enderror"
                                                   value="{{ old('address', $user->address) }}"
                                                   placeholder="City, Area">
                                            @error('address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 pt-3">
                                            <button type="submit" class="btn btn-primary px-4 fw-semibold" style="border-radius: 8px;">
                                                <i class="bi bi-check2 me-1"></i> {{ __('messages.save_changes') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Change Password --}}
                    <div class="tab-pane fade {{ $isPasswordTab ? 'show active' : '' }}"
                         id="tab-change-password"
                         role="tabpanel"
                         aria-labelledby="change-password-tab"
                         tabindex="0">
                        <div class="card shadow-sm border-0" style="border-radius: 12px;">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h6 class="mb-0 fw-bold text-body">
                                    <i class="bi bi-shield-lock-fill text-warning me-2"></i>{{ __('messages.change_password') }}
                                </h6>
                                <small class="text-body-secondary">{{ __('messages.change_password_description') }}</small>
                            </div>
                            <div class="card-body p-4">
                                <form action="{{ route('profile.password.update') }}" method="POST">
                                    @csrf

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="current_password" class="form-label fw-semibold">
                                                {{ __('messages.current_password') }} <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="password"
                                                       name="current_password"
                                                       id="current_password"
                                                       class="form-control @error('current_password') is-invalid @enderror"
                                                       required>
                                                <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="#current_password">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                @error('current_password')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label for="new_password" class="form-label fw-semibold">
                                                {{ __('messages.new_password') }} <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="password"
                                                       name="new_password"
                                                       id="new_password"
                                                       class="form-control @error('new_password') is-invalid @enderror"
                                                       required>
                                                <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="#new_password">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                @error('new_password')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <small class="text-body-secondary">{{ __('messages.password_help_create') ?? 'Minimum 6 characters' }}</small>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label for="new_password_confirmation" class="form-label fw-semibold">
                                                {{ __('messages.confirm_new_password') }} <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="password"
                                                       name="new_password_confirmation"
                                                       id="new_password_confirmation"
                                                       class="form-control"
                                                       required>
                                                <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="#new_password_confirmation">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-12 pt-3">
                                            <button type="submit" class="btn btn-warning text-dark px-4 fw-semibold" style="border-radius: 8px;">
                                                <i class="bi bi-key-fill me-1"></i> {{ __('messages.update_password') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tab switching handler
        var pills = document.querySelectorAll('#profile-tabs button[data-coreui-toggle="pill"]');
        pills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                pills.forEach(function (p) {
                    p.classList.remove('active');
                    p.setAttribute('aria-selected', 'false');
                });
                pill.classList.add('active');
                pill.setAttribute('aria-selected', 'true');

                var targetSelector = pill.getAttribute('data-coreui-target');
                document.querySelectorAll('#profile-tabs-content .tab-pane').forEach(function (pane) {
                    pane.classList.remove('show', 'active');
                });
                var targetPane = document.querySelector(targetSelector);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                }
            });
        });

        // Password visibility toggler
        document.querySelectorAll('.toggle-password-visibility').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetSelector = this.getAttribute('data-target');
                var input = document.querySelector(targetSelector);
                if (!input) return;

                var icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                }
            });
        });
    });
</script>
@endpush
@endsection
