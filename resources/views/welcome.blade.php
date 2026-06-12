<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Trading Helper — Платформа для алготрейдинга</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Профессиональная платформа алготрейдинга на основе EMA + RSI + MACD. Сигналы, аналитика, торговый терминал.">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --accent:  #a855f7;
            --accent2: #ec4899;
            --green:   #0ecb81;
            --red:     #f6465d;
            --yellow:  #f0b90b;
            --bg:      #080810;
            --card:    rgba(15,10,30,.75);
            --border:  rgba(168,85,247,.18);
            --text:    #f1f5f9;
            --muted:   #94a3b8;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ══════════════ CANVAS BG ══════════════ */
        #bgCanvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            opacity: .55;
        }

        /* ══════════════ TICKER BAR ══════════════ */
        .ticker-bar {
            position: relative;
            z-index: 10;
            background: rgba(10,5,20,.9);
            border-bottom: 1px solid var(--border);
            overflow: hidden;
            height: 34px;
            display: flex;
            align-items: center;
        }

        .ticker-track {
            display: flex;
            gap: 48px;
            animation: tickerScroll 35s linear infinite;
            white-space: nowrap;
        }

        .ticker-item  { display: flex; align-items: center; gap: 8px; font-size: 12px; }
        .ticker-sym   { font-weight: 700; color: var(--text); }
        .ticker-price { color: var(--muted); }
        .ticker-chg.up   { color: var(--green); }
        .ticker-chg.down { color: var(--red); }

        @keyframes tickerScroll {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }

        /* ══════════════ NAVBAR ══════════════ */
        nav {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            height: 64px;
            background: rgba(8,8,16,.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo img {
            height: 34px;
            filter: drop-shadow(0 0 8px rgba(248,113,113,.5));
        }

        .nav-logo-text {
            font-size: 15px;
            font-weight: 800;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links { display: flex; align-items: center; gap: 8px; }

        .nav-links a {
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-ghost {
            color: var(--muted);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            color: var(--text);
            border-color: rgba(168,85,247,.5);
            background: rgba(168,85,247,.1);
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            border: 1px solid transparent;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(168,85,247,.45);
        }

        /* ══════════════ HERO ══════════════ */
        .hero {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 110px 20px 90px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 999px;
            border: 1px solid rgba(168,85,247,.4);
            background: rgba(168,85,247,.1);
            font-size: 12px;
            font-weight: 600;
            color: #c4b5fd;
            margin-bottom: 28px;
            animation: fadeUp .6s ease-out both;
        }

        .hero-badge .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 8px var(--green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }

        .hero h1 {
            font-size: clamp(36px, 6vw, 72px);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -.02em;
            margin-bottom: 22px;
            animation: fadeUp .7s .1s ease-out both;
        }

        .hero h1 .grad {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 50%, #f97316 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: clamp(15px, 2vw, 19px);
            color: var(--muted);
            max-width: 660px;
            line-height: 1.7;
            margin-bottom: 40px;
            animation: fadeUp .7s .2s ease-out both;
        }

        .hero-cta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
            animation: fadeUp .7s .3s ease-out both;
        }

        .hero-cta a {
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all .25s;
        }

        .cta-main { background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff; }
        .cta-main:hover { transform: translateY(-2px); box-shadow: 0 12px 36px rgba(168,85,247,.5); }
        .cta-outline { border: 1px solid rgba(168,85,247,.4); color: #c4b5fd; background: rgba(168,85,247,.08); }
        .cta-outline:hover { border-color: var(--accent); background: rgba(168,85,247,.18); }

        /* ══════════════ STATS ROW ══════════════ */
        .stats-strip {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
            gap: 1px;
            background: var(--border);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .stat-cell { background: rgba(8,8,16,.9); padding: 28px 20px; text-align: center; }

        .stat-num {
            font-size: 36px;
            font-weight: 900;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
        }

        .stat-desc { font-size: 13px; color: var(--muted); margin-top: 4px; }

        /* ══════════════ SECTION BASE ══════════════ */
        section {
            position: relative;
            z-index: 1;
            padding: 90px 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-label {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--accent);
            margin-bottom: 12px;
        }

        .section-title { font-size: clamp(26px, 4vw, 44px); font-weight: 800; line-height: 1.2; margin-bottom: 16px; }
        .section-sub   { font-size: 16px; color: var(--muted); max-width: 580px; line-height: 1.7; margin-bottom: 56px; }

        /* ══════════════ FEATURES ══════════════ */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .feature-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: all .3s;
            backdrop-filter: blur(12px);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 0% 0%, rgba(168,85,247,.07) 0%, transparent 70%);
            opacity: 0;
            transition: opacity .3s;
        }

        .feature-card:hover { border-color: rgba(168,85,247,.45); transform: translateY(-5px); box-shadow: 0 20px 48px rgba(168,85,247,.18); }
        .feature-card:hover::before { opacity: 1; }

        .feature-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
            border: 1px solid rgba(168,85,247,.25);
            flex-shrink: 0;
        }

        .fi-purple { background: rgba(168,85,247,.15); }
        .fi-green  { background: rgba(14,203,129,.12); border-color: rgba(14,203,129,.25); }
        .fi-pink   { background: rgba(236,72,153,.12); border-color: rgba(236,72,153,.25); }
        .fi-blue   { background: rgba(59,130,246,.12); border-color: rgba(59,130,246,.25); }
        .fi-yellow { background: rgba(240,185,11,.1);  border-color: rgba(240,185,11,.25); }
        .fi-red    { background: rgba(246,70,93,.12);  border-color: rgba(246,70,93,.25); }
        .fi-cyan   { background: rgba(6,182,212,.12);  border-color: rgba(6,182,212,.25); }

        .feature-title { font-size: 17px; font-weight: 700; }
        .feature-desc  { font-size: 13px; color: var(--muted); line-height: 1.65; }

        .feature-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }

        .tag {
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(168,85,247,.12);
            color: #c4b5fd;
            border: 1px solid rgba(168,85,247,.22);
        }

        /* ══════════════ HOW IT WORKS ══════════════ */
        .how-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
            position: relative;
        }

        .how-grid::before {
            content: '';
            position: absolute;
            top: 36px;
            left: 10%; right: 10%;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--accent), transparent);
        }

        .step { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 20px; }

        .step-num {
            width: 72px; height: 72px;
            border-radius: 50%;
            border: 2px solid rgba(168,85,247,.5);
            background: rgba(168,85,247,.12);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 900;
            color: var(--accent);
            margin-bottom: 18px;
            position: relative; z-index: 1;
            backdrop-filter: blur(8px);
        }

        .step-title { font-size: 15px; font-weight: 700; margin-bottom: 8px; }
        .step-desc  { font-size: 13px; color: var(--muted); line-height: 1.6; }

        /* ══════════════ SIGNAL DEMO ══════════════ */
        .demo-section {
            background: radial-gradient(ellipse at 50% 0%, rgba(168,85,247,.12) 0%, transparent 70%);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 90px 40px;
        }

        .demo-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .demo-signals { display: flex; flex-direction: column; gap: 10px; }

        .signal-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: rgba(10,5,25,.7);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 13px;
            animation: slideRight .5s ease-out both;
            backdrop-filter: blur(8px);
        }

        .signal-row:nth-child(2) { animation-delay: .1s; }
        .signal-row:nth-child(3) { animation-delay: .2s; }
        .signal-row:nth-child(4) { animation-delay: .3s; }
        .signal-row:nth-child(5) { animation-delay: .4s; }

        @keyframes slideRight {
            from { opacity:0; transform: translateX(-16px); }
            to   { opacity:1; transform: translateX(0); }
        }

        .sig-type {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            min-width: 44px;
            text-align: center;
        }

        .sig-buy  { background: rgba(14,203,129,.18); color: var(--green); border: 1px solid rgba(14,203,129,.3); }
        .sig-sell { background: rgba(246,70,93,.18);  color: var(--red);   border: 1px solid rgba(246,70,93,.3); }
        .sig-sym  { font-weight: 700; min-width: 90px; }
        .sig-price { color: var(--muted); flex: 1; }

        .sig-status { padding: 3px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .st-done { background: rgba(14,203,129,.15); color: var(--green); border: 1px solid rgba(14,203,129,.3); }
        .st-miss { background: rgba(246,70,93,.15);  color: var(--red);   border: 1px solid rgba(246,70,93,.3); }
        .st-proc { background: rgba(240,185,11,.1);  color: var(--yellow);border: 1px solid rgba(240,185,11,.3); }

        /* indicators */
        .ind-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; backdrop-filter: blur(10px); }
        .ind-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .ind-name { font-weight: 700; font-size: 13px; }
        .ind-val  { font-size: 13px; font-weight: 700; }
        .ind-bar-bg { height: 4px; background: rgba(255,255,255,.08); border-radius: 999px; overflow: hidden; }
        .ind-bar    { height: 100%; border-radius: 999px; transition: width 1s ease; }

        /* ══════════════ CTA BOTTOM ══════════════ */
        .cta-bottom {
            text-align: center;
            padding: 100px 40px;
            position: relative;
            z-index: 1;
            background: radial-gradient(ellipse at 50% 100%, rgba(168,85,247,.15) 0%, transparent 65%);
        }

        .cta-bottom h2 { font-size: clamp(28px, 5vw, 52px); font-weight: 900; margin-bottom: 18px; }
        .cta-bottom p  { font-size: 17px; color: var(--muted); margin-bottom: 40px; max-width: 500px; margin-left: auto; margin-right: auto; }

        /* ══════════════ FOOTER ══════════════ */
        footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--border);
            background: rgba(6,6,14,.95);
            padding: 36px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .footer-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .footer-logo img  { height: 28px; opacity: .85; }
        .footer-logo span { font-size: 13px; font-weight: 700; color: var(--muted); }
        .footer-copy      { font-size: 12px; color: #475569; }
        .footer-tags      { display: flex; gap: 6px; flex-wrap: wrap; }

        .footer-tag {
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid var(--border);
            font-size: 11px;
            color: var(--muted);
        }

        /* ══════════════ ANIMATIONS ══════════════ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        /* ══════════════ RESPONSIVE ══════════════ */
        @media (max-width: 900px) {
            nav           { padding: 0 20px; }
            section       { padding: 60px 20px; }
            .demo-section { padding: 60px 20px; }
            .demo-inner   { grid-template-columns: 1fr; gap: 40px; }
            .how-grid::before { display: none; }
            .cta-bottom   { padding: 70px 20px; }
            footer        { padding: 28px 20px; }
            .strat-inner  { grid-template-columns: 1fr !important; }
        }

        @media (max-width: 600px) {
            .nav-logo-text { display: none; }
            .hero          { padding: 70px 16px 60px; }
        }
    </style>
</head>
<body>

<canvas id="bgCanvas"></canvas>

<!-- ══════════════ TICKER BAR ══════════════ -->
<div class="ticker-bar">
    <div class="ticker-track">
        <span class="ticker-item"><span class="ticker-sym">BTCUSDT</span>&nbsp;<span class="ticker-price" id="t-BTC">—</span>&nbsp;<span class="ticker-chg" id="c-BTC"></span></span>
        <span class="ticker-item"><span class="ticker-sym">ETHUSDT</span>&nbsp;<span class="ticker-price" id="t-ETH">—</span>&nbsp;<span class="ticker-chg" id="c-ETH"></span></span>
        <span class="ticker-item"><span class="ticker-sym">SOLUSDT</span>&nbsp;<span class="ticker-price" id="t-SOL">—</span>&nbsp;<span class="ticker-chg" id="c-SOL"></span></span>
        <span class="ticker-item"><span class="ticker-sym">BNBUSDT</span>&nbsp;<span class="ticker-price" id="t-BNB">—</span>&nbsp;<span class="ticker-chg" id="c-BNB"></span></span>
        <span class="ticker-item"><span class="ticker-sym">XRPUSDT</span>&nbsp;<span class="ticker-price" id="t-XRP">—</span>&nbsp;<span class="ticker-chg" id="c-XRP"></span></span>
        <span class="ticker-item"><span class="ticker-sym">AVAXUSDT</span>&nbsp;<span class="ticker-price" id="t-AVAX">—</span>&nbsp;<span class="ticker-chg" id="c-AVAX"></span></span>
        <span class="ticker-item"><span class="ticker-sym">DOGEUSDT</span>&nbsp;<span class="ticker-price" id="t-DOGE">—</span>&nbsp;<span class="ticker-chg" id="c-DOGE"></span></span>
        <span class="ticker-item"><span class="ticker-sym">DOTUSDT</span>&nbsp;<span class="ticker-price" id="t-DOT">—</span>&nbsp;<span class="ticker-chg" id="c-DOT"></span></span>
        {{-- duplicate for seamless loop --}}
        <span class="ticker-item"><span class="ticker-sym">BTCUSDT</span>&nbsp;<span class="ticker-price">—</span></span>
        <span class="ticker-item"><span class="ticker-sym">ETHUSDT</span>&nbsp;<span class="ticker-price">—</span></span>
        <span class="ticker-item"><span class="ticker-sym">SOLUSDT</span>&nbsp;<span class="ticker-price">—</span></span>
        <span class="ticker-item"><span class="ticker-sym">BNBUSDT</span>&nbsp;<span class="ticker-price">—</span></span>
        <span class="ticker-item"><span class="ticker-sym">XRPUSDT</span>&nbsp;<span class="ticker-price">—</span></span>
        <span class="ticker-item"><span class="ticker-sym">AVAXUSDT</span>&nbsp;<span class="ticker-price">—</span></span>
        <span class="ticker-item"><span class="ticker-sym">DOGEUSDT</span>&nbsp;<span class="ticker-price">—</span></span>
        <span class="ticker-item"><span class="ticker-sym">DOTUSDT</span>&nbsp;<span class="ticker-price">—</span></span>
    </div>
</div>

<!-- ══════════════ NAVBAR ══════════════ -->
<nav>
    <a href="/" class="nav-logo">
        <img src="{{ asset('images/trading-helper-logo.png') }}" alt="Logo">
        <span class="nav-logo-text">TRADING HELPER</span>
    </a>
    <div class="nav-links">
        @auth
            <a href="{{ route('dashboard') }}" class="btn-ghost">Дашборд</a>
        @else
            <a href="{{ route('login') }}"    class="btn-ghost">Войти</a>
            <a href="{{ route('register') }}" class="btn-primary">Регистрация</a>
        @endauth
    </div>
</nav>

<!-- ══════════════ HERO ══════════════ -->
<div class="hero">
    <div class="hero-badge">
        <div class="dot"></div>
        EMA · RSI · MACD · Стратегии · Binance Futures
    </div>
    <h1>
        Платформа<br>
        <span class="grad">алготрейдинга</span><br>
        нового поколения
    </h1>
    <p class="hero-sub">
        Автоматические торговые сигналы на основе технического анализа,
        профессиональный терминал с TradingView, управление позициями
        на Binance Futures — всё в одном месте.
    </p>
    <div class="hero-cta">
        @auth
            <a href="{{ route('dashboard') }}"     class="cta-main">Открыть дашборд →</a>
            <a href="{{ route('trading.index') }}" class="cta-outline">Торговый терминал</a>
        @else
            <a href="{{ route('register') }}" class="cta-main">Начать бесплатно →</a>
            <a href="{{ route('login') }}"    class="cta-outline">Войти в аккаунт</a>
        @endauth
    </div>
</div>

<!-- ══════════════ STATS ══════════════ -->
@php
    $filteredSignalCount = \App\Models\CryptoSignalNew::query()
        ->whereNotNull('atr')
        ->whereNotNull('ema')
        ->whereNotNull('macd_histogram')
        ->whereNotNull('take_profit')
        ->where('price', '>', 0)
        ->where('atr', '>', 0)
        ->where(function ($q) {
            $q->where(function ($buy) {
                $buy->where('type', 'BUY')
                    ->whereRaw('macd < 0')
                    ->whereRaw('macd_histogram > 0')
                    ->whereBetween('rsi', [50, 60])
                    ->whereRaw('(atr / price) * 100 > 3.5')
                    ->whereRaw('ema < ema_slow');
            })->orWhere(function ($sell) {
                $sell->where('type', 'SELL')
                    ->whereRaw('macd < 0')
                    ->whereRaw('macd_histogram < 0')
                    ->whereBetween('rsi', [35, 40])
                    ->whereRaw('(atr / price) * 100 BETWEEN 0.3 AND 0.6')
                    ->whereRaw('ema > ema_slow');
            });
        })->count();
@endphp
<div class="stats-strip">
    <div class="stat-cell">
        <span class="stat-num" data-count="{{ $filteredSignalCount }}">0</span>
        <div class="stat-desc">Сигналов в базе</div>
    </div>
    <div class="stat-cell">
        <span class="stat-num" data-count="{{ \App\Models\CryptoNews::count() }}">0</span>
        <div class="stat-desc">Крипто-новостей</div>
    </div>
    <div class="stat-cell">
        <span class="stat-num">500+</span>
        <div class="stat-desc">Торговых пар</div>
    </div>
    <div class="stat-cell">
        <span class="stat-num">24/7</span>
        <div class="stat-desc">Мониторинг рынка</div>
    </div>
    <div class="stat-cell">
        <span class="stat-num">125×</span>
        <div class="stat-desc">Максимальное плечо</div>
    </div>
</div>

<!-- ══════════════ FEATURES ══════════════ -->
<section id="features">
    <div class="reveal">
        <span class="section-label">Возможности платформы</span>
        <h2 class="section-title">Всё что нужно<br>профессиональному трейдеру</h2>
        <p class="section-sub">
            Комплексная экосистема для алготрейдинга — от автоматической генерации
            сигналов до исполнения ордеров в один клик.
        </p>
    </div>
    <div class="features-grid">
        <div class="feature-card reveal">
            <div class="feature-icon fi-purple">📉</div>
            <div class="feature-title">Торговый терминал</div>
            <div class="feature-desc">
                Полноценный торговый интерфейс с графиком TradingView, живым стаканом
                ордеров и лентой сделок. Открывайте BUY/SELL позиции напрямую с Binance Futures.
            </div>
            <div class="feature-tags">
                <span class="tag">TradingView</span>
                <span class="tag">Order Book</span>
                <span class="tag">Market/Limit</span>
            </div>
        </div>

        <div class="feature-card reveal">
            <div class="feature-icon fi-green">⚡</div>
            <div class="feature-title">Сигналы EMA+RSI+MACD</div>
            <div class="feature-desc">
                Алгоритмические сигналы BUY/SELL на основе трёх классических индикаторов.
                Автоматический расчёт Take Profit и Stop Loss на основе ATR.
            </div>
            <div class="feature-tags">
                <span class="tag">EMA(20/50)</span>
                <span class="tag">RSI(14)</span>
                <span class="tag">MACD</span>
                <span class="tag">ATR</span>
            </div>
        </div>

        <div class="feature-card reveal">
            <div class="feature-icon fi-yellow">📊</div>
            <div class="feature-title">Аналитика и результаты</div>
            <div class="feature-desc">
                Ежедневная статистика по сигналам: DONE (TP достигнут), MISSED (SL сработал).
                P&amp;L расчёт с учётом плеча и комиссий.
            </div>
            <div class="feature-tags">
                <span class="tag">P&amp;L tracking</span>
                <span class="tag">Win rate</span>
                <span class="tag">Бэктест</span>
            </div>
        </div>

        <div class="feature-card reveal">
            <div class="feature-icon fi-pink">👤</div>
            <div class="feature-title">Управление профилями</div>
            <div class="feature-desc">
                Несколько API профилей Binance в одном месте. Раздельная PROD/TEST среда,
                управление позициями, открытые ордера и история сделок.
            </div>
            <div class="feature-tags">
                <span class="tag">Multi-account</span>
                <span class="tag">PROD / TEST</span>
                <span class="tag">Positions</span>
            </div>
        </div>

        <div class="feature-card reveal">
            <div class="feature-icon fi-blue">📰</div>
            <div class="feature-title">Крипто-новости</div>
            <div class="feature-desc">
                Агрегация свежих новостей криптовалютного рынка. Фильтрация по монете,
                поиск, связанные статьи. Всегда в курсе событий рынка.
            </div>
            <div class="feature-tags">
                <span class="tag">BTC/ETH/SOL</span>
                <span class="tag">Агрегация</span>
                <span class="tag">Связанные</span>
            </div>
        </div>

        <div class="feature-card reveal">
            <div class="feature-icon fi-red">🔒</div>
            <div class="feature-title">Безопасность и контроль</div>
            <div class="feature-desc">
                API ключи хранятся в зашифрованном виде. Раздельные тестовая и боевая среды.
                Кросс и изолированная маржа, настраиваемое плечо 1×–125×.
            </div>
            <div class="feature-tags">
                <span class="tag">Cross/Isolated</span>
                <span class="tag">1×–125× плечо</span>
                <span class="tag">TP/SL</span>
            </div>
        </div>

        <div class="feature-card reveal" style="border-color:rgba(6,182,212,.3)">
            <div class="feature-icon fi-cyan">🤖</div>
            <div class="feature-title">Конструктор стратегий</div>
            <div class="feature-desc">
                Создавайте собственные торговые стратегии с условиями на 25+ индикаторов.
                BUY и SELL в одной стратегии, бэктест на истории, запуск по всем парам сразу.
            </div>
            <div class="feature-tags">
                <span class="tag">25+ индикаторов</span>
                <span class="tag">Бэктест</span>
                <span class="tag">ALL pairs</span>
                <span class="tag">Auto-trade</span>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════ HOW IT WORKS ══════════════ -->
<div style="background:rgba(168,85,247,.04);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
<section>
    <div class="reveal" style="text-align:center;margin-bottom:56px">
        <span class="section-label">Как это работает</span>
        <h2 class="section-title">Четыре шага к прибыльной торговле</h2>
    </div>
    <div class="how-grid reveal">
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-title">Регистрация</div>
            <div class="step-desc">Создайте аккаунт и добавьте API ключи от Binance Futures в раздел профилей</div>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <div class="step-title">Сигналы</div>
            <div class="step-desc">Алгоритм анализирует рынок 24/7 и генерирует сигналы BUY/SELL с уровнями TP/SL</div>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <div class="step-title">Терминал</div>
            <div class="step-desc">Открывайте позиции через удобный терминал или настройте автоматическую торговлю</div>
        </div>
        <div class="step">
            <div class="step-num">4</div>
            <div class="step-title">Аналитика</div>
            <div class="step-desc">Отслеживайте P&L, анализируйте результаты и оптимизируйте стратегию</div>
        </div>
    </div>
</section>
</div>

<!-- ══════════════ STRATEGIES SHOWCASE ══════════════ -->
<div style="background:radial-gradient(ellipse at 50% 50%, rgba(6,182,212,.07) 0%, transparent 70%);border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:90px 40px">
<div style="max-width:1200px;margin:0 auto">

    <div class="reveal" style="text-align:center;margin-bottom:56px">
        <span class="section-label" style="color:#06b6d4">Конструктор стратегий</span>
        <h2 class="section-title">Ваши правила.<br><span style="background:linear-gradient(135deg,#06b6d4,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Ваши условия. Ваша стратегия.</span></h2>
        <p style="font-size:16px;color:var(--muted);max-width:600px;margin:0 auto;line-height:1.7">
            Больше не нужно ограничиваться фиксированным алгоритмом EMA+RSI+MACD.
            Собирайте собственные стратегии из любых индикаторов с логикой AND/OR.
        </p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:start" class="reveal strat-inner">

        {{-- Left: feature list --}}
        <div style="display:flex;flex-direction:column;gap:28px">
            <div style="display:flex;gap:16px;align-items:flex-start">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(6,182,212,.15);border:1px solid rgba(6,182,212,.3);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">📐</div>
                <div>
                    <div style="font-size:15px;font-weight:700;margin-bottom:5px">25+ индикаторов</div>
                    <div style="font-size:13px;color:var(--muted);line-height:1.6">RSI, EMA, MACD, Bollinger Bands, ATR, Stochastic, CCI, ADX, SuperTrend, VWAP, OBV и другие. Каждый с настраиваемыми параметрами.</div>
                </div>
            </div>
            <div style="display:flex;gap:16px;align-items:flex-start">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(168,85,247,.15);border:1px solid rgba(168,85,247,.3);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">⚖️</div>
                <div>
                    <div style="font-size:15px;font-weight:700;margin-bottom:5px">Логика AND / OR</div>
                    <div style="font-size:13px;color:var(--muted);line-height:1.6">Комбинируйте условия с логическими операторами. BUY и SELL условия задаются одновременно в одной стратегии.</div>
                </div>
            </div>
            <div style="display:flex;gap:16px;align-items:flex-start">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(14,203,129,.12);border:1px solid rgba(14,203,129,.25);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">📈</div>
                <div>
                    <div style="font-size:15px;font-weight:700;margin-bottom:5px">Бэктест на истории</div>
                    <div style="font-size:13px;color:var(--muted);line-height:1.6">Проверьте стратегию на до 500 исторических свечах. Получите win rate, P&L по месяцам и список всех сигналов.</div>
                </div>
            </div>
            <div style="display:flex;gap:16px;align-items:flex-start">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(240,185,11,.1);border:1px solid rgba(240,185,11,.25);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">🌐</div>
                <div>
                    <div style="font-size:15px;font-weight:700;margin-bottom:5px">Запуск по всем парам</div>
                    <div style="font-size:13px;color:var(--muted);line-height:1.6">Выберите «Все пары (ALL)» и стратегия автоматически проверит каждую из 300+ USDT Futures пар на Binance.</div>
                </div>
            </div>
            <div style="display:flex;gap:16px;align-items:flex-start">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(236,72,153,.12);border:1px solid rgba(236,72,153,.25);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">⚡</div>
                <div>
                    <div style="font-size:15px;font-weight:700;margin-bottom:5px">Telegram + Авто-трейд</div>
                    <div style="font-size:13px;color:var(--muted);line-height:1.6">Получайте уведомления в Telegram или включите авто-трейд — стратегия сама откроет позицию через ваш API профиль.</div>
                </div>
            </div>
        </div>

        {{-- Right: visual strategy card mockup --}}
        <div style="display:flex;flex-direction:column;gap:12px">

            {{-- Strategy header --}}
            <div style="background:rgba(6,182,212,.06);border:1px solid rgba(6,182,212,.25);border-radius:16px;padding:18px 20px;display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-size:15px;font-weight:700">RSI Oversold + MACD Cross</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:3px">BTCUSDT · 1h · ATR режим</div>
                </div>
                <span style="padding:4px 12px;border-radius:999px;background:rgba(14,203,129,.15);border:1px solid rgba(14,203,129,.3);color:#0ecb81;font-size:11px;font-weight:700">● Активна</span>
            </div>

            {{-- Conditions columns --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div style="background:rgba(14,203,129,.05);border:1px solid rgba(14,203,129,.2);border-radius:12px;padding:14px">
                    <div style="font-size:11px;font-weight:700;color:#0ecb81;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px">BUY условия</div>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <div style="background:rgba(14,203,129,.1);border:1px solid rgba(14,203,129,.2);border-radius:8px;padding:6px 10px;font-size:11px">RSI &lt; 35</div>
                        <div style="font-size:10px;font-weight:700;color:#a855f7;text-align:center">AND</div>
                        <div style="background:rgba(14,203,129,.1);border:1px solid rgba(14,203,129,.2);border-radius:8px;padding:6px 10px;font-size:11px">MACD hist &gt; 0</div>
                        <div style="font-size:10px;font-weight:700;color:#a855f7;text-align:center">AND</div>
                        <div style="background:rgba(14,203,129,.1);border:1px solid rgba(14,203,129,.2);border-radius:8px;padding:6px 10px;font-size:11px">EMA(20) &gt; EMA(50)</div>
                    </div>
                </div>
                <div style="background:rgba(246,70,93,.05);border:1px solid rgba(246,70,93,.2);border-radius:12px;padding:14px">
                    <div style="font-size:11px;font-weight:700;color:#f6465d;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px">SELL условия</div>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <div style="background:rgba(246,70,93,.1);border:1px solid rgba(246,70,93,.2);border-radius:8px;padding:6px 10px;font-size:11px">RSI &gt; 70</div>
                        <div style="font-size:10px;font-weight:700;color:#a855f7;text-align:center">OR</div>
                        <div style="background:rgba(246,70,93,.1);border:1px solid rgba(246,70,93,.2);border-radius:8px;padding:6px 10px;font-size:11px">MACD hist &lt; 0</div>
                        <div style="font-size:10px;font-weight:700;color:#a855f7;text-align:center">AND</div>
                        <div style="background:rgba(246,70,93,.1);border:1px solid rgba(246,70,93,.2);border-radius:8px;padding:6px 10px;font-size:11px">BB upper пробой</div>
                    </div>
                </div>
            </div>

            {{-- Backtest stats --}}
            <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px">
                <div style="font-size:11px;color:var(--muted);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px">Результаты бэктеста · 300 свечей</div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;text-align:center">
                    <div>
                        <div style="font-size:20px;font-weight:800;color:#a855f7">47</div>
                        <div style="font-size:10px;color:var(--muted)">Сигналов</div>
                    </div>
                    <div>
                        <div style="font-size:20px;font-weight:800;color:#0ecb81">31</div>
                        <div style="font-size:10px;color:var(--muted)">DONE</div>
                    </div>
                    <div>
                        <div style="font-size:20px;font-weight:800;color:#0ecb81">65.9%</div>
                        <div style="font-size:10px;color:var(--muted)">Win Rate</div>
                    </div>
                    <div>
                        <div style="font-size:20px;font-weight:800;color:#0ecb81">+38.4%</div>
                        <div style="font-size:10px;color:var(--muted)">P&L</div>
                    </div>
                </div>
            </div>

            {{-- Indicators list --}}
            <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 16px">
                <div style="font-size:11px;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px">Доступные индикаторы</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    @foreach(['RSI','EMA','MACD','ATR','BB','Stoch','CCI','ADX','SuperTrend','VWAP','OBV','WMA','SMA','CMF','MFI','ROC','SAR','Williams %R','Ichimoku','Keltner','Donchian'] as $ind)
                    <span style="padding:3px 9px;border-radius:999px;font-size:10px;font-weight:600;background:rgba(6,182,212,.1);color:#67e8f9;border:1px solid rgba(6,182,212,.2)">{{ $ind }}</span>
                    @endforeach
                    <span style="padding:3px 9px;border-radius:999px;font-size:10px;font-weight:600;background:rgba(168,85,247,.1);color:#c4b5fd;border:1px solid rgba(168,85,247,.2)">+ещё 4</span>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<!-- ══════════════ SIGNAL DEMO ══════════════ -->
<div class="demo-section">
    <div class="demo-inner">
        <div>
            <span class="section-label">Live сигналы</span>
            <h2 class="section-title" style="margin-bottom:14px">Реальные сигналы<br>в реальном времени</h2>
            <p style="color:var(--muted);font-size:15px;line-height:1.7;margin-bottom:28px">
                Система анализирует более 20 торговых пар на Binance Futures,
                вычисляет силу сигнала по балльной системе и отправляет
                уведомления в Telegram.
            </p>
            <div style="display:flex;flex-direction:column;gap:12px">
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted)">
                    <span style="color:var(--green)">✓</span> ATR-based Take Profit и Stop Loss
                </div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted)">
                    <span style="color:var(--green)">✓</span> Балльная система силы сигнала (STRONG / MEDIUM)
                </div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted)">
                    <span style="color:var(--green)">✓</span> Автоматическая проверка исполнения (DONE/MISSED)
                </div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted)">
                    <span style="color:var(--green)">✓</span> Бэктест на исторических данных Binance
                </div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted)">
                    <span style="color:var(--green)">✓</span> Telegram-уведомления мгновенно
                </div>
            </div>
        </div>

        <div class="demo-signals">
            @php
                try {
                    $demoSignals = \App\Models\CryptoSignalNew::query()
                        ->where('status', 'DONE')
                        ->whereNotNull('atr')
                        ->whereNotNull('ema')
                        ->whereNotNull('macd_histogram')
                        ->whereNotNull('take_profit')
                        ->where('price', '>', 0)
                        ->where('atr', '>', 0)
                        ->where(function ($q) {
                            $q->where(function ($buy) {
                                $buy->where('type', 'BUY')
                                    ->whereRaw('macd < 0')
                                    ->whereRaw('macd_histogram > 0')
                                    ->whereBetween('rsi', [50, 60])
                                    ->whereRaw('(atr / price) * 100 > 3.5')
                                    ->whereRaw('ema < ema_slow');
                            })
                            ->orWhere(function ($sell) {
                                $sell->where('type', 'SELL')
                                    ->whereRaw('macd < 0')
                                    ->whereRaw('macd_histogram < 0')
                                    ->whereBetween('rsi', [35, 40])
                                    ->whereRaw('(atr / price) * 100 BETWEEN 0.3 AND 0.6')
                                    ->whereRaw('ema > ema_slow');
                            });
                        })
                        ->orderBy('updated_at', 'desc')
                        ->limit(100)
                        ->get()
                        ->unique('symbol')
                        ->take(5);
                } catch (\Exception $e) {
                    $demoSignals = collect();
                }
            @endphp

            @forelse($demoSignals as $s)
                @php
                    $profitPct = $s->type === 'BUY'
                        ? ($s->take_profit - $s->price) / $s->price * 100
                        : ($s->price - $s->take_profit) / $s->price * 100;
                @endphp
                <div class="signal-row">
                    <span class="sig-type {{ $s->type === 'BUY' ? 'sig-buy' : 'sig-sell' }}">{{ $s->type }}</span>
                    <span class="sig-sym">{{ $s->symbol }}</span>
                    <span class="sig-price">{{ $s->price > 100 ? number_format($s->price, 2) : number_format($s->price, 4) }}</span>
                    <span style="font-size:12px;color:var(--green);font-weight:700">+{{ number_format($profitPct, 2) }}%</span>
                    <span style="font-size:11px;color:#475569">{{ $s->updated_at->diffForHumans() }}</span>
                    <span class="sig-status st-done">DONE ✓</span>
                </div>
            @empty
                <div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">
                    Нет завершённых сигналов
                </div>
            @endforelse

            <div class="ind-card" style="margin-top:8px">
                <div class="ind-header">
                    <span class="ind-name">RSI(14)</span>
                    <span class="ind-val" style="color:#f0b90b" id="rsiVal">54.8</span>
                </div>
                <div class="ind-bar-bg">
                    <div class="ind-bar" id="rsiBar" style="width:54.8%;background:linear-gradient(to right,#f0b90b,#f97316)"></div>
                </div>
            </div>
            <div class="ind-card">
                <div class="ind-header">
                    <span class="ind-name">MACD Histogram</span>
                    <span class="ind-val" style="color:#0ecb81" id="macdVal">+0.0024</span>
                </div>
                <div class="ind-bar-bg">
                    <div class="ind-bar" id="macdBar" style="width:62%;background:linear-gradient(to right,#0ecb81,#3b82f6)"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════ CTA BOTTOM ══════════════ -->
