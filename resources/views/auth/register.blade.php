<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — BizAccount Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Same CSS variables and background as login */
        :root {
            --primary: #7C3AED; --primary-light: #8B5CF6; --primary-dark: #5B21B6;
            --accent: #06B6D4; --bg-dark: #0F0E17;
            --text-muted: #9090B0; --border: rgba(255,255,255,0.1);
            --error: #EF4444;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Outfit',sans-serif; background:var(--bg-dark); min-height:100vh; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .bg-gradient { position:fixed; inset:0; background:linear-gradient(135deg,#0F0E17 0%,#1A1035 40%,#0D1B2A 100%); z-index:0; }
        .orb { position:fixed; border-radius:50%; filter:blur(80px); opacity:.15; animation:floatOrb linear infinite; }
        .orb-1 { width:500px;height:500px;background:#7C3AED;top:-100px;right:-100px;animation-duration:20s; }
        .orb-2 { width:400px;height:400px;background:#06B6D4;bottom:-80px;left:-80px;animation-duration:15s;animation-delay:-5s; }
        @keyframes floatOrb { 0%{transform:translate(0,0)}50%{transform:translate(30px,-30px)}100%{transform:translate(0,0)} }
        .grid-pattern { position:fixed;inset:0;background-image:linear-gradient(rgba(124,58,237,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(124,58,237,.03) 1px,transparent 1px);background-size:50px 50px;z-index:0; }
        .particles { position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none; }
        .particle { position:absolute;width:2px;height:2px;background:rgba(124,58,237,.6);border-radius:50%;animation:rise linear infinite; }
        @keyframes rise { 0%{transform:translateY(100vh);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-10vh) translateX(50px);opacity:0} }

        .register-wrapper { position:relative;z-index:10;width:100%;max-width:480px;padding:24px;animation:slideUp .8s cubic-bezier(.22,1,.36,1) both; }
        @keyframes slideUp { from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)} }

        .register-card { background:rgba(255,255,255,.05);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.10);border-radius:24px;padding:40px 40px;box-shadow:0 32px 64px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.08); }

        .logo-area { text-align:center;margin-bottom:28px; }
        .logo-icon { width:56px;height:56px;background:linear-gradient(135deg,#7C3AED,#06B6D4);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;box-shadow:0 8px 24px rgba(124,58,237,.4); }
        .logo-title { font-size:22px;font-weight:700;color:#fff;letter-spacing:-.5px; }
        .logo-title span { color:#8B5CF6; }
        .logo-subtitle { color:var(--text-muted);font-size:13px;margin-top:4px; }

        .form-row { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
        .form-group { margin-bottom:18px;animation:slideUp .8s cubic-bezier(.22,1,.36,1) both; }
        label { display:block;color:var(--text-muted);font-size:12px;font-weight:500;margin-bottom:6px;letter-spacing:.3px;text-transform:uppercase; }
        .input-wrap { position:relative; }
        .input-wrap .icon { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:14px;transition:color .2s; }
        input { width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);border-radius:10px;padding:11px 12px 11px 38px;color:#fff;font-family:'Outfit',sans-serif;font-size:14px;outline:none;transition:all .3s; }
        input:focus { border-color:var(--primary-light);background:rgba(124,58,237,.08);box-shadow:0 0 0 3px rgba(124,58,237,.15); }
        input::placeholder { color:rgba(255,255,255,.25); }
        .error-msg { color:var(--error);font-size:11px;margin-top:4px; }

        .btn-register { width:100%;padding:13px;background:linear-gradient(135deg,#7C3AED,#5B21B6);border:none;border-radius:12px;color:#fff;font-family:'Outfit',sans-serif;font-size:15px;font-weight:600;cursor:pointer;transition:all .3s;box-shadow:0 4px 16px rgba(124,58,237,.4); }
        .btn-register:hover { transform:translateY(-2px);box-shadow:0 8px 24px rgba(124,58,237,.5); }

        .login-link { text-align:center;color:var(--text-muted);font-size:13px;margin-top:16px; }
        .login-link a { color:#8B5CF6;text-decoration:none;font-weight:600; }

        @media(max-width:500px) { .form-row{grid-template-columns:1fr} .register-card{padding:32px 20px} }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="grid-pattern"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="particles" id="particles"></div>

    <div class="register-wrapper">
        <div class="register-card">
            <div class="logo-area">
                <div class="logo-icon">💼</div>
                <div class="logo-title">Biz<span>Account</span> Pro</div>
                <div class="logo-subtitle">Create your account</div>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-wrap">
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="John Doe" required autofocus>
                        <span class="icon">👤</span>
                    </div>
                    @error('name')<div class="error-msg">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
                        <span class="icon">✉</span>
                    </div>
                    @error('email')<div class="error-msg">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrap">
                            <input type="password" name="password" placeholder="Min 8 chars" required>
                            <span class="icon">🔒</span>
                        </div>
                        @error('password')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-wrap">
                            <input type="password" name="password_confirmation" placeholder="Repeat" required>
                            <span class="icon">🔒</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-register">Create Account →</button>
                <div class="login-link">
                    Already have an account? <a href="{{ route('login') }}">Sign In</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const c = document.getElementById('particles');
        for(let i=0;i<20;i++){
            const p=document.createElement('div');
            p.className='particle';
            p.style.cssText=`left:${Math.random()*100}vw;animation-duration:${8+Math.random()*12}s;animation-delay:${-Math.random()*20}s;width:${1+Math.random()*3}px;height:${1+Math.random()*3}px`;
            c.appendChild(p);
        }
    </script>
</body>
</html>
