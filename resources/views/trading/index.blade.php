<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Trading Terminal – Trading Helper</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --bg:       #0b0e11;
            --bg2:      #161a1e;
            --bg3:      #1e2329;
            --border:   #2b3139;
            --accent:   #a855f7;
            --green:    #0ecb81;
            --red:      #f6465d;
            --text:     #eaecef;
            --muted:    #848e9c;
            --yellow:   #f0b90b;
        }

        html, body { height:100%; overflow:hidden; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 13px;
            display: flex;
            flex-direction: column;
        }

        /* ═══════════════════ TOP BAR ═══════════════════ */
        .topbar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 14px;
            height: 52px;
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            flex-wrap: nowrap;
            overflow: hidden;
        }

        .topbar-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .topbar-logo img {
            height: 30px;
            filter: drop-shadow(0 2px 6px rgba(248,113,113,.4));
        }

        .topbar-logo span {
            font-size: 13px;
            font-weight: 700;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .divider-v { width:1px; height:28px; background:var(--border); flex-shrink:0; }

        /* Symbol picker */
        .symbol-picker {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .symbol-select {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-size: 14px;
            font-weight: 700;
            padding: 5px 10px;
            cursor: pointer;
            outline: none;
        }

        .symbol-select:focus { border-color: var(--accent); }

        /* Market type toggle */
        .market-toggle {
            display: flex;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .market-toggle button {
            padding: 5px 12px;
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .market-toggle button.active {
            background: var(--accent);
            color: #fff;
        }

        /* Ticker strip */
        .ticker-strip {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
            overflow: hidden;
            padding: 0 8px;
        }

        .ticker-item { display:flex; flex-direction:column; align-items:flex-start; }
        .ticker-label { font-size:10px; color:var(--muted); }
        .ticker-value { font-size:13px; font-weight:600; }
        .ticker-value.up   { color: var(--green); }
        .ticker-value.down { color: var(--red); }
        .ticker-value.neutral { color: var(--text); }

        /* Profile selector */
        .profile-select-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .profile-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
        }

        .badge-prod { background: rgba(246,70,93,.15); color: var(--red); border:1px solid rgba(246,70,93,.3); }
        .badge-test { background: rgba(240,185,11,.12); color: var(--yellow); border:1px solid rgba(240,185,11,.3); }

        .topbar-select {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-size: 12px;
            padding: 5px 8px;
            cursor: pointer;
            outline: none;
        }

        .topbar-select:focus { border-color: var(--accent); }

        .nav-back {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            font-size: 12px;
            text-decoration: none;
            transition: all .2s;
            flex-shrink: 0;
        }

        .nav-back:hover { border-color: var(--accent); color: var(--text); }

        /* ═══════════════════ MAIN AREA ═══════════════════ */
        .trading-main {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Left column */
        .col-left {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
            min-width: 0;
        }

        /* Chart */
        .chart-wrap {
            flex: 1;
            min-height: 0;
            position: relative;
        }

        #tv-chart-container {
            width: 100%;
            height: 100%;
        }

        /* Middle row: orderbook + trades */
        .market-data-row {
            display: flex;
            height: 200px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        .panel {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .panel + .panel { border-left: 1px solid var(--border); }

        .panel-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            padding: 6px 10px;
            border-bottom: 1px solid var(--border);
            text-transform: uppercase;
            letter-spacing: .5px;
            flex-shrink: 0;
        }

        .panel-body {
            overflow-y: auto;
            flex: 1;
            padding: 0;
        }

        /* Order book */
        .ob-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            padding: 4px 10px;
            font-size: 10px;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }

        .ob-header span:nth-child(2), .ob-row span:nth-child(2) { text-align: center; }
        .ob-header span:last-child, .ob-row span:last-child { text-align: right; }

        .ob-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            padding: 2px 10px;
            font-size: 11px;
            position: relative;
            cursor: pointer;
        }

        .ob-row:hover { background: rgba(255,255,255,.03); }

        .ob-row .ob-bar {
            position: absolute;
            top:0; right:0; bottom:0;
            opacity: .12;
            pointer-events: none;
        }

        .ob-ask .ob-bar { background: var(--red); }
        .ob-bid .ob-bar { background: var(--green); }
        .ob-ask span:first-child { color: var(--red); }
        .ob-bid span:first-child { color: var(--green); }

        .ob-spread {
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }

        /* Recent trades */
        .rt-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            padding: 4px 10px;
            font-size: 10px;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }

        .rt-header span:nth-child(2), .rt-row span:nth-child(2) { text-align: center; }
        .rt-header span:last-child, .rt-row span:last-child { text-align: right; }

        .rt-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            padding: 2px 10px;
            font-size: 11px;
        }

        /* ═══════════════════ ORDER FORM ═══════════════════ */
        .col-right {
            width: 300px;
            flex-shrink: 0;
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background: var(--bg2);
        }

        .order-form { padding: 12px; display: flex; flex-direction: column; gap: 10px; }

        /* Market / Futures toggle in form */
        .form-toggle-row {
            display: flex;
            gap: 6px;
        }

        .form-toggle-row .tog-btn {
            flex: 1;
            padding: 6px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            text-align: center;
        }

        .form-toggle-row .tog-btn.active {
            background: rgba(168,85,247,.2);
            border-color: var(--accent);
            color: var(--accent);
        }

        /* Margin type */
        .margin-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .margin-row label { font-size: 11px; color: var(--muted); }

        .margin-toggle {
            display: flex;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 5px;
            overflow: hidden;
        }

        .margin-toggle button {
            padding: 4px 10px;
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .margin-toggle button.active {
            background: rgba(168,85,247,.25);
            color: var(--accent);
        }

        /* Leverage */
        .leverage-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .leverage-row label { font-size: 11px; color: var(--muted); }

        .leverage-control {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .lev-btn {
            width: 22px; height: 22px;
            border-radius: 4px;
            border: 1px solid var(--border);
            background: var(--bg3);
            color: var(--text);
            font-size: 14px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all .2s;
        }

        .lev-btn:hover { border-color: var(--accent); color: var(--accent); }

        .lev-display {
            min-width: 42px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: var(--yellow);
            padding: 2px 6px;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 4px;
        }

        /* Buy/Sell tabs */
        .side-tabs {
            display: flex;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .side-tab {
            flex: 1;
            padding: 8px;
            border: none;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            text-align: center;
        }

        .side-tab.buy  { background: rgba(14,203,129,.12); color: var(--green); }
        .side-tab.sell { background: rgba(246,70,93,.12);  color: var(--red); }
        .side-tab.buy.active  { background: var(--green); color: #fff; }
        .side-tab.sell.active { background: var(--red);   color: #fff; }

        /* Order type */
        .order-type-tabs {
            display: flex;
            gap: 4px;
        }

        .ot-tab {
            flex: 1;
            padding: 5px;
            border-radius: 5px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all .2s;
        }

        .ot-tab.active {
            background: var(--bg3);
            border-color: var(--accent);
            color: var(--text);
        }

        /* Form fields */
        .field { display: flex; flex-direction: column; gap: 4px; }
        .field label { font-size: 11px; color: var(--muted); }

        .field-input-wrap {
            display: flex;
            align-items: center;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            transition: border-color .2s;
        }

        .field-input-wrap:focus-within { border-color: var(--accent); }

        .field-input-wrap input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text);
            font-size: 13px;
            padding: 7px 10px;
            outline: none;
            width: 100%;
        }

        .field-unit {
            padding: 0 8px;
            font-size: 11px;
            color: var(--muted);
            white-space: nowrap;
        }

        /* Percent buttons */
        .pct-row {
            display: flex;
            gap: 4px;
        }

        .pct-btn {
            flex: 1;
            padding: 5px 0;
            border-radius: 5px;
            border: 1px solid var(--border);
            background: var(--bg3);
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all .2s;
        }

        .pct-btn:hover { border-color: var(--accent); color: var(--accent); }

        /* TP/SL toggle */
        .tpsl-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            padding: 4px 0;
        }

        .tpsl-toggle span { font-size: 11px; color: var(--muted); }

        .tpsl-toggle .toggle-switch {
            width: 32px; height: 17px;
            background: var(--border);
            border-radius: 999px;
            position: relative;
            transition: background .2s;
        }

        .tpsl-toggle .toggle-switch.on { background: var(--accent); }

        .tpsl-toggle .toggle-knob {
            position: absolute;
            top: 2px; left: 2px;
            width: 13px; height: 13px;
            background: #fff;
            border-radius: 50%;
            transition: left .2s;
        }

        .tpsl-toggle .toggle-switch.on .toggle-knob { left: 17px; }

        .tpsl-fields { display: flex; flex-direction: column; gap: 6px; }

        /* Order summary */
        .order-summary {
            background: var(--bg3);
            border-radius: 8px;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }

        .summary-row .s-label { color: var(--muted); }
        .summary-row .s-value { color: var(--text); font-weight: 600; }

        /* Submit button */
        .submit-btn {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all .25s;
            letter-spacing: .3px;
        }

        .submit-btn.buy  { background: var(--green); color: #fff; }
        .submit-btn.sell { background: var(--red);   color: #fff; }
        .submit-btn:hover { opacity: .88; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,.4); }
        .submit-btn:active { transform: translateY(0); }

        /* ═══════════════════ BOTTOM TABS ═══════════════════ */
        .bottom-panel {
            height: 180px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            background: var(--bg2);
        }

        .bottom-tabs {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .bottom-tab {
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all .2s;
            white-space: nowrap;
        }

        .bottom-tab.active {
            color: var(--text);
            border-bottom-color: var(--accent);
        }

        .bottom-tab:hover { color: var(--text); }

        .bottom-content {
            flex: 1;
            overflow-y: auto;
        }

        .bottom-pane { display:none; padding: 6px 12px; }
        .bottom-pane.active { display:block; }

        .positions-table, .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .positions-table th, .orders-table th {
            text-align: left;
            color: var(--muted);
            padding: 4px 8px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            border-bottom: 1px solid var(--border);
        }

        .positions-table td, .orders-table td {
            padding: 5px 8px;
            border-bottom: 1px solid rgba(43,49,57,.5);
        }

        .positions-table tr:hover td, .orders-table tr:hover td {
            background: rgba(255,255,255,.02);
        }

        .empty-row td {
            text-align: center;
            color: var(--muted);
            padding: 20px;
        }

        /* Toast notification */
        .toast-wrap {
            position: fixed;
            top: 60px;
            right: 16px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }

        .toast {
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            min-width: 220px;
            box-shadow: 0 8px 24px rgba(0,0,0,.5);
            animation: slideIn .3s ease-out;
            pointer-events: auto;
        }

        .toast.success { background: rgba(14,203,129,.15); border: 1px solid rgba(14,203,129,.4); color: var(--green); }
        .toast.error   { background: rgba(246,70,93,.15);  border: 1px solid rgba(246,70,93,.4);  color: var(--red); }
        .toast.info    { background: rgba(168,85,247,.15); border: 1px solid rgba(168,85,247,.4); color: var(--accent); }

        @keyframes slideIn {
            from { opacity:0; transform:translateX(20px); }
            to   { opacity:1; transform:translateX(0); }
        }

        /* Loading overlay */
        .btn-loading { opacity: .6; pointer-events: none; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        /* Responsive: hide market-data-row on small screens */
        @media (max-width: 900px) {
            .col-right { width: 260px; }
            .market-data-row { display: none; }
        }

        /* ═══════════════════ MODALS ═══════════════════ */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 10000;
            background: rgba(0,0,0,.65);
            backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none;
            transition: opacity .2s;
        }
        .modal-overlay.open {
            opacity: 1; pointer-events: auto;
        }
        .modal {
            background: #13102a;
            border: 1px solid rgba(168,85,247,.25);
            border-radius: 16px;
            padding: 28px 28px 22px;
            width: 360px;
            max-width: 90vw;
            box-shadow: 0 24px 64px rgba(0,0,0,.7), 0 0 0 1px rgba(168,85,247,.1);
            transform: translateY(12px) scale(.97);
            transition: transform .2s;
        }
        .modal-overlay.open .modal {
            transform: translateY(0) scale(1);
        }
        .modal-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin-bottom: 16px;
        }
        .modal-icon.danger  { background: rgba(246,70,93,.15);  border: 1px solid rgba(246,70,93,.25); }
        .modal-icon.warning { background: rgba(240,185,11,.12); border: 1px solid rgba(240,185,11,.25); }
        .modal-icon.info    { background: rgba(168,85,247,.15); border: 1px solid rgba(168,85,247,.25); }
        .modal-title {
            font-size: 16px; font-weight: 700; color: #f1f5f9; margin-bottom: 8px;
        }
        .modal-body {
            font-size: 13px; color: #94a3b8; line-height: 1.6; margin-bottom: 20px;
        }
        .modal-body strong { color: #f1f5f9; }
        .modal-detail {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
            border-radius: 8px; padding: 10px 14px; margin-top: 10px;
            display: flex; flex-direction: column; gap: 6px;
        }
        .modal-detail-row {
            display: flex; justify-content: space-between; font-size: 12px;
        }
        .modal-detail-row .md-label { color: #64748b; }
        .modal-detail-row .md-value { color: #f1f5f9; font-weight: 600; }
        .modal-detail-row .md-value.green { color: var(--green); }
        .modal-detail-row .md-value.red   { color: var(--red); }
        .modal-actions {
            display: flex; gap: 10px;
        }
        .modal-btn {
            flex: 1; padding: 10px; border-radius: 8px; font-size: 13px;
            font-weight: 700; cursor: pointer; border: none; transition: all .2s;
        }
        .modal-btn.cancel {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: #94a3b8;
        }
        .modal-btn.cancel:hover { background: rgba(255,255,255,.1); color: #f1f5f9; }
        .modal-btn.danger {
            background: rgba(246,70,93,.2);
            border: 1px solid rgba(246,70,93,.4);
            color: var(--red);
        }
        .modal-btn.danger:hover { background: rgba(246,70,93,.35); }
        .modal-btn.confirm-buy {
            background: rgba(14,203,129,.2);
            border: 1px solid rgba(14,203,129,.4);
            color: var(--green);
        }
        .modal-btn.confirm-buy:hover { background: rgba(14,203,129,.35); }
        .modal-btn.confirm-sell {
            background: rgba(246,70,93,.2);
            border: 1px solid rgba(246,70,93,.4);
            color: var(--red);
        }
        .modal-btn.confirm-sell:hover { background: rgba(246,70,93,.35); }
    </style>
</head>
<body>

<!-- ═══════════════ TOP BAR ═══════════════ -->
<div class="topbar">
    <a href="{{ route('dashboard') }}" class="topbar-logo">
        <img src="{{ asset('images/trading-helper-logo.png') }}" alt="Logo">
        <span>TRADING HELPER</span>
    </a>

    <div class="divider-v"></div>

    <!-- Symbol picker -->
    <div class="symbol-picker">
        <select class="symbol-select" id="symbolSelect" onchange="changeSymbol(this.value)">
            @foreach($popularSymbols as $s)
                <option value="{{ $s }}" {{ $symbol === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
    </div>

    <!-- Market toggle -->
    <div class="market-toggle">
        <button id="btnFutures" class="{{ $market==='futures'?'active':'' }}" onclick="setMarket('futures')">Futures</button>
        <button id="btnSpot"    class="{{ $market==='spot'?'active':'' }}"    onclick="setMarket('spot')">Spot</button>
    </div>

    <!-- Ticker strip (static placeholder) -->
    <div class="ticker-strip">
        <div class="ticker-item">
            <span class="ticker-label">Последняя цена</span>
            <span class="ticker-value neutral" id="tickerPrice">—</span>
        </div>
        <div class="ticker-item">
            <span class="ticker-label">24ч Изм.</span>
            <span class="ticker-value" id="tickerChange">—</span>
        </div>
        <div class="ticker-item">
            <span class="ticker-label">24ч Макс.</span>
            <span class="ticker-value neutral" id="tickerHigh">—</span>
        </div>
        <div class="ticker-item">
            <span class="ticker-label">24ч Мин.</span>
            <span class="ticker-value neutral" id="tickerLow">—</span>
        </div>
        <div class="ticker-item">
            <span class="ticker-label">24ч Объём</span>
            <span class="ticker-value neutral" id="tickerVol">—</span>
        </div>
        <div class="ticker-item">
            <span class="ticker-label">Фандинг / Осталось</span>
            <span class="ticker-value neutral" id="tickerFunding">—</span>
        </div>
    </div>

    <div class="divider-v"></div>

    <!-- Profile selector -->
    <div class="profile-select-wrap">
        <span style="font-size:11px;color:var(--muted)">Профиль:</span>
        <select class="topbar-select" id="profileSelect" onchange="changeProfile(this.value)">
            @if($profiles->isEmpty())
                <option value="">Нет профилей</option>
            @else
                @foreach($profiles as $p)
                    <option value="{{ $p->id }}" {{ $selectedProfile?->id == $p->id ? 'selected' : '' }}>
                        {{ $p->profile_name }} ({{ $p->category }})
                    </option>
                @endforeach
            @endif
        </select>
        @if($selectedProfile)
            <span class="profile-badge {{ $selectedProfile->category==='PROD'?'badge-prod':'badge-test' }}">
                {{ $selectedProfile->category }}
            </span>
        @endif
    </div>

    <a href="{{ route('dashboard') }}" class="nav-back">← Главная</a>
</div>

<!-- ═══════════════ MAIN ═══════════════ -->
<div class="trading-main">

    <!-- LEFT: Chart + Orderbook/Trades -->
    <div class="col-left">
        <div class="chart-wrap">
            <div id="tv-chart-container"></div>
        </div>

        <div class="market-data-row">
            <!-- Order Book -->
            <div class="panel">
                <div class="panel-title">Стакан ордеров</div>
                <div class="ob-header">
                    <span>Цена (USDT)</span>
                    <span>Кол-во</span>
                    <span>Сумма</span>
                </div>
                <div class="panel-body" id="orderbookBody">
                    <!-- ASK rows (red) -->
                    <div id="askRows"></div>
                    <div class="ob-spread" id="spreadRow">— Spread —</div>
                    <!-- BID rows (green) -->
                    <div id="bidRows"></div>
                </div>
            </div>

            <!-- Recent Trades -->
            <div class="panel">
                <div class="panel-title">Последние сделки</div>
                <div class="rt-header">
                    <span>Цена</span>
                    <span>Кол-во</span>
                    <span>Время</span>
                </div>
                <div class="panel-body">
                    <div id="recentTrades"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Order Form -->
    <div class="col-right">
        <div class="order-form">

            <!-- Futures / Spot label -->
            <div class="form-toggle-row">
                <div class="tog-btn active" id="formMarketLabel">Futures Perpetual</div>
            </div>

            @if($profiles->isEmpty())
                <div style="color:var(--red);font-size:12px;padding:8px;background:rgba(246,70,93,.1);border-radius:6px;border:1px solid rgba(246,70,93,.2)">
                    Нет профилей. <a href="{{ route('profiles.create') }}" style="color:var(--accent)">Добавить профиль →</a>
                </div>
            @endif

            <!-- Margin type -->
            <div class="margin-row">
                <label>Тип маржи</label>
                <div class="margin-toggle">
                    <button id="btnCross"    class="active" onclick="setMarginType('CROSSED')">Кросс</button>
                    <button id="btnIsolated"             onclick="setMarginType('ISOLATED')">Изолир.</button>
                </div>
            </div>

            <!-- Leverage -->
            <div class="leverage-row">
                <label>Плечо</label>
                <div class="leverage-control">
                    <button class="lev-btn" onclick="changeLev(-1)">−</button>
                    <div class="lev-display" id="levDisplay">20×</div>
                    <button class="lev-btn" onclick="changeLev(+1)">+</button>
                </div>
            </div>

            <!-- Leverage presets -->
            <div class="pct-row">
                @foreach([1,5,10,20,50,75,100,125] as $lv)
                <button class="pct-btn" onclick="setLev({{ $lv }})">{{ $lv }}×</button>
                @endforeach
            </div>

            <!-- Buy / Sell -->
            <div class="side-tabs">
                <button class="side-tab buy active" id="btnBuy"  onclick="setSide('BUY')">BUY / LONG</button>
                <button class="side-tab sell"        id="btnSell" onclick="setSide('SELL')">SELL / SHORT</button>
            </div>

            <!-- Order type -->
            <div class="order-type-tabs">
                <button class="ot-tab active" id="tabMarket" onclick="setOrderType('MARKET')">Market</button>
                <button class="ot-tab"        id="tabLimit"  onclick="setOrderType('LIMIT')">Limit</button>
                <button class="ot-tab"        id="tabStopLimit" onclick="setOrderType('STOP_LIMIT')">Stop-Limit</button>
            </div>

            <!-- Price (for Limit / Stop-Limit) -->
            <div class="field" id="fieldPrice" style="display:none">
                <label>Цена</label>
                <div class="field-input-wrap">
                    <input type="number" id="inputPrice" placeholder="0.00" step="any" oninput="recalcTotal()">
                    <span class="field-unit">USDT</span>
                </div>
            </div>

            <!-- Stop price (for Stop-Limit) -->
            <div class="field" id="fieldStopPrice" style="display:none">
                <label>Стоп-цена</label>
                <div class="field-input-wrap">
                    <input type="number" id="inputStopPrice" placeholder="0.00" step="any">
                    <span class="field-unit">USDT</span>
                </div>
            </div>

            <!-- Quantity -->
            <div class="field">
                <label>Количество (<span id="baseAssetLabel">BTC</span>)</label>
                <div class="field-input-wrap">
                    <input type="number" id="inputQty" placeholder="0.000" step="any" oninput="recalcTotal()">
                    <span class="field-unit" id="qtyUnitLabel">BTC</span>
                </div>
            </div>

            <!-- Percent buttons -->
            <div class="pct-row">
                <button class="pct-btn" onclick="setPct(25)">25%</button>
                <button class="pct-btn" onclick="setPct(50)">50%</button>
                <button class="pct-btn" onclick="setPct(75)">75%</button>
                <button class="pct-btn" onclick="setPct(100)">100%</button>
            </div>

            <!-- USDT amount shortcut -->
            <div class="field">
                <label>Или введите сумму в USDT</label>
                <div class="field-input-wrap">
                    <input type="number" id="inputUsdt" placeholder="10.00" step="any" oninput="calcQtyFromUsdt()">
                    <span class="field-unit">USDT</span>
                </div>
            </div>

            <!-- TP/SL toggle -->
            <div class="tpsl-toggle" onclick="toggleTpSl()">
                <span>Take Profit / Stop Loss</span>
                <div class="toggle-switch" id="tpslSwitch">
                    <div class="toggle-knob"></div>
                </div>
            </div>

            <div class="tpsl-fields" id="tpslFields" style="display:none">
                <div class="field">
                    <label>Take Profit (цена)</label>
                    <div class="field-input-wrap">
                        <input type="number" id="inputTp" placeholder="0.00" step="any">
                        <span class="field-unit">USDT</span>
                    </div>
                </div>
                <div class="field">
                    <label>Stop Loss (цена)</label>
                    <div class="field-input-wrap">
                        <input type="number" id="inputSl" placeholder="0.00" step="any">
                        <span class="field-unit">USDT</span>
                    </div>
                </div>
            </div>

            <!-- Order summary -->
            <div class="order-summary">
                <div class="summary-row">
                    <span class="s-label">Стоимость ордера</span>
                    <span class="s-value" id="summaryTotal">— USDT</span>
                </div>
                <div class="summary-row">
                    <span class="s-label">Требуемая маржа</span>
                    <span class="s-value" id="summaryMargin">— USDT</span>
                </div>
                <div class="summary-row">
                    <span class="s-label">Комиссия (~0.04%)</span>
                    <span class="s-value" id="summaryFee">— USDT</span>
                </div>
            </div>

            <!-- Submit -->
            <button class="submit-btn buy" id="submitBtn" onclick="submitOrder()">
                Открыть BUY / LONG
            </button>

        </div>
    </div>
</div>

<!-- ═══════════════ BOTTOM ═══════════════ -->
<div class="bottom-panel">
    <div class="bottom-tabs">
        <div class="bottom-tab active" onclick="switchBottomTab('positions', this)">Позиции</div>
        <div class="bottom-tab" onclick="switchBottomTab('orders', this)">Открытые ордера</div>
        <div class="bottom-tab" onclick="switchBottomTab('history', this)">История сделок</div>
        <div class="bottom-tab" onclick="switchBottomTab('pnl', this)">PnL</div>
    </div>
    <div class="bottom-content">
        <!-- Positions -->
        <div class="bottom-pane active" id="pane-positions">
            <table class="positions-table">
                <thead>
                    <tr>
                        <th>Символ</th><th>Сторона</th><th>Размер</th>
                        <th>Цена входа</th><th>Марк. цена</th>
                        <th>Лик. цена</th><th>Маржа</th>
                        <th>Нереализ. PnL</th><th>ROE%</th><th>Действия</th>
                    </tr>
                </thead>
                <tbody id="positionsBody">
                    <tr class="empty-row"><td colspan="10">Нет открытых позиций</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Open Orders -->
        <div class="bottom-pane" id="pane-orders">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Время</th><th>Символ</th><th>Тип</th><th>Сторона</th>
                        <th>Цена</th><th>Кол-во</th><th>Исполнено</th><th>Статус</th><th>Действия</th>
                    </tr>
                </thead>
                <tbody id="ordersBody">
                    <tr class="empty-row"><td colspan="9">Нет открытых ордеров</td></tr>
                </tbody>
            </table>
        </div>

        <!-- History -->
        <div class="bottom-pane" id="pane-history">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Время</th><th>Символ</th><th>Тип</th><th>Сторона</th>
                        <th>Цена</th><th>Кол-во</th><th>Реализ. PnL</th><th>Комиссия</th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                    <tr class="empty-row"><td colspan="8">История пуста — выберите профиль</td></tr>
                </tbody>
            </table>
        </div>

        <!-- PnL -->
        <div class="bottom-pane" id="pane-pnl">
            <table class="orders-table">
                <thead>
                    <tr><th>Дата</th><th>Тип</th><th>Символ</th><th>Сумма</th></tr>
                </thead>
                <tbody id="pnlBody">
                    <tr class="empty-row"><td colspan="4">Нет данных PnL — выберите профиль</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-wrap" id="toastWrap"></div>

<!-- ═══ MODAL: Confirm close position ═══ -->
<div class="modal-overlay" id="modalClosePos">
    <div class="modal">
        <div class="modal-icon danger">⚠</div>
        <div class="modal-title">Закрыть позицию</div>
        <div class="modal-body">
            Вы уверены, что хотите закрыть позицию?
            <div class="modal-detail">
                <div class="modal-detail-row">
                    <span class="md-label">Символ</span>
                    <span class="md-value" id="mcp-symbol">—</span>
                </div>
                <div class="modal-detail-row">
                    <span class="md-label">Сторона</span>
                    <span class="md-value" id="mcp-side">—</span>
                </div>
                <div class="modal-detail-row">
                    <span class="md-label">Объём</span>
                    <span class="md-value" id="mcp-qty">—</span>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button class="modal-btn cancel" onclick="closeModal('modalClosePos')">Отмена</button>
            <button class="modal-btn danger" id="mcp-confirm">Закрыть позицию</button>
        </div>
    </div>
</div>

<!-- ═══ MODAL: Confirm cancel order ═══ -->
<div class="modal-overlay" id="modalCancelOrder">
    <div class="modal">
        <div class="modal-icon warning">✕</div>
        <div class="modal-title">Отменить ордер</div>
        <div class="modal-body">
            Вы уверены, что хотите отменить ордер?
            <div class="modal-detail">
                <div class="modal-detail-row">
                    <span class="md-label">Символ</span>
                    <span class="md-value" id="mco-symbol">—</span>
                </div>
                <div class="modal-detail-row">
                    <span class="md-label">ID ордера</span>
                    <span class="md-value" id="mco-id">—</span>
                </div>
                <div class="modal-detail-row">
                    <span class="md-label">Тип</span>
                    <span class="md-value" id="mco-type">—</span>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button class="modal-btn cancel" onclick="closeModal('modalCancelOrder')">Оставить</button>
            <button class="modal-btn danger" id="mco-confirm">Отменить ордер</button>
        </div>
    </div>
</div>

<!-- ═══ MODAL: Confirm submit order ═══ -->
<div class="modal-overlay" id="modalSubmitOrder">
    <div class="modal">
        <div class="modal-icon info">📋</div>
        <div class="modal-title" id="mso-title">Подтвердить ордер</div>
        <div class="modal-body">
            Проверьте параметры перед отправкой:
            <div class="modal-detail">
                <div class="modal-detail-row">
                    <span class="md-label">Пара</span>
                    <span class="md-value" id="mso-symbol">—</span>
                </div>
                <div class="modal-detail-row">
                    <span class="md-label">Сторона</span>
                    <span class="md-value" id="mso-side">—</span>
                </div>
                <div class="modal-detail-row">
                    <span class="md-label">Тип</span>
                    <span class="md-value" id="mso-type">—</span>
                </div>
                <div class="modal-detail-row">
                    <span class="md-label">Количество</span>
                    <span class="md-value" id="mso-qty">—</span>
                </div>
                <div class="modal-detail-row">
                    <span class="md-label">Стоимость</span>
                    <span class="md-value" id="mso-total">—</span>
                </div>
                <div class="modal-detail-row">
                    <span class="md-label">Плечо</span>
                    <span class="md-value" id="mso-lev">—</span>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button class="modal-btn cancel" onclick="closeModal('modalSubmitOrder')">Отмена</button>
            <button class="modal-btn confirm-buy" id="mso-confirm">Подтвердить</button>
        </div>
    </div>
</div>

<!-- TradingView -->
<script src="https://s3.tradingview.com/tv.js"></script>
<script>
// ═══ STATE ═══
const STATE = {
    symbol:     '{{ $symbol }}',
    market:     '{{ $market }}',
    profileId:  '{{ $selectedProfile?->id ?? "" }}',
    side:       'BUY',
    orderType:  'MARKET',
    marginType: 'CROSSED',
    leverage:   20,
    tpslOpen:   false,
    lastPrice:  0,
};

const PROFILES = {!! $profilesJson !!};

// ═══ TRADINGVIEW WIDGET ═══
let tvWidget = null;

function buildTvSymbol() {
    const base = 'BINANCE:' + STATE.symbol;
    return STATE.market === 'futures' ? base + '.P' : base;
}

function initChart() {
    if (tvWidget) {
        tvWidget.remove();
        tvWidget = null;
    }
    const container = document.getElementById('tv-chart-container');
    container.innerHTML = '';
    tvWidget = new TradingView.widget({
        autosize:          true,
        symbol:            buildTvSymbol(),
        interval:          '15',
        timezone:          'Europe/Moscow',
        theme:             'dark',
        style:             '1',
        locale:            'ru',
        toolbar_bg:        '#161a1e',
        enable_publishing: false,
        hide_side_toolbar: false,
        allow_symbol_change: true,
        save_image:        false,
        container_id:      'tv-chart-container',
        withdateranges:    true,
        hide_top_toolbar:  false,
        studies:           ['RSI@tv-studioes', 'MACD@tv-studioes'],
    });
}

// ═══ MARKET DATA (Binance public API) ═══
async function fetchTicker() {
    try {
        const endpoint = STATE.market === 'futures'
            ? `https://fapi.binance.com/fapi/v1/ticker/24hr?symbol=${STATE.symbol}`
            : `https://api.binance.com/api/v3/ticker/24hr?symbol=${STATE.symbol}`;
        const r = await fetch(endpoint);
        const d = await r.json();
        STATE.lastPrice = parseFloat(d.lastPrice || d.markPrice || 0);
        document.getElementById('tickerPrice').textContent  = fmtPrice(STATE.lastPrice);
        const chg = parseFloat(d.priceChangePercent);
        const el = document.getElementById('tickerChange');
        el.textContent = (chg >= 0 ? '+' : '') + chg.toFixed(2) + '%';
        el.className = 'ticker-value ' + (chg >= 0 ? 'up' : 'down');
        document.getElementById('tickerHigh').textContent = fmtPrice(parseFloat(d.highPrice));
        document.getElementById('tickerLow').textContent  = fmtPrice(parseFloat(d.lowPrice));
        document.getElementById('tickerVol').textContent  = fmtVol(parseFloat(d.volume));
    } catch(e) {}

    if (STATE.market === 'futures') {
        try {
            const r2 = await fetch(`https://fapi.binance.com/fapi/v1/premiumIndex?symbol=${STATE.symbol}`);
            const d2 = await r2.json();
            const rate = (parseFloat(d2.lastFundingRate) * 100).toFixed(4);
            const nextMs = parseInt(d2.nextFundingTime) - Date.now();
            const h = Math.floor(nextMs/3600000);
            const m = Math.floor((nextMs%3600000)/60000);
            document.getElementById('tickerFunding').textContent = `${rate}% / ${h}ч ${m}м`;
        } catch(e) {}
    } else {
        document.getElementById('tickerFunding').textContent = '—';
    }
}

// ═══ ORDER BOOK (Binance public) ═══
async function fetchOrderBook() {
    try {
        const endpoint = STATE.market === 'futures'
            ? `https://fapi.binance.com/fapi/v1/depth?symbol=${STATE.symbol}&limit=10`
            : `https://api.binance.com/api/v3/depth?symbol=${STATE.symbol}&limit=10`;
        const r = await fetch(endpoint);
        const d = await r.json();
        const asks = (d.asks || []).slice(0,8).reverse();
        const bids = (d.bids || []).slice(0,8);
        renderOrderBook(asks, bids);
    } catch(e) {}
}

function renderOrderBook(asks, bids) {
    const maxQty = Math.max(...asks.map(a=>parseFloat(a[1])), ...bids.map(b=>parseFloat(b[1])));
    document.getElementById('askRows').innerHTML = asks.map(([p,q]) => {
        const pct = (parseFloat(q)/maxQty*100).toFixed(0);
        return `<div class="ob-row ob-ask">
            <span>${fmtPrice(parseFloat(p))}</span>
            <span>${parseFloat(q).toFixed(4)}</span>
            <span>${fmtPrice(parseFloat(p)*parseFloat(q))}</span>
            <div class="ob-bar" style="width:${pct}%"></div>
        </div>`;
    }).join('');

    if (asks.length && bids.length) {
        const spread = (parseFloat(asks[asks.length-1][0]) - parseFloat(bids[0][0]));
        document.getElementById('spreadRow').textContent = fmtPrice(parseFloat(asks[asks.length-1][0])) + ' · Spread: ' + spread.toFixed(4);
    }

    document.getElementById('bidRows').innerHTML = bids.map(([p,q]) => {
        const pct = (parseFloat(q)/maxQty*100).toFixed(0);
        return `<div class="ob-row ob-bid">
            <span>${fmtPrice(parseFloat(p))}</span>
            <span>${parseFloat(q).toFixed(4)}</span>
            <span>${fmtPrice(parseFloat(p)*parseFloat(q))}</span>
            <div class="ob-bar" style="width:${pct}%"></div>
        </div>`;
    }).join('');
}

// ═══ RECENT TRADES (Binance public) ═══
async function fetchRecentTrades() {
    try {
        const endpoint = STATE.market === 'futures'
            ? `https://fapi.binance.com/fapi/v1/trades?symbol=${STATE.symbol}&limit=20`
            : `https://api.binance.com/api/v3/trades?symbol=${STATE.symbol}&limit=20`;
        const r = await fetch(endpoint);
        const trades = await r.json();
        document.getElementById('recentTrades').innerHTML = trades.reverse().map(t => {
            const isBuy = !t.isBuyerMaker;
            const time = new Date(t.time).toLocaleTimeString('ru');
            return `<div class="rt-row">
                <span style="color:${isBuy?'var(--green)':'var(--red)'}">${fmtPrice(parseFloat(t.price))}</span>
                <span>${parseFloat(t.qty).toFixed(4)}</span>
                <span style="color:var(--muted)">${time}</span>
            </div>`;
        }).join('');
    } catch(e) {}
}

// ═══ ACCOUNT DATA ═══
async function fetchAccountData() {
    if (!STATE.profileId) return;
    try {
        const r = await fetch(`/profiles/${STATE.profileId}/account-data?light=1`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!r.ok) return;
        const d = await r.json();
        renderPositions(d.activePositions || []);
        renderOpenOrders(d.openOrders || []);
    } catch(e) {}
}

function renderPositions(positions) {
    const tbody = document.getElementById('positionsBody');
    if (!positions.length) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="10">Нет открытых позиций</td></tr>';
        return;
    }
    tbody.innerHTML = positions.map(p => {
        const amt   = parseFloat(p.positionAmt || 0);
        const entry = parseFloat(p.entryPrice || 0);
        const mark  = parseFloat(p.markPrice || 0);
        const pnl   = parseFloat(p.unrealizedProfit || 0);
        const side  = amt > 0 ? 'LONG' : 'SHORT';
        const color = pnl >= 0 ? 'var(--green)' : 'var(--red)';
        const roe   = entry > 0 ? ((pnl / (Math.abs(amt) * entry)) * 100).toFixed(2) : '—';
        const closeSide = amt > 0 ? 'SELL' : 'BUY';

        return `<tr>
            <td style="font-weight:700">${p.symbol}</td>
            <td style="color:${amt>0?'var(--green)':'var(--red)'};font-weight:700">${side}</td>
            <td>${Math.abs(amt)}</td>
            <td>${fmtPrice(entry)}</td>
            <td>${fmtPrice(mark)}</td>
            <td style="color:var(--red)">${fmtPrice(parseFloat(p.liquidationPrice||0))}</td>
            <td>${fmtPrice(parseFloat(p.isolatedMargin||0))}</td>
            <td style="color:${color};font-weight:700">${pnl>=0?'+':''}${pnl.toFixed(4)} USDT</td>
            <td style="color:${color}">${roe}%</td>
            <td>
                <button onclick="closePos('${p.symbol}','${closeSide}','${Math.abs(amt)}')"
                    style="padding:3px 8px;border-radius:4px;border:1px solid var(--red);background:rgba(246,70,93,.12);
                           color:var(--red);font-size:10px;cursor:pointer;">
                    Закрыть
                </button>
            </td>
        </tr>`;
    }).join('');
}

function renderOpenOrders(orders) {
    const tbody = document.getElementById('ordersBody');
    if (!orders.length) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="9">Нет открытых ордеров</td></tr>';
        return;
    }
    tbody.innerHTML = orders.map(o => {
        const side = o.side || '';
        const time = o.time ? new Date(o.time).toLocaleString('ru') : '—';
        return `<tr>
            <td style="color:var(--muted)">${time}</td>
            <td style="font-weight:700">${o.symbol}</td>
            <td>${o.type}</td>
            <td style="color:${side==='BUY'?'var(--green)':'var(--red)'}">${side}</td>
            <td>${fmtPrice(parseFloat(o.price||0))}</td>
            <td>${o.origQty||'—'}</td>
            <td>${o.executedQty||'—'}</td>
            <td>${o.status||'—'}</td>
            <td>
                <button onclick="cancelOrd('${o.symbol}',${o.orderId},'${o.type||'LIMIT'}')"
                    style="padding:3px 8px;border-radius:4px;border:1px solid var(--border);background:transparent;
                           color:var(--muted);font-size:10px;cursor:pointer;">
                    Отменить
                </button>
            </td>
        </tr>`;
    }).join('');
}

// ═══ CONTROLS ═══
function changeSymbol(sym) {
    STATE.symbol = sym;
    initChart();
    updateAll();
    updateUrl();
}

function setMarket(m) {
    STATE.market = m;
    document.getElementById('btnFutures').className = m === 'futures' ? 'active' : '';
    document.getElementById('btnSpot').className    = m === 'spot'    ? 'active' : '';
    document.getElementById('formMarketLabel').textContent = m === 'futures' ? 'Futures Perpetual' : 'Spot';
    const futuresOnly = document.querySelectorAll('.futures-only');
    futuresOnly.forEach(el => el.style.display = m === 'futures' ? '' : 'none');
    initChart();
    updateAll();
    updateUrl();
}

function changeProfile(id) {
    STATE.profileId = id;
    fetchAccountData();
    updateUrl();
}

function setSide(side) {
    STATE.side = side;
    document.getElementById('btnBuy').className  = 'side-tab buy'  + (side==='BUY'?' active':'');
    document.getElementById('btnSell').className = 'side-tab sell' + (side==='SELL'?' active':'');
    const btn = document.getElementById('submitBtn');
    btn.className = 'submit-btn ' + (side==='BUY'?'buy':'sell');
    btn.textContent = side === 'BUY' ? 'Открыть BUY / LONG' : 'Открыть SELL / SHORT';
}

function setOrderType(type) {
    STATE.orderType = type;
    ['MARKET','LIMIT','STOP_LIMIT'].forEach(t => {
        document.getElementById('tab'+t.replace('_','').replace('STOPLI','StopLi')).className = 'ot-tab' + (t===type?' active':'');
    });
    document.getElementById('fieldPrice').style.display     = type !== 'MARKET' ? '' : 'none';
    document.getElementById('fieldStopPrice').style.display = type === 'STOP_LIMIT' ? '' : 'none';
}

function setMarginType(type) {
    STATE.marginType = type;
    document.getElementById('btnCross').className    = 'active' + (type==='CROSSED'?' active':'');
    document.getElementById('btnIsolated').className = type==='ISOLATED' ? 'active' : '';
    document.getElementById('btnCross').className    = type==='CROSSED'  ? 'active' : '';
}

function changeLev(delta) {
    STATE.leverage = Math.max(1, Math.min(125, STATE.leverage + delta));
    document.getElementById('levDisplay').textContent = STATE.leverage + '×';
    recalcTotal();
}

function setLev(v) {
    STATE.leverage = v;
    document.getElementById('levDisplay').textContent = v + '×';
    recalcTotal();
}

function toggleTpSl() {
    STATE.tpslOpen = !STATE.tpslOpen;
    const sw = document.getElementById('tpslSwitch');
    const fields = document.getElementById('tpslFields');
    sw.className = 'toggle-switch' + (STATE.tpslOpen ? ' on' : '');
    fields.style.display = STATE.tpslOpen ? '' : 'none';
}

function setPct(pct) {
    if (!STATE.lastPrice) return;
    // Assumes available balance = 100 USDT (placeholder — real balance from API)
    const balance = 100;
    const notional = balance * (pct/100) * STATE.leverage;
    const qty = notional / STATE.lastPrice;
    document.getElementById('inputQty').value = qty.toFixed(6);
    document.getElementById('inputUsdt').value = (balance * pct/100).toFixed(2);
    recalcTotal();
}

function calcQtyFromUsdt() {
    const usdt = parseFloat(document.getElementById('inputUsdt').value) || 0;
    const price = getEffectivePrice();
    if (price > 0) {
        const qty = (usdt * STATE.leverage) / price;
        document.getElementById('inputQty').value = qty.toFixed(6);
    }
    recalcTotal();
}

function getEffectivePrice() {
    if (STATE.orderType === 'MARKET') return STATE.lastPrice;
    return parseFloat(document.getElementById('inputPrice').value) || STATE.lastPrice;
}

function recalcTotal() {
    const qty   = parseFloat(document.getElementById('inputQty').value) || 0;
    const price = getEffectivePrice();
    const total = qty * price;
    const margin= total / STATE.leverage;
    const fee   = total * 0.0004;
    document.getElementById('summaryTotal').textContent  = total > 0 ? fmtPrice(total)  + ' USDT' : '— USDT';
    document.getElementById('summaryMargin').textContent = total > 0 ? fmtPrice(margin) + ' USDT' : '— USDT';
    document.getElementById('summaryFee').textContent    = total > 0 ? fmtPrice(fee)    + ' USDT' : '— USDT';

    const base = STATE.symbol.replace('USDT','');
    document.getElementById('baseAssetLabel').textContent = base;
    document.getElementById('qtyUnitLabel').textContent   = base;
}

// ═══ MODAL HELPERS ═══
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}
// Close on overlay click
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});
// Close on Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    }
});

// ═══ ORDER SUBMISSION ═══
function submitOrder() {
    if (!STATE.profileId) { toast('Выберите профиль', 'error'); return; }
    const qty = parseFloat(document.getElementById('inputQty').value);
    if (!qty || qty <= 0) { toast('Введите количество', 'error'); return; }

    const price = getEffectivePrice();
    const total = qty * price;

    // Fill modal
    const isBuy = STATE.side === 'BUY';
    document.getElementById('mso-title').textContent   = `Подтвердить ${STATE.side} ордер`;
    document.getElementById('mso-symbol').textContent  = STATE.symbol;
    const sideEl = document.getElementById('mso-side');
    sideEl.textContent  = STATE.side === 'BUY' ? 'BUY / LONG' : 'SELL / SHORT';
    sideEl.className    = 'md-value ' + (isBuy ? 'green' : 'red');
    document.getElementById('mso-type').textContent    = STATE.orderType;
    document.getElementById('mso-qty').textContent     = qty.toFixed(6) + ' ' + STATE.symbol.replace('USDT','');
    document.getElementById('mso-total').textContent   = total > 0 ? fmtPrice(total) + ' USDT' : '—';
    document.getElementById('mso-lev').textContent     = STATE.leverage + '×';
    const confirmBtn = document.getElementById('mso-confirm');
    confirmBtn.className = 'modal-btn ' + (isBuy ? 'confirm-buy' : 'confirm-sell');
    confirmBtn.textContent = isBuy ? 'Открыть LONG' : 'Открыть SHORT';

    // Bind confirm action
    confirmBtn.onclick = async () => {
        closeModal('modalSubmitOrder');
        await _doSubmitOrder(qty);
    };

    openModal('modalSubmitOrder');
}

async function _doSubmitOrder(qty) {
    const btn = document.getElementById('submitBtn');
    btn.classList.add('btn-loading');
    btn.textContent = 'Отправка...';

    const payload = {
        _token:      document.querySelector('meta[name=csrf-token]').content,
        symbol:      STATE.symbol,
        side:        STATE.side,
        order_type:  STATE.orderType === 'STOP_LIMIT' ? 'LIMIT' : STATE.orderType,
        quantity:    qty,
        margin_type: STATE.marginType,
        leverage:    STATE.leverage,
    };
    if (STATE.orderType !== 'MARKET') {
        payload.price = parseFloat(document.getElementById('inputPrice').value) || null;
    }
    if (STATE.tpslOpen) {
        payload.tp = parseFloat(document.getElementById('inputTp').value) || null;
        payload.sl = parseFloat(document.getElementById('inputSl').value) || null;
    }

    try {
        const r = await fetch(`/trading/${STATE.profileId}/order`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const d = await r.json();
        if (d.success) {
            toast(`Ордер ${STATE.side} исполнен!`, 'success');
            document.getElementById('inputQty').value  = '';
            document.getElementById('inputUsdt').value = '';
            recalcTotal();
            setTimeout(fetchAccountData, 1500);
        } else {
            toast(d.error || 'Ошибка ордера', 'error');
        }
    } catch(e) {
        toast('Ошибка сети', 'error');
    }

    btn.classList.remove('btn-loading');
    setSide(STATE.side);
}

function closePos(symbol, side, qty) {
    if (!STATE.profileId) return;

    document.getElementById('mcp-symbol').textContent = symbol;
    const sideEl = document.getElementById('mcp-side');
    sideEl.textContent = side === 'SELL' ? 'LONG → закрыть' : 'SHORT → закрыть';
    sideEl.className   = 'md-value ' + (side === 'SELL' ? 'green' : 'red');
    document.getElementById('mcp-qty').textContent = qty;

    document.getElementById('mcp-confirm').onclick = async () => {
        closeModal('modalClosePos');
        try {
            const r = await fetch(`/profiles/${STATE.profileId}/close-position`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    _token: document.querySelector('meta[name=csrf-token]').content,
                    symbol, side, quantity: qty
                }),
            });
            const d = await r.json();
            d.success ? toast('Позиция закрыта', 'success') : toast(d.error, 'error');
            if (d.success) setTimeout(fetchAccountData, 1500);
        } catch(e) { toast('Ошибка сети', 'error'); }
    };

    openModal('modalClosePos');
}

