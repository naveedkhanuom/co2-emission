<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Altayaboon</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #00A86B;
            --color-primary-dark: #007A4F;
            --color-card-bg: #ffffff;
            --color-body-bg: #f3f8f7;
            --color-accent: #3498DB;
            --color-text-dark: #1B2C39;
            --color-text-light: #6B7B83;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }

        body {
            background-color: var(--color-body-bg);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-wrapper { width: 100%; display: flex; justify-content: center; }

        .login-box {
            width: 100%;
            max-width: 420px;
            background: var(--color-card-bg);
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--box-shadow);
            text-align: center;
            animation: fadeIn 0.8s ease-out forwards;
        }

        .login-box .icon-logo { width: 100%; height: 60px; margin-bottom: 20px; display:flex; justify-content:center; align-items:center; }
        .login-box .icon-logo img { height: 100%; width: auto; }

        .login-box h2 { color: var(--color-text-dark); margin-bottom: 5px; font-weight:700; }
        .login-box p.subtitle { color: var(--color-text-light); font-size: 15px; margin-bottom: 30px; }

        .input-group { margin-bottom: 20px; text-align:left; }
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            background-color: #fdfdfd;
            color: var(--color-text-dark);
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .input-group input:focus { border-color: var(--color-accent); box-shadow: 0 0 10px rgba(52,152,219,0.2); }
        .input-group input.is-invalid { border-color: #d9534f; }
        .invalid-feedback { display:block; color:#d9534f; margin-top:5px; font-size:13px; }

        .form-check { display:flex; align-items:center; margin-bottom:30px; justify-content:space-between; }
        .form-check-label { font-size:14px; color: var(--color-text-light); cursor:pointer; padding-left:5px; }
        .form-check-input { width:16px; height:16px; margin:0; accent-color: var(--color-primary); cursor:pointer; }

        .btn {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border:none;
            background: var(--color-primary);
            color:#fff;
            font-weight:700;
            font-size:17px;
            cursor:pointer;
            transition:0.2s ease-out;
            box-shadow:0 4px 15px rgba(0,168,107,0.3);
            text-transform:uppercase;
            letter-spacing:1px;
        }
        .btn:hover { background: var(--color-primary-dark); transform: translateY(-2px); box-shadow:0 6px 20px rgba(0,168,107,0.4); }

        .text-center-link { margin-top:25px; font-size:14px; color: var(--color-text-light); }
        .text-center-link a { color: var(--color-accent); text-decoration:none; font-weight:600; margin-left:5px; }
        .text-center-link a:hover { text-decoration:underline; }

        @keyframes fadeIn { from {opacity:0; transform:translateY(30px);} to {opacity:1; transform:translateY(0);} }

        @media (max-width:480px) { .login-box { padding:30px 20px; border-radius:15px; } }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-box">
        <div class="icon-logo">
            <img src="https://altayaboon.com/assets/altayaboonlogosvg-BKWEtYJo.svg"
                 alt="Altayaboon Logo"
                 loading="eager"
                 onerror="this.style.display='none'; this.closest('.icon-logo').innerHTML='<h3 style=\'color: var(--color-primary); font-size: 20px;\'>ALTAYABOON</h3>';"/>
        </div>

        <h2>Welcome Back</h2>
        <p class="subtitle">Log in to track your environmental impact.</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="input-group">
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="Email">
                @error('email') <span class="invalid-feedback"><strong>{{ $message }}</strong></span> @enderror
            </div>

            <div class="input-group">
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Password">
                @error('password') <span class="invalid-feedback"><strong>{{ $message }}</strong></span> @enderror
            </div>

            <div class="form-check">
                <div style="display:flex; align-items:center;">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">Remember Me</label>
                </div>
                <a href="{{ route('password.request') }}" class="form-check-label" style="text-decoration:underline;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn">Login</button>

            <p class="text-center-link">
                Don't have an account? <a href="{{ route('register') }}">Sign Up</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>
