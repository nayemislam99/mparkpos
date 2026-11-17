<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101b2d">
    <title>Sign in | {{ config('app.name', 'M POS') }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172235;
            background: #f3f6fa;
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
                radial-gradient(ellipse at 18% 12%, rgba(52, 211, 153, .13), transparent 34%),
                #f3f6fa;
        }

        .login-shell {
            width: min(100%, 1040px);
            min-height: 620px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            border: 1px solid #e6ebf1;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(27, 43, 65, .12);
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
                radial-gradient(circle at 88% 18%, rgba(52, 211, 153, .2), transparent 25%),
                linear-gradient(145deg, #13243b, #101b2d 72%);
        }

        .brand-panel::after {
            position: absolute;
            right: -130px;
            bottom: -180px;
            width: 400px;
            height: 400px;
            border: 1px solid rgba(255, 255, 255, .09);
            border-radius: 50%;
            box-shadow: 0 0 0 42px rgba(255, 255, 255, .025), 0 0 0 84px rgba(255, 255, 255, .02);
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

        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 13px;
            color: #10251f;
            background: #70e0b0;
            font-size: 18px;
            font-weight: 850;
        }

        .brand-copy { max-width: 390px; margin: auto 0; padding: 72px 0; }
        .eyebrow { color: #8ae5bd; font-size: 12px; font-weight: 750; letter-spacing: .16em; text-transform: uppercase; }
        .brand-copy h1 { margin: 18px 0 16px; font-size: clamp(36px, 4vw, 52px); line-height: 1.08; letter-spacing: -.055em; }
        .brand-copy p { max-width: 360px; margin: 0; color: #b7c4d4; font-size: 15px; line-height: 1.8; }
        .brand-footer { color: #8392a7; font-size: 12px; }

        .form-panel { display: flex; align-items: center; justify-content: center; padding: 56px; }
        .form-content { width: min(100%, 360px); }
        .form-content h2 { margin: 0; font-size: 30px; letter-spacing: -.045em; }
        .intro { margin: 10px 0 32px; color: #7a8798; font-size: 14px; line-height: 1.6; }
        .field { margin-bottom: 20px; }
        .field label { display: block; margin-bottom: 8px; color: #344156; font-size: 13px; font-weight: 650; }
        .field input {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #dfe5ec;
            border-radius: 10px;
            outline: none;
            background: #fff;
            color: #172235;
            font: inherit;
            font-size: 14px;
            transition: border-color .15s, box-shadow .15s;
        }
        .field input:focus { border-color: #35b982; box-shadow: 0 0 0 3px rgba(53, 185, 130, .14); }
        .field input::placeholder { color: #a4adba; }
        .field input[aria-invalid="true"] { border-color: #dc5364; }
        .field-error { margin: 7px 0 0; color: #bc3547; font-size: 12px; }
        .form-options { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 4px 0 24px; }
        .remember { display: inline-flex; align-items: center; gap: 8px; color: #687588; font-size: 13px; }
        .remember input { accent-color: #209a69; }
        a { color: #16875c; font-size: 13px; font-weight: 650; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .submit {
            width: 100%;
            height: 49px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: #16875c;
            box-shadow: 0 7px 16px rgba(22, 135, 92, .18);
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            transition: background .15s, transform .15s;
        }
        .submit:hover { transform: translateY(-1px); background: #11764f; }
        .demo-note { margin-top: 24px; padding: 13px 14px; border: 1px solid #dcefe6; border-radius: 10px; color: #527060; background: #f3fbf7; font-size: 12px; line-height: 1.7; }
        .demo-note strong { color: #226b4b; }
        .security-note { margin: 24px 0 0; color: #98a2b0; font-size: 11px; text-align: center; }

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
        <section class="brand-panel" aria-label="M POS">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">M</span>
                <span>{{ config('app.name', 'M POS') }}</span>
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
                <p class="intro">Sign in to continue to your M POS workspace.</p>

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