function cancelOrd(symbol, orderId, type = '—') {
    if (!STATE.profileId) return;

    document.getElementById('mco-symbol').textContent = symbol;
    document.getElementById('mco-id').textContent     = orderId;
    document.getElementById('mco-type').textContent   = type;

    document.getElementById('mco-confirm').onclick = async () => {
        closeModal('modalCancelOrder');
        try {
            const r = await fetch(`/trading/${STATE.profileId}/cancel-order`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    _token: document.querySelector('meta[name=csrf-token]').content,
                    symbol, order_id: orderId
                }),
            });
            const d = await r.json();
            d.success ? toast('Ордер отменён', 'success') : toast(d.error, 'error');
            if (d.success) setTimeout(fetchAccountData, 1500);
        } catch(e) { toast('Ошибка сети', 'error'); }
    };

    openModal('modalCancelOrder');
}

// ═══ BOTTOM TABS ═══
function switchBottomTab(name, el) {
    document.querySelectorAll('.bottom-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.bottom-pane').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('pane-' + name).classList.add('active');

    if (name === 'history' && STATE.profileId) loadHistory();
    if (name === 'pnl'     && STATE.profileId) loadPnl();
}

async function loadHistory() {
    try {
        const r = await fetch(`/profiles/${STATE.profileId}/account-data?tab=trade_history&symbol=${STATE.symbol}`);
        const d = await r.json();
        const trades = d.tradeHistory || [];
        document.getElementById('historyBody').innerHTML = trades.length
            ? trades.map(t => `<tr>
                <td style="color:var(--muted)">${new Date(t.time).toLocaleString('ru')}</td>
                <td>${t._symbol||t.symbol||'—'}</td>
                <td>Trade</td>
                <td style="color:${t.side==='BUY'?'var(--green)':'var(--red)'}">${t.side}</td>
                <td>${fmtPrice(parseFloat(t.price||0))}</td>
                <td>${t.qty||'—'}</td>
                <td style="color:${parseFloat(t.realizedPnl||0)>=0?'var(--green)':'var(--red)'}">
                    ${parseFloat(t.realizedPnl||0).toFixed(4)}
                </td>
                <td>${parseFloat(t.commission||0).toFixed(6)}</td>
            </tr>`).join('')
            : '<tr class="empty-row"><td colspan="8">Нет истории</td></tr>';
    } catch(e) {}
}

