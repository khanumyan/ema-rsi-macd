<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Trading Helper – Вход</title>
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

        .login-card {
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
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
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

        .hint {
            font-size: 11px;
            color: #9ca3af;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .hint code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            background: rgba(15, 23, 42, 0.9);
            padding: 1px 4px;
            border-radius: 4px;
            color: #e5e7eb;
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
            font-size: 11px;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-container">
            <img src="{{ asset('images/trading-helper-logo.png') }}" alt="Trading Helper Logo" class="logo-image">
        </div>

        <h1 class="header-title">TRADING HELPER</h1>
        <p class="header-subtitle">Вход в панель управления EMA + RSI + MACD</p>

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="form-group">
                <label for="email" class="label">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', 'admin@ema-rsi-macd.local') }}"
                    required
                    autofocus
                    class="input"
                >
            </div>

            <div class="form-group">
                <label for="password" class="label">Пароль</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="input"
                >
            </div>

            <div class="hint">
                Статичные данные для входа:<br>
                Email: <code>admin@ema-rsi-macd.local</code><br>
                Пароль: <code>RsiMacd!2026</code>
            </div>

            <button type="submit" class="submit-btn">
                Войти
            </button>
        </form>

        <div class="footer-note">
            Trading Helper · {{ now()->year }}
        </div>
    </div>
</body>
</html>