<div class="cta-bottom">
    <h2>
        Готовы начать торговать<br>
        <span style="background:linear-gradient(135deg,#a855f7,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">умнее и быстрее?</span>
    </h2>
    <p>Зарегистрируйтесь бесплатно и получите доступ ко всем инструментам платформы прямо сейчас.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        @auth
            <a href="{{ route('dashboard') }}" class="cta-main" style="padding:15px 36px;border-radius:12px;font-size:16px;font-weight:700;text-decoration:none;display:inline-block">Перейти в дашборд →</a>
        @else
            <a href="{{ route('register') }}" class="cta-main"   style="padding:15px 36px;border-radius:12px;font-size:16px;font-weight:700;text-decoration:none;display:inline-block">Создать аккаунт →</a>
            <a href="{{ route('login') }}"    class="cta-outline" style="padding:15px 36px;border-radius:12px;font-size:16px;font-weight:700;text-decoration:none;color:#c4b5fd;border:1px solid rgba(168,85,247,.4);background:rgba(168,85,247,.08);display:inline-block">Войти</a>
        @endauth
    </div>
</div>

<!-- ══════════════ FOOTER ══════════════ -->
<footer>
    <a href="/" class="footer-logo">
        <img src="{{ asset('images/trading-helper-logo.png') }}" alt="Logo">
        <span>Trading Helper © {{ now()->year }}</span>
    </a>
    <div class="footer-tags">
        <span class="footer-tag">EMA</span>
        <span class="footer-tag">RSI</span>
        <span class="footer-tag">MACD</span>
        <span class="footer-tag">Стратегии</span>
        <span class="footer-tag">Binance Futures</span>
        <span class="footer-tag">TradingView</span>
    </div>
    <span class="footer-copy">Для личного использования · Не является финансовым советом</span>