async function loadPnl() {
    try {
        const r = await fetch(`/profiles/${STATE.profileId}/account-data?tab=income`);
        const d = await r.json();
        const income = d.income || [];
        document.getElementById('pnlBody').innerHTML = income.length
            ? income.map(i => {
                const val = parseFloat(i.income||0);
                return `<tr>
                    <td style="color:var(--muted)">${i.time ? new Date(i.time).toLocaleString('ru') : '—'}</td>
                    <td>${i.incomeType||'—'}</td>
                    <td>${i.symbol||'—'}</td>
                    <td style="color:${val>=0?'var(--green)':'var(--red)'};font-weight:700"
                        ${val>=0?'+':''}${val.toFixed(4)} ${i.asset||'USDT'}
                    </td>
                </tr>`;
            }).join('')
            : '<tr class="empty-row"><td colspan="4">Нет PnL данных</td></tr>';
    } catch(e) {}
}

// ═══ HELPERS ═══
function fmtPrice(v) {
    if (!v || isNaN(v)) return '—';
    if (v >= 1000) return v.toLocaleString('ru', {minimumFractionDigits:2, maximumFractionDigits:2});
    if (v >= 1)    return v.toFixed(4);
    return v.toFixed(6);
}

function fmtVol(v) {
    if (v >= 1e9) return (v/1e9).toFixed(2) + 'B';
    if (v >= 1e6) return (v/1e6).toFixed(2) + 'M';
    if (v >= 1e3) return (v/1e3).toFixed(2) + 'K';
    return v.toFixed(2);
}

function toast(msg, type='info') {
    const wrap = document.getElementById('toastWrap');
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.textContent = msg;
    wrap.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}

function updateUrl() {
    const url = new URL(window.location.href);
    url.searchParams.set('symbol', STATE.symbol);
    url.searchParams.set('market', STATE.market);
    if (STATE.profileId) url.searchParams.set('profile_id', STATE.profileId);
    window.history.replaceState(null, '', url.toString());
}

function updateAll() {
    fetchTicker();
    fetchOrderBook();
    fetchRecentTrades();
    recalcTotal();
}

// ═══ INIT ═══
initChart();
updateAll();
fetchAccountData();

// Auto-refresh
setInterval(fetchTicker, 5000);
setInterval(fetchOrderBook, 3000);
setInterval(fetchRecentTrades, 5000);
setInterval(fetchAccountData, 15000);
</script>
</body>
</html>
