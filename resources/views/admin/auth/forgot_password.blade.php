@extends('admin.auth.auth')

@section('content')
<div class="bg-dark min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-2">Forgot Password</h2>
                        <p class="text-body-secondary mb-4">
                            Enter your email to receive reset link
                        </p>

                        <form method="POST" action="{{ route('forgot.password.submit') }}">
                            @csrf

                            <div class="input-group mb-3 auth-input">
                                <span class="input-group-text">
                                    <svg class="icon">
                                        <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg#cil-envelope-open') }}"></use>
                                    </svg>
                                </span>
                                <input type="email"
                                       name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="Email address" />
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button class="btn btn-primary w-100">
                                Send Reset Link
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="{{ route('auth.login') }}" class="small">
                                ← Back to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
