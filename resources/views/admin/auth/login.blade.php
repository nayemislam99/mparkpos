<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#191715">
    <title>Sign in | {{ config('app.name', 'Micro POS') }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172235;
            background: #f8fafc;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }

        * { box-sizing: border-box; }

        body {
            min-width: 320px;
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 32px;
            background:
                radial-gradient(ellipse at 18% 12%, rgba(210, 1, 1, .09), transparent 34%),
                #f8fafc;
        }

        .login-shell {
            width: min(100%, 1040px);
            min-height: 620px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(25, 23, 21, .14);
        }

        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: 48px;
            color: #fff;
            background:
                radial-gradient(circle at 88% 18%, rgba(210, 1, 1, .35), transparent 30%),
                linear-gradient(145deg, #1f1b18, #191715 72%);
        }

        .brand-panel::after {
            position: absolute;
            right: -130px;
            bottom: -180px;
            width: 400px;
            height: 400px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 50%;
            box-shadow: 0 0 0 42px rgba(210, 1, 1, .04), 0 0 0 84px rgba(210, 1, 1, .02);
            content: "";
            pointer-events: none;
        }

        .brand, .brand-copy, .brand-footer { position: relative; z-index: 1; }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 19px;
            font-weight: 750;
            letter-spacing: -.04em;
        }

        .brand img {
            max-height: 44px;
            width: auto;
            object-fit: contain;
        }

        .brand-copy { max-width: 390px; margin: auto 0; padding: 72px 0; }
        .eyebrow { color: #f87171; font-size: 12px; font-weight: 750; letter-spacing: .16em; text-transform: uppercase; }
        .brand-copy h1 { margin: 18px 0 16px; font-size: clamp(36px, 4vw, 52px); line-height: 1.08; letter-spacing: -.055em; }
        .brand-copy p { max-width: 360px; margin: 0; color: #cbd5e1; font-size: 15px; line-height: 1.8; }
        .brand-footer { color: #94a3b8; font-size: 12px; }

        .form-panel { display: flex; align-items: center; justify-content: center; padding: 56px; }
        .form-content { width: min(100%, 360px); }
        .form-content h2 { margin: 0; font-size: 30px; letter-spacing: -.045em; }
        .intro { margin: 10px 0 32px; color: #64748b; font-size: 14px; line-height: 1.6; }
        .field { margin-bottom: 20px; }
        .field label { display: block; margin-bottom: 8px; color: #334155; font-size: 13px; font-weight: 650; }
        .field input {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            background: #fff;
            color: #191715;
            font: inherit;
            font-size: 14px;
            transition: border-color .15s, box-shadow .15s;
        }
        .field input:focus { border-color: #d20101; box-shadow: 0 0 0 3px rgba(210, 1, 1, .16); }
        .field input::placeholder { color: #94a3b8; }
        .field input[aria-invalid="true"] { border-color: #dc2626; }
        .field-error { margin: 7px 0 0; color: #dc2626; font-size: 12px; }
        .form-options { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 4px 0 24px; }
        .remember { display: inline-flex; align-items: center; gap: 8px; color: #64748b; font-size: 13px; }
        .remember input { accent-color: #d20101; }
        a { color: #d20101; font-size: 13px; font-weight: 650; text-decoration: none; }
        a:hover { text-decoration: underline; color: #b50000; }
        .submit {
            width: 100%;
            height: 49px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: #d20101;
            box-shadow: 0 7px 16px rgba(210, 1, 1, .25);
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            transition: background .15s, transform .15s;
        }
        .submit:hover { transform: translateY(-1px); background: #b50000; }
        .demo-note { margin-top: 24px; padding: 13px 14px; border: 1px solid #fee2e2; border-radius: 10px; color: #7f1d1d; background: #fef2f2; font-size: 12px; line-height: 1.7; }
        .demo-note strong { color: #991b1b; }
        .security-note { margin: 24px 0 0; color: #94a3b8; font-size: 11px; text-align: center; }

        @media (max-width: 760px) {
            body { padding: 16px; }
            .login-shell { min-height: auto; max-width: 480px; grid-template-columns: 1fr; border-radius: 20px; }
            .brand-panel { min-height: 190px; padding: 26px 28px; }
            .brand-copy { padding: 30px 0 0; }
            .brand-copy h1 { margin: 10px 0 8px; font-size: 29px; }
            .brand-copy p, .brand-footer { display: none; }
            .eyebrow { font-size: 10px; }
            .form-panel { padding: 34px 28px 30px; }
            .intro { margin-bottom: 24px; }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="brand-panel" aria-label="Micro POS">
            <div class="brand">
                <img src="{{ asset('admin/assets/img/mpos.png') }}" alt="{{ config('app.name', 'Micro POS') }}">
            </div>
            <div class="brand-copy">
                <span class="eyebrow">Point of sale</span>
                <h1>Your store,<br>in sync.</h1>
                <p>A simpler workspace for your sales, products, and day-to-day store operations.</p>
            </div>
            <div class="brand-footer">Secure access to your store workspace</div>
        </section>

        <section class="form-panel">
            <div class="form-content">
                <h2>Welcome back</h2>
                <p class="intro">Sign in to continue to your Micro POS workspace.</p>

                <form method="POST" action="{{ route('auth.login.submit') }}">
                    @csrf
                    <div class="field">
                        <label for="email">Email address</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', app()->isLocal() ? 'admin@mpos.local' : '') }}"
                            placeholder="you@store.com"
                            autocomplete="username"
                            required
                            autofocus
                            @error('email') aria-invalid="true" @enderror
                        >
                        @error('email')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            value="{{ app()->isLocal() ? '12345678' : '' }}"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                            @error('password') aria-invalid="true" @enderror
                        >
                        @error('password')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-options">
                        <label class="remember">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            Remember me
                        </label>
                        <a href="{{ route('forgot.password') }}">Forgot password?</a>
                    </div>

                    <button class="submit" type="submit">Sign in</button>
                </form>

                @if (app()->isLocal())
                    <div class="demo-note">
                        <strong>Local demo account</strong><br>
                        Email: admin@mpos.local &nbsp;·&nbsp; Password: 12345678
                    </div>
                @endif
                <p class="security-note">Your account is protected by secure sign-in.</p>
            </div>
        </section>
    </main>
</body>
</html>
