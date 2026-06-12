<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Trading Helper – Регистрация</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0a2e 50%, #0a0a0a 100%);
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: rgba(15, 23, 42, 0.9);
            border-radius: 20px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            padding: 28px 24px 24px 24px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.9);
        }

        .logo-container {
            margin-bottom: 18px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-image {
            max-width: 140px;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 4px 16px rgba(248, 113, 113, 0.4));
            animation: fadeInScale 0.6s ease-out;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }

        .header-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 4px;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-subtitle {
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
            margin-bottom: 20px;
        }

        .error-box {
            margin-bottom: 14px;
            border-radius: 10px;
            background: rgba(185, 28, 28, 0.2);
            border: 1px solid rgba(248, 113, 113, 0.5);
            padding: 10px 12px;
            font-size: 12px;
            color: #fecaca;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
            color: #e5e7eb;
        }

        .input {
            width: 100%;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(15, 23, 42, 0.8);
            padding: 9px 11px;
            font-size: 13px;
            color: #e5e7eb;
            outline: none;
            transition: all 0.25s ease;
        }

        .input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 1px rgba(168, 85, 247, 0.7);
        }

        .input.is-error {
            border-color: rgba(248, 113, 113, 0.7);
        }

        .field-error {
            font-size: 11px;
            color: #fca5a5;
            margin-top: 4px;
        }

        .submit-btn {
            width: 100%;
            border: none;
            border-radius: 999px;
            padding: 9px 16px;
            font-size: 14px;
            font-weight: 500;
            color: #f9fafb;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 4px;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.35);
        }

        .footer-note {
            margin-top: 14px;
            font-size: 12px;
            text-align: center;
            color: #6b7280;
        }

        .footer-note a {
            color: #a855f7;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-note a:hover {
            color: #ec4899;
        }

        .home-link {
            position: fixed;
            top: 18px;
            left: 18px;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(15, 23, 42, 0.9);
            color: #e5e7eb;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .home-link:hover {
            border-color: #a855f7;
            color: #e9d5ff;
            box-shadow: 0 6px 18px rgba(168, 85, 247, 0.4);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <a href="{{ url('/') }}" class="home-link">Главная</a>

    <div class="card">
        <div class="logo-container">
            <img src="{{ asset('images/trading-helper-logo.png') }}" alt="Trading Helper Logo" class="logo-image">
        </div>

        <h1 class="header-title">TRADING HELPER</h1>
        <p class="header-subtitle">Создать аккаунт</p>

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}">
            @csrf

            <div class="form-group">
                <label for="name" class="label">Имя</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    class="input {{ $errors->has('name') ? 'is-error' : '' }}"
                    placeholder="Ваше имя"
                >
                @error('name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="label">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="input {{ $errors->has('email') ? 'is-error' : '' }}"
                    placeholder="example@email.com"
                >
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="label">Пароль</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="input {{ $errors->has('password') ? 'is-error' : '' }}"
                    placeholder="Минимум 8 символов"
                >
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="label">Подтвердите пароль</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    class="input"
                    placeholder="Повторите пароль"
                >
            </div>

            <button type="submit" class="submit-btn">
                Зарегистрироваться
            </button>
        </form>

        <div class="footer-note">
            Уже есть аккаунт? <a href="{{ route('login') }}">Войти</a>
        </div>
    </div>
</body>
</html>
