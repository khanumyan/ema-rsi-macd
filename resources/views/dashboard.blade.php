<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Trading Helper – Dashboard</title>
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
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        .header {
            text-align: center;
            padding: 40px 16px 30px 16px;
            position: relative;
        }

        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 8px 16px;
            color: #fca5a5;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
        }

        .user-info {
            position: absolute;
            top: 20px;
            left: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #94a3b8;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.7);
        }

        .home-chip {
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.7);
            background: rgba(15, 23, 42, 0.9);
            color: #e5e7eb;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .home-chip:hover {
            border-color: #a855f7;
            color: #e9d5ff;
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.4);
        }

        .user-email {
            font-size: 12px;
            color: #9ca3af;
        }

        .header-title {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .header-subtitle {
            font-size: 16px;
            color: #94a3b8;
        }

        .logo-container {
            margin-bottom: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-image {
            max-width: 180px;
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

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .menu-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 20px;
            padding: 24px;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .menu-card:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(168, 85, 247, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.2);
        }

        .menu-card-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #ffffff;
        }

        .menu-card-description {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .menu-card-arrow {
            margin-top: 16px;
            font-size: 20px;
            color: #a855f7;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stats-section {
            margin-top: 60px;
            padding: 24px;
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 16px;
        }

        .stats-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            color: #a855f7;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

        .stat-item {
            text-align: center;
            padding: 16px;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #94a3b8;
        }

        .footer {
            text-align: center;
            padding: 40px 16px;
            color: #64748b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="user-info">
                <a href="{{ route('dashboard') }}" class="home-chip">Главная</a>
                <span class="user-email">{{ auth()->user()->email ?? 'admin' }}</span>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    Выйти
                </button>
            </form>

            <div class="logo-container">
                <img src="{{ asset('images/trading-helper-logo.png') }}" alt="Trading Helper Logo" class="logo-image">
            </div>

            <h1 class="header-title">TRADING HELPER Dashboard</h1>
            <p class="header-subtitle">
                Панель управления стратегией EMA + RSI + MACD и Telegram-сигналами
            </p>
        </header>

        <main>
            <section class="menu-grid">
                <a href="{{ route('signals.index', ['filter' => 'strong']) }}" class="menu-card">
                    <div class="menu-card-title">Сигналы</div>
                    <div class="menu-card-description">
                        История сигналов по EMA+RSI+MACD, сила сигналов, тип (BUY/SELL) и статусы исполнения.
                    </div>
                </a>

                <a href="{{ route('results.index', ['filter' => 'strong']) }}" class="menu-card">
                    <div class="menu-card-title">Результаты</div>
                    <div class="menu-card-description">
                        Ежедневная статистика по выполненным и пропущенным сигналам, средний профит/стоп и итоговый результат.
                    </div>
                </a>
            </section>

            <section class="stats-section">
                <h2 class="stats-title">Краткая статистика (заглушка)</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">—</div>
                        <div class="stat-label">Сигналов за сегодня</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">—</div>
                        <div class="stat-label">Отправлено в Telegram</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">—</div>
                        <div class="stat-label">Активных стратегий</div>
                    </div>
                </div>
            </section>

            <footer class="footer">
                Trading Helper · EMA + RSI + MACD · {{ now()->year }}
            </footer>
        </main>
    </div>
</body>
</html>