</footer>

<script>
// ══════════════ CANVAS BACKGROUND ══════════════
(function () {
    const canvas = document.getElementById('bgCanvas');
    const ctx    = canvas.getContext('2d');
    let W, H, candles = [], particles = [];

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    function genCandles() {
        candles = [];
        let price = 50 + Math.random() * 30;
        const n = Math.ceil(W / 18) + 2;
        for (let i = 0; i < n; i++) {
            const open  = price;
            const move  = (Math.random() - .48) * 6;
            const close = price + move;
            candles.push({ open, close, high: Math.max(open,close)+Math.random()*3, low: Math.min(open,close)-Math.random()*3 });
            price = close;
        }
    }
    genCandles();

    function initParticles() {
        particles = [];
        for (let i = 0, n = Math.floor(W/22); i < n; i++) {
            particles.push({ x: Math.random()*W, y: Math.random()*H, r: Math.random()*1.5+.3, vx: (Math.random()-.5)*.3, vy: (Math.random()-.5)*.3, a: Math.random()*.5+.1 });
        }
    }
    initParticles();

    let offset = 0;
    function draw() {
        ctx.clearRect(0, 0, W, H);

        ctx.strokeStyle = 'rgba(168,85,247,.05)'; ctx.lineWidth = 1;
        for (let y = 0; y < H; y += 60) { ctx.beginPath(); ctx.moveTo(0,y); ctx.lineTo(W,y); ctx.stroke(); }
        for (let x = 0; x < W; x += 80) { ctx.beginPath(); ctx.moveTo(x,0); ctx.lineTo(x,H); ctx.stroke(); }

        const cW=10, gap=7, total=cW+gap, scaleY=H*.35, baseY=H*.72;
        const minP=Math.min(...candles.map(c=>c.low)), maxP=Math.max(...candles.map(c=>c.high)), range=maxP-minP||1;

        candles.forEach((c,i) => {
            const x = i*total - (offset%total);
            const oY=baseY-((c.open-minP)/range)*scaleY, cY=baseY-((c.close-minP)/range)*scaleY;
            const hY=baseY-((c.high-minP)/range)*scaleY, lY=baseY-((c.low-minP)/range)*scaleY;
            const bull=c.close>=c.open, col=bull?'rgba(14,203,129,.35)':'rgba(246,70,93,.35)';
            ctx.strokeStyle=col; ctx.lineWidth=1;
            ctx.beginPath(); ctx.moveTo(x+cW/2,hY); ctx.lineTo(x+cW/2,lY); ctx.stroke();
            ctx.fillStyle=bull?'rgba(14,203,129,.2)':'rgba(246,70,93,.2)';
            const top=Math.min(oY,cY), ht=Math.abs(cY-oY)||1;
            ctx.fillRect(x,top,cW,ht); ctx.strokeRect(x,top,cW,ht);
        });

        particles.forEach(p => {
            p.x+=p.vx; p.y+=p.vy;
            if(p.x<0)p.x=W; if(p.x>W)p.x=0; if(p.y<0)p.y=H; if(p.y>H)p.y=0;
            ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
            ctx.fillStyle='rgba(168,85,247,'+p.a+')'; ctx.fill();
        });

        offset += .4;
        if (offset >= total) {
            offset = 0; candles.shift();
            const last=candles[candles.length-1], open=last.close, move=(Math.random()-.48)*6, close=open+move;
            candles.push({ open, close, high: Math.max(open,close)+Math.random()*3, low: Math.min(open,close)-Math.random()*3 });
        }
        requestAnimationFrame(draw);
    }
    draw();
})();

