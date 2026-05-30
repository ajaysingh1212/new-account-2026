<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — BizAccount Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7C3AED;
            --primary-light: #8B5CF6;
            --primary-dark: #5B21B6;
            --accent: #06B6D4;
            --bg-dark: #0F0E17;
            --bg-card: rgba(255,255,255,0.06);
            --text: #E8E8F0;
            --text-muted: #9090B0;
            --border: rgba(255,255,255,0.1);
            --success: #10B981;
            --error: #EF4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated gradient background */
        .bg-gradient {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #0F0E17 0%, #1A1035 40%, #0D1B2A 100%);
            z-index: 0;
        }

        /* Floating orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            animation: floatOrb linear infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: #7C3AED; top: -100px; left: -100px; animation-duration: 20s; }
        .orb-2 { width: 400px; height: 400px; background: #06B6D4; bottom: -80px; right: -80px; animation-duration: 15s; animation-delay: -5s; }
        .orb-3 { width: 300px; height: 300px; background: #EC4899; top: 50%; left: 50%; animation-duration: 25s; animation-delay: -10s; }

        @keyframes floatOrb {
            0%   { transform: translate(0,0) rotate(0deg); }
            33%  { transform: translate(30px,-30px) rotate(120deg); }
            66%  { transform: translate(-20px,20px) rotate(240deg); }
            100% { transform: translate(0,0) rotate(360deg); }
        }

        /* Grid pattern */
        .grid-pattern {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(124,58,237,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(124,58,237,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 0;
        }

        /* Floating particles */
        .particles { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
        .particle {
            position: absolute;
            width: 2px; height: 2px;
            background: rgba(124,58,237,0.6);
            border-radius: 50%;
            animation: rise linear infinite;
        }
        @keyframes rise {
            0%   { transform: translateY(100vh) translateX(0); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { transform: translateY(-10vh) translateX(50px); opacity: 0; }
        }

        /* Card */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 24px;
            animation: slideUp 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 32px 64px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.08);
        }

        .logo-area {
            text-align: center;
            margin-bottom: 36px;
            animation: slideUp 0.8s 0.1s cubic-bezier(0.22,1,0.36,1) both;
        }

        .logo-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #7C3AED, #06B6D4);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
            box-shadow: 0 8px 24px rgba(124,58,237,0.4);
            animation: pulse-logo 3s ease-in-out infinite;
        }
        @keyframes pulse-logo {
            0%, 100% { box-shadow: 0 8px 24px rgba(124,58,237,0.4); }
            50% { box-shadow: 0 8px 32px rgba(124,58,237,0.7); }
        }

        .logo-title {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
        }
        .logo-title span { color: #8B5CF6; }
        .logo-subtitle { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        /* Form */
        .form-group {
            margin-bottom: 20px;
            animation: slideUp 0.8s cubic-bezier(0.22,1,0.36,1) both;
        }
        .form-group:nth-child(1) { animation-delay: 0.2s; }
        .form-group:nth-child(2) { animation-delay: 0.3s; }
        .form-group:nth-child(3) { animation-delay: 0.35s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }

        label {
            display: block;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .input-wrap { position: relative; }

        .input-wrap .icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 15px;
            transition: color 0.2s;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 12px;
            padding: 13px 14px 13px 42px;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            outline: none;
            transition: all 0.3s;
        }

        input:focus {
            border-color: var(--primary-light);
            background: rgba(124,58,237,0.08);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
        }
        input:focus + .icon, .input-wrap:focus-within .icon { color: var(--primary-light); }
        input::placeholder { color: rgba(255,255,255,0.25); }

        .error-msg {
            color: var(--error);
            font-size: 12px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .form-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            animation: slideUp 0.8s 0.45s cubic-bezier(0.22,1,0.36,1) both;
        }

        .remember-me {
            display: flex; align-items: center; gap: 8px;
            color: var(--text-muted); font-size: 13px; cursor: pointer;
        }
        .remember-me input[type="checkbox"] {
            width: 16px; height: 16px; padding: 0;
            accent-color: var(--primary);
        }

        .forgot-link {
            color: var(--primary-light);
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--accent); }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #7C3AED, #5B21B6);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(124,58,237,0.4);
            animation: slideUp 0.8s 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(124,58,237,0.5);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(rgba(255,255,255,0.1), transparent);
            pointer-events: none;
        }

        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 24px 0;
            color: var(--text-muted); font-size: 12px;
            animation: slideUp 0.8s 0.55s cubic-bezier(0.22,1,0.36,1) both;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px;
            background: rgba(255,255,255,0.08);
        }

        .register-link {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            animation: slideUp 0.8s 0.6s cubic-bezier(0.22,1,0.36,1) both;
        }
        .register-link a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .register-link a:hover { color: var(--accent); }

        /* Alert */
        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 10px;
            padding: 12px 16px;
            color: #FCA5A5;
            font-size: 13px;
            margin-bottom: 20px;
        }

        @media (max-width: 480px) {
            .login-card { padding: 36px 24px; }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="grid-pattern"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Particles -->
    <div class="particles" id="particles"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-area">
                <img src="{{ asset('images/logo.png') }}" alt="" width="100px;">
                <div class="logo-title">Eemot <span>Account</span> Pro</div>
                <div class="logo-subtitle">Sign in to your account</div>
            </div>

            @if($errors->any())
            <div class="alert-error">
                ⚠️ {{ $errors->first() }}
            </div>
            @endif

            @if(session('status'))
            <div class="alert-error" style="background:rgba(16,185,129,0.1);border-color:rgba(16,185,129,0.3);color:#6EE7B7;">
                ✓ {{ session('status') }}
            </div>
            @endif

            @if(session('pin_login_user_id'))
            <form method="POST" action="{{ route('pin-login') }}" style="margin-bottom:24px;">
                @csrf
                <div class="form-group">
                    <label>Quick PIN Login</label>
                    <div class="input-wrap">
                        <input type="password" name="pin" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="6 digit PIN" required>
                        <span class="icon">#</span>
                    </div>
                    @error('pin')<div class="error-msg">! {{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn-login">Unlock Account</button>
                <div class="divider">OR PASSWORD LOGIN</div>
            </form>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <input type="email" name="email" value="{{ old('email') }}"
                            placeholder="you@example.com" required autofocus>
                        <span class="icon">✉</span>
                    </div>
                    @error('email')<div class="error-msg">⚠ {{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <input type="password" name="password" placeholder="Enter password" required>
                        <span class="icon">🔒</span>
                    </div>
                    @error('password')<div class="error-msg">⚠ {{ $message }}</div>@enderror
                </div>

                <div class="form-meta">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="btn-login">Sign In →</button>

                <div class="divider">OR</div>
                <div class="register-link">
                    Don't have an account? <a href="{{ route('register') }}">Create Account</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Generate particles
        const container = document.getElementById('particles');
        for (let i = 0; i < 25; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.left = Math.random() * 100 + 'vw';
            p.style.animationDuration = (8 + Math.random() * 12) + 's';
            p.style.animationDelay = (-Math.random() * 20) + 's';
            p.style.width = p.style.height = (1 + Math.random() * 3) + 'px';
            p.style.opacity = (0.2 + Math.random() * 0.6).toString();
            container.appendChild(p);
        }
    </script>
</body>
</html>
