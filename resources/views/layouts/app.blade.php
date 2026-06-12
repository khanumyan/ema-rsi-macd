<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Trading Helper')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --sidebar-w: 230px;
            --bg:        #0a0a0f;
            --sidebar-bg:#0f0f1a;
            --card-bg:   rgba(20, 14, 40, 0.7);
            --border:    rgba(168, 85, 247, 0.15);
            --accent:    #a855f7;
            --accent2:   #ec4899;
            --text:      #f1f5f9;
            --muted:     #94a3b8;
            --green:     #0ecb81;
            --red:       #f6465d;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        .layout {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ═══════════════ SIDEBAR ═══════════════ */
        .sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Logo */
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 18px 18px;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
        }

        .sidebar-logo img {
            width: 36px;
            height: 36px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(248,113,113,.5));
        }

        .sidebar-logo-text {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .3px;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .sidebar-logo-sub {
            font-size: 10px;
            color: var(--muted);
            font-weight: 400;
        }

        /* Nav */
        .sidebar-nav {
            flex: 1;
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .8px;
            padding: 10px 8px 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
            transition: all .2s;
            position: relative;
        }

        .nav-item:hover {
            background: rgba(168, 85, 247, 0.1);
            color: var(--text);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(168,85,247,.25), rgba(236,72,153,.15));
            color: #fff;
            border: 1px solid rgba(168,85,247,.3);
        }

        .nav-item.active .nav-icon {
            filter: drop-shadow(0 0 6px rgba(168,85,247,.8));
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 999px;
            background: rgba(168,85,247,.2);
            color: var(--accent);
            border: 1px solid rgba(168,85,247,.3);
        }

        /* User info at bottom */
        .sidebar-footer {
            border-top: 1px solid var(--border);
            padding: 14px 12px;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .user-info { flex: 1; overflow: hidden; }

        .user-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email {
            font-size: 10px;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .logout-form button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid rgba(239,68,68,.25);
            background: rgba(239,68,68,.08);
            color: #fca5a5;
            font-size: 12px;
            cursor: pointer;
            transition: all .2s;
        }

        .logout-form button:hover {
            background: rgba(239,68,68,.18);
            border-color: rgba(239,68,68,.4);
        }

        /* ═══════════════ CONTENT ═══════════════ */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: linear-gradient(135deg, #0a0a0a 0%, #120a1e 50%, #0a0a0a 100%);
        }

        /* Top bar inside content */
        .content-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 24px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            background: rgba(10, 10, 20, 0.6);
            backdrop-filter: blur(10px);
        }

        .topbar-title {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-time {
            font-size: 12px;
            color: var(--muted);
        }

        /* Scrollable page body */
        .page-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(168,85,247,.3); border-radius: 4px; }

        /* ═══ Responsive mobile sidebar ═══ */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 1000;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--sidebar-bg);
            color: var(--text);
            font-size: 18px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .sidebar-toggle { display: flex; }
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                bottom: 0;
                z-index: 999;
                transition: left .3s;
            }
            .sidebar.open { left: 0; }
            .main-content { padding-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

<button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

<div class="layout">
    <!-- ═══ SIDEBAR ═══ -->
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-logo">
            <img src="{{ asset('images/trading-helper-logo.png') }}" alt="Logo">
            <div>
                <div class="sidebar-logo-text">TRADING HELPER</div>
                <div class="sidebar-logo-sub">EMA · RSI · MACD</div>
            </div>
        </a>

        <nav class="sidebar-nav">
            <span class="nav-section-label">Основное</span>

            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-icon">🏠</span>
                Главная
            </a>

            <a href="{{ route('trading.index') }}"
               class="nav-item {{ request()->routeIs('trading.*') ? 'active' : '' }}">
                <span class="nav-icon">📉</span>
                Трейдинг
            </a>

            <span class="nav-section-label">Аналитика</span>

            <a href="{{ route('signals.index', ['filter' => 'strong']) }}"
               class="nav-item {{ request()->routeIs('signals.*') ? 'active' : '' }}">
                <span class="nav-icon">⚡</span>
                Сигналы
            </a>

            <a href="{{ route('results.index', ['filter' => 'strong']) }}"
               class="nav-item {{ request()->routeIs('results.*') ? 'active' : '' }}">
                <span class="nav-icon">📊</span>
                Результаты
            </a>

            <a href="{{ route('news.index') }}"
               class="nav-item {{ request()->routeIs('news.*') ? 'active' : '' }}">
                <span class="nav-icon">📰</span>
                Новости
            </a>

            <a href="{{ route('strategies.index') }}"
               class="nav-item {{ request()->routeIs('strategies.*') ? 'active' : '' }}">
                <span class="nav-icon">🤖</span>
                Стратегии
            </a>

            <span class="nav-section-label">Управление</span>

            <a href="{{ route('profiles.index') }}"
               class="nav-item {{ request()->routeIs('profiles.*') ? 'active' : '' }}">
                <span class="nav-icon">👤</span>
                Профили
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email ?? 'U', 0, 1)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name ?? 'User' }}</div>
                    <div class="user-email">{{ auth()->user()->email ?? '' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit">
                    <span>↩</span> Выйти
                </button>
            </form>
        </div>
    </aside>

    <!-- ═══ MAIN CONTENT ═══ -->
    <div class="main-content">
        <div class="content-topbar">
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
            <div class="topbar-right">
                <span class="topbar-time" id="clockDisplay"></span>
            </div>
        </div>

        <div class="page-body">
            @yield('content')
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
    }

    // Live clock
    function updateClock() {
        const el = document.getElementById('clockDisplay');
        if (el) el.textContent = new Date().toLocaleString('ru', {
            weekday: 'short', day: '2-digit', month: 'short',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>

@stack('scripts')
</body>
</html>
