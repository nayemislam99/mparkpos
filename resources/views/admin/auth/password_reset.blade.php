@extends('admin.auth.auth')

@section('content')
<div class="bg-dark min-vh-100 d-flex align-items-center py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6 col-xl-5">
                <div class="card shadow-lg border-0 overflow-hidden">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="fw-bold mb-2">Reset Password</h2>
                        <p class="text-body-secondary mb-4">
                             Set a new password for your account.
                        </p>

                        {{-- Email (read-only, not "inside" password input area) --}}
                        <div class="mb-3">
                            <label class="form-label small text-body-secondary mb-1">Account Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <svg class="icon">
                                        <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg#cil-envelope-open') }}"></use>
                                    </svg>
                                </span>
                                <input type="text"
                                       class="form-control bg-body-tertiary"
                                       value="{{ $email }}"
                                       readonly />
                            </div>
                        </div>

                        <form method="POST" action="{{ route('forgot.password.reset.submit') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="email" value="{{ $email }}">

                            {{-- New Password --}}
                            <div class="mb-3">
                                <label class="form-label small text-body-secondary mb-1">New Password</label>
                                <div class="input-group auth-input">
                                    <span class="input-group-text bg-light">
                                        <svg class="icon">
                                            <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg#cil-lock-locked') }}"></use>
                                        </svg>
                                    </span>
                                    <input type="password"
                                           name="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Enter new password" />
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text small">
                                    Use at least 8 characters (recommended).
                                </div>
                            </div>

                            {{-- Confirm Password --}}
                            <div class="mb-4">
                                <label class="form-label small text-body-secondary mb-1">Confirm Password</label>
                                <div class="input-group auth-input">
                                    <span class="input-group-text bg-light">
                                        <svg class="icon">
                                            <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg#cil-lock-locked') }}"></use>
                                        </svg>
                                    </span>
                                    <input type="password"
                                           name="password_confirmation"
                                           class="form-control @error('password_confirmation') is-invalid @enderror"
                                           placeholder="Re-type new password" />
                                    @error('password_confirmation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <button class="btn btn-primary w-100 fw-semibold py-2">
                                Reset Password
                            </button>

                            <div class="text-center mt-3">
                                <a href="{{ route('auth.login') }}" class="small text-decoration-none">
                                    Back to login
                                </a>
                            </div>
                        </form>
                    </div>
            </div>
        </div>
    </div>
</div>
@endsection