// ══════════════ LIVE TICKER ══════════════
const SYMS = ['BTCUSDT','ETHUSDT','SOLUSDT','BNBUSDT','XRPUSDT','AVAXUSDT','DOGEUSDT','DOTUSDT'];
const KEYS = ['BTC','ETH','SOL','BNB','XRP','AVAX','DOGE','DOT'];

async function fetchTicker() {
    try {
        const r   = await fetch('https://fapi.binance.com/fapi/v1/ticker/24hr');
        const all = await r.json();
        const map = {};
        all.forEach(d => { map[d.symbol] = d; });
        SYMS.forEach((sym, i) => {
            const d = map[sym]; if (!d) return;
            const pe = document.getElementById('t-'+KEYS[i]);
            const ce = document.getElementById('c-'+KEYS[i]);
            if (!pe || !ce) return;
            const price = parseFloat(d.lastPrice), chg = parseFloat(d.priceChangePercent);
            pe.textContent = price >= 1000
                ? price.toLocaleString('ru',{minimumFractionDigits:2,maximumFractionDigits:2})
                : price.toFixed(4);
            ce.textContent = (chg>=0?'+':'')+chg.toFixed(2)+'%';
            ce.className = 'ticker-chg '+(chg>=0?'up':'down');
        });
    } catch(e) {}
}
fetchTicker();
setInterval(fetchTicker, 8000);

// ══════════════ INDICATORS ANIMATION ══════════════
function animateIndicators() {
    const rsi  = 42 + Math.random() * 20;
    const macd = (Math.random() - .4) * .01;
    document.getElementById('rsiVal').textContent = rsi.toFixed(1);
    document.getElementById('rsiBar').style.width = rsi + '%';
    const mv = document.getElementById('macdVal');
    const mb = document.getElementById('macdBar');
    mv.textContent = (macd>=0?'+':'')+macd.toFixed(4);
    mv.style.color = macd>=0 ? '#0ecb81' : '#f6465d';
    mb.style.width  = (50+macd*5000)+'%';
    mb.style.background = macd>=0 ? 'linear-gradient(to right,#0ecb81,#3b82f6)' : 'linear-gradient(to right,#f6465d,#a855f7)';
}
setInterval(animateIndicators, 3000);

// ══════════════ COUNTER ANIMATION ══════════════
function animateCounters() {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count);
        if (!target || el.dataset.animated) return;
        el.dataset.animated = '1';
        let current = 0;
        const step = Math.max(1, Math.ceil(target/60));
        const timer = setInterval(() => {
            current = Math.min(current+step, target);
            el.textContent = current.toLocaleString('ru');
            if (current >= target) clearInterval(timer);
        }, 20);
    });
}

// ══════════════ REVEAL ON SCROLL ══════════════
const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: .12 });
document.querySelectorAll('.reveal').forEach(el => io.observe(el));

const statsIo = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) { animateCounters(); statsIo.disconnect(); }
}, { threshold: .1 });
const statsEl = document.querySelector('.stats-strip');
if (statsEl) statsIo.observe(statsEl);
</script>
</body>
</html>
