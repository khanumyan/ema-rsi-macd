@extends('layouts.app')
@section('page-title', $profile->profile_name . ' — ' . $profile->category)
@push('styles')
<style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0a2e 50%, #0a0a0a 100%);
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px 16px 40px; }
        .header { text-align: center; padding: 32px 16px 20px; position: relative; }
        .logout-btn {
            position: absolute; top: 20px; right: 20px;
            background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px; padding: 8px 16px; color: #fca5a5; text-decoration: none; font-size: 14px;
        }
        .user-info {
            position: absolute; top: 20px; left: 20px;
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 13px; color: #94a3b8; padding: 6px 10px; border-radius: 999px;
            background: rgba(15, 23, 42, 0.7);
        }
        .home-chip {
            padding: 4px 10px; border-radius: 999px; border: 1px solid rgba(148, 163, 184, 0.7);
            background: rgba(15, 23, 42, 0.9); color: #e5e7eb; text-decoration: none; font-size: 12px;
        }
        .home-chip:hover { border-color: #a855f7; color: #e9d5ff; }
        .user-email { font-size: 12px; color: #9ca3af; }
        .logo-container { margin-bottom: 16px; display: flex; justify-content: center; align-items: center; }
        .logo-image { max-width: 140px; height: auto; filter: drop-shadow(0 4px 16px rgba(248, 113, 113, 0.4)); }
        .header-title { font-size: 28px; font-weight: bold; background: linear-gradient(to right, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 6px; }
        .header-subtitle { font-size: 14px; color: #94a3b8; }
        .breadcrumb { margin-bottom: 20px; font-size: 13px; color: #94a3b8; }
        .breadcrumb a { color: #a855f7; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .pnl-cards {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px;
        }
        .pnl-card {
            background: rgba(15, 23, 42, 0.95); border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.4);
            padding: 18px; text-align: center;
        }
        .pnl-card .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 6px; }
        .pnl-card .value { font-size: 20px; font-weight: 700; }
        .pnl-card .value.positive { color: #4ade80; }
        .pnl-card .value.negative { color: #f87171; }
        .tabs-wrap { overflow-x: auto; margin-bottom: 16px; -webkit-overflow-scrolling: touch; }
        .tabs {
            display: inline-flex; min-width: max-content; border-radius: 10px; overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.5); background: rgba(15, 23, 42, 0.6);
        }
        .tabs a, .tabs span.tab {
            padding: 10px 14px; font-size: 12px; color: #94a3b8; text-decoration: none;
            background: transparent; transition: all 0.2s; white-space: nowrap; cursor: pointer;
            border: none; font-family: inherit;
        }
        .tabs a:hover, .tabs span.tab:hover { color: #e5e7eb; background: rgba(168, 85, 247, 0.2); }
        .tabs a.active, .tabs span.tab.active { color: #e5e7eb; background: linear-gradient(135deg, #a855f7, #ec4899); }
        .table-wrapper {
            margin-top: 12px; border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(15, 23, 42, 0.95); overflow: auto;
        }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead { background: rgba(15, 23, 42, 0.98); }
        th, td { padding: 8px 10px; border-bottom: 1px solid rgba(51, 65, 85, 0.8); text-align: left; }
        th { font-size: 10px; text-transform: uppercase; letter-spacing: 0.03em; color: #94a3b8; }
        tbody tr:hover { background: rgba(30, 64, 175, 0.25); }
        tbody tr td:first-child a { color: #a855f7; text-decoration: none; font-weight: 500; }
        tbody tr td:first-child a:hover { text-decoration: underline; }
        .badge { padding: 2px 6px; border-radius: 999px; font-size: 10px; }
        .badge-long { background: rgba(34, 197, 94, 0.2); color: #bbf7d0; }
        .badge-short { background: rgba(239, 68, 68, 0.2); color: #fecaca; }
        .btn-close { padding: 4px 10px; font-size: 11px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.5); background: rgba(239, 68, 68, 0.2); color: #fecaca; cursor: pointer; }
        .btn-close:hover { background: rgba(239, 68, 68, 0.4); }
        .error-box { padding: 16px; border-radius: 12px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fecaca; margin-bottom: 20px; }
        .ws-status { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #94a3b8; margin-bottom: 12px; padding: 6px 10px; border-radius: 8px; background: rgba(15, 23, 42, 0.8); }
        .ws-status .dot { width: 8px; height: 8px; border-radius: 50%; background: #64748b; }
        .ws-status.connected .dot { background: #4ade80; }
        .ws-status.error .dot { background: #f87171; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:9999; align-items:center; justify-content:center; }
        .modal-overlay.active { display:flex; }
        .modal { background:#0f0a1e; border:1px solid rgba(168,85,247,.35); border-radius:16px; padding:30px 28px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,.7); }
        .modal-icon { font-size:36px; text-align:center; margin-bottom:14px; }
        .modal h3 { font-size:17px; font-weight:700; text-align:center; margin-bottom:8px; color:#f1f5f9; }
        .modal p { font-size:13px; color:#94a3b8; text-align:center; margin-bottom:22px; line-height:1.5; }
        .modal-btns { display:flex; gap:10px; justify-content:center; }
        .modal-btn { padding:9px 24px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .2s; }
        .modal-btn-cancel { background:rgba(255,255,255,.07); color:#94a3b8; border:1px solid rgba(255,255,255,.1); }
        .modal-btn-cancel:hover { background:rgba(255,255,255,.12); }
        .modal-btn-confirm { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; }
        .modal-btn-confirm:hover { opacity:.88; }
    </style>
@endpush
@section('content')
    <div class="container" style="max-width:100%;padding:0">
        <div class="breadcrumb">
            <a href="{{ route('profiles.index') }}">← Все профили</a> &rarr; {{ $profile->profile_name }}
        </div>

        @if ($error)
            <div class="error-box">{{ $error }}</div>
        @else
            <div id="ws-status" class="ws-status" style="display: none;">
                <span class="dot"></span>
                <span id="ws-status-text">Подключение к сокету Binance…</span>
            </div>
            <section class="pnl-cards">
                <div class="pnl-card">
                    <div class="label">Нереализованный PNL</div>
                    @php $u = $totalUnrealizedProfit; @endphp
                    <div id="pnl-unrealized" class="value {{ $u >= 0 ? 'positive' : 'negative' }}">{{ number_format($u, 2) }} USDT</div>
                </div>
                <div class="pnl-card">
                    <div class="label">Баланс кошелька</div>
                    <div id="pnl-wallet" class="value">{{ number_format($totalWalletBalance, 2) }} USDT</div>
                </div>
                <div class="pnl-card">
                    <div class="label">Маржа</div>
                    <div id="pnl-margin" class="value">{{ number_format($totalMarginBalance, 2) }} USDT</div>
                </div>
            </section>

            @php $curTab = request('tab', 'positions'); @endphp
            <div class="tabs-wrap">
                <div class="tabs">
                    <a href="{{ url()->current() }}?tab=positions" class="{{ $curTab === 'positions' ? 'active' : '' }}">Активные позиции</a>
                    <a href="{{ url()->current() }}?tab=open_orders" class="{{ $curTab === 'open_orders' ? 'active' : '' }}">Open Orders</a>
                    <a href="{{ url()->current() }}?tab=order_history" class="{{ $curTab === 'order_history' ? 'active' : '' }}">Order History</a>
                    <a href="{{ url()->current() }}?tab=trade_history" class="{{ $curTab === 'trade_history' ? 'active' : '' }}">Trade History</a>
                    <a href="{{ url()->current() }}?tab=transaction_history" class="{{ $curTab === 'transaction_history' ? 'active' : '' }}">Transaction History</a>
                    <a href="{{ url()->current() }}?tab=assets" class="{{ $curTab === 'assets' ? 'active' : '' }}">Assets</a>
                </div>
            </div>

            {{-- Активные позиции --}}
            <div id="pane-positions" class="tab-pane {{ $curTab === 'positions' ? 'active' : '' }}">
                <section class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Символ</th>
                                <th>Сторона</th>
                                <th>Размер</th>
                                <th>Вход</th>
                                <th>Mark</th>
                                <th>Нереал. PNL</th>
                                <th>Плечо</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="positions-tbody">
                            @forelse ($activePositions as $pos)
                                @php
                                    $amt = (float) ($pos['positionAmt'] ?? 0);
                                    $side = $amt >= 0 ? 'LONG' : 'SHORT';
                                    $sym = $pos['symbol'] ?? '';
                                    $qty = $amt >= 0 ? (string) $amt : (string) abs($amt);
                                    $closeSide = $amt >= 0 ? 'SELL' : 'BUY';
                                    $posSide = $pos['positionSide'] ?? 'BOTH';
                                @endphp
                                <tr data-symbol="{{ $sym }}" data-side="{{ $closeSide }}" data-quantity="{{ $qty }}" data-position-side="{{ $posSide }}">
                                    <td><a href="{{ route('profiles.positions.show', [$profile, $sym]) }}">{{ $sym }}</a></td>
                                    <td><span class="badge {{ $side === 'LONG' ? 'badge-long' : 'badge-short' }}">{{ $side }}</span></td>
                                    <td>{{ $pos['positionAmt'] ?? '—' }}</td>
                                    <td>{{ $pos['entryPrice'] ?? '—' }}</td>
                                    <td>{{ $pos['markPrice'] ?? '—' }}</td>
                                    <td class="{{ isset($pos['unRealizedProfit']) ? (floatval($pos['unRealizedProfit']) >= 0 ? 'value positive' : 'value negative') : '' }}">{{ $pos['unRealizedProfit'] ?? $pos['unrealizedProfit'] ?? '—' }}</td>
                                    <td>{{ $pos['leverage'] ?? '—' }}</td>
                                    <td><button type="button" class="btn-close btn-close-position">Закрыть</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" style="text-align:center; padding:24px; color:#94a3b8;">Нет активных позиций.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>

            {{-- Open Orders --}}
            <div id="pane-open_orders" class="tab-pane {{ $curTab === 'open_orders' ? 'active' : '' }}">
                <section class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Символ</th>
                                <th>Сторона</th>
                                <th>Тип</th>
                                <th>Цена</th>
                                <th>Кол-во</th>
                                <th>Статус</th>
                                <th>Время</th>
                            </tr>
                        </thead>
                        <tbody id="open-orders-tbody">
                            @forelse ($openOrders as $o)
                                @php $orderTime = $o['time'] ?? $o['createTime'] ?? 0; @endphp
                                <tr>
                                    <td>{{ $o['symbol'] ?? '—' }}</td>
                                    <td>{{ $o['side'] ?? '—' }}</td>
                                    <td>{{ $o['type'] ?? $o['orderType'] ?? '—' }}</td>
                                    <td>{{ $o['stopPrice'] ?? $o['triggerPrice'] ?? $o['price'] ?? '—' }}</td>
                                    <td>{{ $o['origQty'] ?? $o['quantity'] ?? '—' }}</td>
                                    <td>{{ $o['status'] ?? $o['algoStatus'] ?? '—' }}</td>
                                    <td>{{ $orderTime ? date('Y-m-d H:i', $orderTime / 1000) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">Нет открытых ордеров.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>

            {{-- Order History --}}
            <div id="pane-order_history" class="tab-pane {{ $curTab === 'order_history' ? 'active' : '' }}">
                <div style="margin: 10px 0 12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <form method="GET" action="{{ url()->current() }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <input type="hidden" name="tab" value="order_history">
                        <label style="font-size:12px; color:#94a3b8;">Символ</label>
                        <select name="history_symbol" style="background: rgba(15,23,42,0.9); color:#e5e7eb; border:1px solid rgba(148,163,184,0.5); border-radius:8px; padding:8px 10px; font-size:12px;">
                            <option value="">—</option>
                            <option value="__ALL__" {{ ($historySymbol ?? '') === '__ALL__' ? 'selected' : '' }}>Все сделки профиля</option>
                            @foreach (($historySymbolsCandidates ?? []) as $sym)
                                <option value="{{ $sym }}" {{ ($historySymbol ?? '') === $sym ? 'selected' : '' }}>{{ $sym }}</option>
                            @endforeach
                        </select>
                        <input name="history_symbol_manual" value="{{ request('history_symbol_manual','') }}" placeholder="или введите символ, например BTCUSDT" style="min-width:220px; background: rgba(15,23,42,0.9); color:#e5e7eb; border:1px solid rgba(148,163,184,0.5); border-radius:8px; padding:8px 10px; font-size:12px;">
                        <button class="btn-close" type="submit" style="border-color: rgba(168,85,247,0.6); background: rgba(168,85,247,0.2); color:#e9d5ff;">Показать</button>
                    </form>
                </div>
                <section class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Символ</th>
                                <th>OrderId</th>
                                <th>Сторона</th>
                                <th>Тип</th>
                                <th>Цена</th>
                                <th>Кол-во</th>
                                <th>Статус</th>
                                <th>Время</th>
                            </tr>
                        </thead>
                        <tbody id="order-history-tbody">
                            @forelse ($orderHistory as $o)
                                <tr>
                                    <td>{{ $o['_symbol'] ?? $o['symbol'] ?? '—' }}</td>
                                    <td>{{ $o['orderId'] ?? '—' }}</td>
                                    <td>{{ $o['side'] ?? '—' }}</td>
                                    <td>{{ $o['type'] ?? '—' }}</td>
                                    <td>{{ $o['price'] ?? $o['stopPrice'] ?? '—' }}</td>
                                    <td>{{ $o['origQty'] ?? '—' }}</td>
                                    <td>{{ $o['status'] ?? '—' }}</td>
                                    <td>{{ isset($o['time']) ? date('Y-m-d H:i', $o['time'] / 1000) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" style="text-align:center; padding:24px; color:#94a3b8;">Нет ордеров.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>

            {{-- Trade History --}}
            <div id="pane-trade_history" class="tab-pane {{ $curTab === 'trade_history' ? 'active' : '' }}">
                <div style="margin: 10px 0 12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <form method="GET" action="{{ url()->current() }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <input type="hidden" name="tab" value="trade_history">
                        <label style="font-size:12px; color:#94a3b8;">Символ</label>
                        <select name="history_symbol" style="background: rgba(15,23,42,0.9); color:#e5e7eb; border:1px solid rgba(148,163,184,0.5); border-radius:8px; padding:8px 10px; font-size:12px;">
                            <option value="">—</option>
                            <option value="__ALL__" {{ ($historySymbol ?? '') === '__ALL__' ? 'selected' : '' }}>Все сделки профиля</option>
                            @foreach (($historySymbolsCandidates ?? []) as $sym)
                                <option value="{{ $sym }}" {{ ($historySymbol ?? '') === $sym ? 'selected' : '' }}>{{ $sym }}</option>
                            @endforeach
                        </select>
                        <input name="history_symbol_manual" value="{{ request('history_symbol_manual','') }}" placeholder="или введите символ, например BTCUSDT" style="min-width:220px; background: rgba(15,23,42,0.9); color:#e5e7eb; border:1px solid rgba(148,163,184,0.5); border-radius:8px; padding:8px 10px; font-size:12px;">
                        <button class="btn-close" type="submit" style="border-color: rgba(168,85,247,0.6); background: rgba(168,85,247,0.2); color:#e9d5ff;">Показать</button>
                    </form>
                </div>
                <section class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Символ</th>
                                <th>Сторона</th>
                                <th>Цена</th>
                                <th>Кол-во</th>
                                <th>Комиссия</th>
                                <th>Realized PNL</th>
                                <th>Время</th>
                            </tr>
                        </thead>
                        <tbody id="trade-history-tbody">
                            @forelse ($tradeHistory as $t)
                                <tr>
                                    <td>{{ $t['_symbol'] ?? $t['symbol'] ?? '—' }}</td>
                                    <td>{{ $t['side'] ?? '—' }}</td>
                                    <td>{{ $t['price'] ?? '—' }}</td>
                                    <td>{{ $t['qty'] ?? '—' }}</td>
                                    <td>{{ $t['commission'] ?? '—' }} {{ $t['commissionAsset'] ?? '' }}</td>
                                    <td>{{ $t['realizedPnl'] ?? '—' }}</td>
                                    <td>{{ isset($t['time']) ? date('Y-m-d H:i', $t['time'] / 1000) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">Нет сделок.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>

            {{-- Transaction History (Income) --}}
            <div id="pane-transaction_history" class="tab-pane {{ $curTab === 'transaction_history' ? 'active' : '' }}">
                <section class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Символ</th>
                                <th>Тип</th>
                                <th>Доход</th>
                                <th>Актив</th>
                                <th>Время</th>
                            </tr>
                        </thead>
                        <tbody id="income-tbody">
                            @forelse ($income as $i)
                                <tr>
                                    <td>{{ $i['symbol'] ?? '—' }}</td>
                                    <td>{{ $i['incomeType'] ?? '—' }}</td>
                                    <td>{{ $i['income'] ?? '—' }}</td>
                                    <td>{{ $i['asset'] ?? '—' }}</td>
                                    <td>{{ isset($i['time']) ? date('Y-m-d H:i', $i['time'] / 1000) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" style="text-align:center; padding:24px; color:#94a3b8;">Нет транзакций.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>

            {{-- Assets --}}
            <div id="pane-assets" class="tab-pane {{ $curTab === 'assets' ? 'active' : '' }}">
                <section class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Актив</th>
                                <th>Баланс кошелька</th>
                                <th>Нереал. PNL</th>
                                <th>Кросс-баланс</th>
                            </tr>
                        </thead>
                        <tbody id="assets-tbody">
                            @php $assetsWithBalance = array_values(array_filter($assets ?? [], function($a) { return (float)($a['walletBalance'] ?? 0) != 0 || (float)($a['crossWalletBalance'] ?? 0) != 0; })); @endphp
                            @forelse ($assetsWithBalance as $a)
                                <tr>
                                    <td>{{ $a['asset'] ?? '—' }}</td>
                                    <td>{{ $a['walletBalance'] ?? '—' }}</td>
                                    <td>{{ $a['unrealizedProfit'] ?? '—' }}</td>
                                    <td>{{ $a['crossWalletBalance'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center; padding:24px; color:#94a3b8;">Нет активов.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>
        @endif
    </div>

    @if (!$error)
    <script>
    (function() {
        var profileId = {{ $profile->id }};
        var curTab = '{{ $curTab }}';
        var accountDataUrl = '{{ route("profiles.account-data", $profile) }}';
        var accountDataLightUrl = accountDataUrl + '?light=1';
        var streamUrlApi = '{{ route("profiles.stream-url", $profile) }}';
        var closePositionUrl = '{{ route("profiles.close-position", $profile) }}';
        var positionDetailBase = '{{ route("profiles.positions.show", [$profile, "__SYMBOL__"]) }}'.replace('__SYMBOL__', '');
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var ws = null;
        var refreshInFlight = false;
        var refreshIntervalId = null;
        var REFRESH_EVERY_MS = 1000;
        var statusEl = document.getElementById('ws-status');
        var statusText = document.getElementById('ws-status-text');

        function setStatus(connected, text) {
            if (!statusEl) return;
            statusEl.style.display = 'inline-flex';
            statusEl.classList.remove('connected', 'error');
            if (connected) statusEl.classList.add('connected');
            else if (text && text.indexOf('Ошибка') >= 0) statusEl.classList.add('error');
            if (statusText) statusText.textContent = text || (connected ? 'Сокет Binance: real-time' : 'Сокет отключён');
        }

        function formatNum(n) { return Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function formatTime(ms) {
            if (!ms) return '—';
            var d = new Date(ms);
            return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
        }
        function updatePnLCards(data) {
            var u = data.totalUnrealizedProfit || 0;
            var el = document.getElementById('pnl-unrealized');
            if (el) { el.textContent = formatNum(u) + ' USDT'; el.className = 'value ' + (u >= 0 ? 'positive' : 'negative'); }
            el = document.getElementById('pnl-wallet'); if (el) el.textContent = formatNum(data.totalWalletBalance || 0) + ' USDT';
            el = document.getElementById('pnl-margin'); if (el) el.textContent = formatNum(data.totalMarginBalance || 0) + ' USDT';
        }
        function renderPositionRow(pos) {
            var amt = parseFloat(pos.positionAmt || 0);
            var side = amt >= 0 ? 'LONG' : 'SHORT';
            var sym = pos.symbol || '';
            var qty = amt >= 0 ? String(amt) : String(Math.abs(amt));
            var closeSide = amt >= 0 ? 'SELL' : 'BUY';
            var posSide = pos.positionSide || 'BOTH';
            var href = positionDetailBase + encodeURIComponent(sym);
            var upnl = pos.unRealizedProfit ?? pos.unrealizedProfit ?? '—';
            var upnlNum = parseFloat(upnl);
            var upnlClass = (typeof upnlNum === 'number' && !isNaN(upnlNum)) ? (upnlNum >= 0 ? 'value positive' : 'value negative') : '';
            var upnlStr = (typeof upnlNum === 'number' && !isNaN(upnlNum)) ? upnlNum.toFixed(4) : (upnl || '—');
            return '<tr data-symbol="' + sym + '" data-side="' + closeSide + '" data-quantity="' + qty + '" data-position-side="' + posSide + '">' +
                '<td><a href="' + href + '">' + sym + '</a></td>' +
                '<td><span class="badge ' + (side === 'LONG' ? 'badge-long' : 'badge-short') + '">' + side + '</span></td>' +
                '<td>' + (pos.positionAmt ?? '—') + '</td><td>' + (pos.entryPrice ?? '—') + '</td>' +
                '<td>' + (pos.markPrice ?? '—') + '</td><td class="' + upnlClass + '">' + upnlStr + '</td>' +
                '<td>' + (pos.leverage ?? '—') + '</td>' +
                '<td><button type="button" class="btn-close btn-close-position">Закрыть</button></td></tr>';
        }
        function renderOpenOrderRow(o) {
            var price = o.stopPrice || o.triggerPrice || o.price || '—';
            var qty = o.origQty || o.quantity || '—';
            var status = o.status || o.algoStatus || '—';
            var type = o.type || o.orderType || '—';
            var t = o.time || o.createTime || 0;
            return '<tr><td>' + (o.symbol || '—') + '</td><td>' + (o.side || '—') + '</td><td>' + type + '</td><td>' + price + '</td><td>' + qty + '</td><td>' + status + '</td><td>' + formatTime(t) + '</td></tr>';
        }
        function renderOrderHistoryRow(o) {
            var sym = o._symbol || o.symbol || '—';
            return '<tr><td>' + sym + '</td><td>' + (o.orderId || '—') + '</td><td>' + (o.side || '—') + '</td><td>' + (o.type || '—') + '</td><td>' + (o.price || o.stopPrice || '—') + '</td><td>' + (o.origQty || '—') + '</td><td>' + (o.status || '—') + '</td><td>' + formatTime(o.time) + '</td></tr>';
        }
        function renderTradeHistoryRow(t) {
            var sym = t._symbol || t.symbol || '—';
            return '<tr><td>' + sym + '</td><td>' + (t.side || '—') + '</td><td>' + (t.price || '—') + '</td><td>' + (t.qty || '—') + '</td><td>' + (t.commission || '—') + ' ' + (t.commissionAsset || '') + '</td><td>' + (t.realizedPnl ?? '—') + '</td><td>' + formatTime(t.time) + '</td></tr>';
        }
        function renderIncomeRow(i) {
            return '<tr><td>' + (i.symbol || '—') + '</td><td>' + (i.incomeType || '—') + '</td><td>' + (i.income || '—') + '</td><td>' + (i.asset || '—') + '</td><td>' + formatTime(i.time) + '</td></tr>';
        }
        function renderAssetRow(a) {
            if (parseFloat(a.walletBalance || 0) === 0 && parseFloat(a.crossWalletBalance || 0) === 0) return '';
            return '<tr><td>' + (a.asset || '—') + '</td><td>' + (a.walletBalance ?? '—') + '</td><td>' + (a.unrealizedProfit ?? '—') + '</td><td>' + (a.crossWalletBalance ?? '—') + '</td></tr>';
        }
        function bindCloseButtons() {
            document.querySelectorAll('#positions-tbody .btn-close-position').forEach(function(btn) {
                btn.onclick = function() {
                    var tr = btn.closest('tr');
                    if (!tr) return;
                    document.getElementById('modalClosePosText').textContent = 'Позиция ' + tr.dataset.symbol + ' будет закрыта по рыночной цене.';
                    document.getElementById('modalClosePosConfirm').onclick = function() {
                        closeModal('modalClosePosition');
                        var fd = new FormData();
                        fd.append('_token', csrf);
                        fd.append('symbol', tr.dataset.symbol);
                        fd.append('side', tr.dataset.side);
                        fd.append('quantity', tr.dataset.quantity);
                        if (tr.dataset.positionSide && (tr.dataset.positionSide === 'LONG' || tr.dataset.positionSide === 'SHORT'))
                            fd.append('position_side', tr.dataset.positionSide);
                        fetch(closePositionUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                            .then(function(r) { return r.json(); })
                            .then(function(res) {
                                if (res.error) alert(res.error);
                                else refreshLight();
                            })
                            .catch(function() { alert('Ошибка запроса'); });
                    };
                    openModal('modalClosePosition');
                };
            });
        }
        function updateLightTables(data) {
            updatePnLCards(data);
            var list = data.activePositions || [];
            var tbody = document.getElementById('positions-tbody');
            if (tbody) {
                if (list.length === 0) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:24px; color:#94a3b8;">Нет активных позиций.</td></tr>';
                else tbody.innerHTML = list.map(renderPositionRow).join('');
                bindCloseButtons();
            }
            if (data.openOrders !== undefined) {
                list = data.openOrders || [];
                tbody = document.getElementById('open-orders-tbody');
                if (tbody) {
                    if (list.length === 0) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">Нет открытых ордеров.</td></tr>';
                    else tbody.innerHTML = list.map(renderOpenOrderRow).join('');
                }
            }
        }
        function updateAllTables(data) {
            updatePnLCards(data);
            var list = data.activePositions || [];
            var tbody = document.getElementById('positions-tbody');
            if (tbody) {
                if (list.length === 0) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:24px; color:#94a3b8;">Нет активных позиций.</td></tr>';
                else tbody.innerHTML = list.map(renderPositionRow).join('');
                bindCloseButtons();
            }
            list = data.openOrders || [];
            tbody = document.getElementById('open-orders-tbody');
            if (tbody) {
                if (list.length === 0) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">Нет открытых ордеров.</td></tr>';
                else tbody.innerHTML = list.map(renderOpenOrderRow).join('');
            }
            list = data.orderHistory || [];
            tbody = document.getElementById('order-history-tbody');
            if (tbody) {
                if (list.length === 0) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:24px; color:#94a3b8;">Нет ордеров.</td></tr>';
                else tbody.innerHTML = list.map(renderOrderHistoryRow).join('');
            }
            list = data.tradeHistory || [];
            tbody = document.getElementById('trade-history-tbody');
            if (tbody) {
                if (list.length === 0) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">Нет сделок.</td></tr>';
                else tbody.innerHTML = list.map(renderTradeHistoryRow).join('');
            }
            list = data.income || [];
            tbody = document.getElementById('income-tbody');
            if (tbody) {
                if (list.length === 0) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color:#94a3b8;">Нет транзакций.</td></tr>';
                else tbody.innerHTML = list.map(renderIncomeRow).join('');
            }
            list = (data.assets || []).filter(function(a) { return parseFloat(a.walletBalance || 0) !== 0 || parseFloat(a.crossWalletBalance || 0) !== 0; });
            tbody = document.getElementById('assets-tbody');
            if (tbody) {
                if (list.length === 0) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px; color:#94a3b8;">Нет активов.</td></tr>';
                else tbody.innerHTML = list.map(renderAssetRow).join('');
            }
        }
        function refreshAccountData() {
            fetch(accountDataUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.error) return;
                    updateAllTables(data);
                })
                .catch(function() {});
        }
        function refreshLight() {
            if (refreshInFlight) return;
            refreshInFlight = true;
            fetch(accountDataLightUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.error) return;
                    updateLightTables(data);
                })
                .catch(function() {})
                .finally(function() { refreshInFlight = false; });
        }
        function applyAccountUpdateFromStream(msg) {
            if (msg.e !== 'ACCOUNT_UPDATE' || !msg.a) return;
            var data = msg.a;
            var p = data.P || data.p || [];
            var balances = data.B || data.a || [];
            var totalUp = 0;
            var positions = [];
            for (var i = 0; i < p.length; i++) {
                var x = p[i];
                var pa = parseFloat(x.pa || 0);
                if (pa === 0) continue;
                totalUp += parseFloat(x.up || 0);
                positions.push({
                    symbol: x.s || '',
                    positionAmt: String(pa),
                    entryPrice: x.ep || '—',
                    markPrice: x.markPrice || '—',
                    unRealizedProfit: x.up,
                    leverage: x.leverage || '—',
                    positionSide: x.ps || 'BOTH'
                });
            }
            var wb = 0, cw = 0;
            if (balances && balances.length) {
                for (var j = 0; j < balances.length; j++) {
                    wb += parseFloat(balances[j].wb || 0);
                    cw += parseFloat(balances[j].cw || 0);
                }
            }
            updateLightTables({
                totalUnrealizedProfit: totalUp,
                totalWalletBalance: wb || undefined,
                totalMarginBalance: (wb + totalUp) || undefined,
                activePositions: positions
            });
        }
        function connectSocket() {
            fetch(streamUrlApi, { headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.error || !data.wsUrl) {
                        setStatus(false, 'Ошибка сокета: ' + (data.error || 'нет URL'));
                        return;
                    }
                    ws = new WebSocket(data.wsUrl);
                    ws.onopen = function() { setStatus(true); startRefreshEverySecond(); };
                    ws.onclose = function() { setStatus(false, 'Сокет Binance отключён'); };
                    ws.onerror = function() { setStatus(false, 'Ошибка соединения с Binance'); };
                    ws.onmessage = function(ev) {
                        try {
                            var msg = JSON.parse(ev.data);
                            if (!msg || typeof msg !== 'object' || !msg.e) return;
                            if (msg.e === 'ACCOUNT_UPDATE') {
                                applyAccountUpdateFromStream(msg);
                            } else if (msg.e === 'ORDER_TRADE_UPDATE' || msg.e === 'ACCOUNT_CONFIG_UPDATE') {
                                refreshLight();
                            }
                        } catch (e) {}
                    };
                })
                .catch(function() { setStatus(false, 'Ошибка: не удалось получить URL сокета'); });
        }
        window.addEventListener('beforeunload', function() { stopRefreshEverySecond(); });
        function startRefreshEverySecond() {
            if (refreshIntervalId) return;
            refreshIntervalId = setInterval(function() { refreshLight(); }, REFRESH_EVERY_MS);
        }
        function stopRefreshEverySecond() {
            if (refreshIntervalId) {
                clearInterval(refreshIntervalId);
                refreshIntervalId = null;
            }
        }
        document.querySelectorAll('.btn-close-position').forEach(function(btn) {
            btn.onclick = function() {
                var tr = btn.closest('tr');
                if (!tr) return;
                document.getElementById('modalClosePosText').textContent = 'Позиция ' + tr.dataset.symbol + ' будет закрыта по рыночной цене.';
                document.getElementById('modalClosePosConfirm').onclick = function() {
                    closeModal('modalClosePosition');
                    var fd = new FormData();
                    fd.append('_token', csrf);
                    fd.append('symbol', tr.dataset.symbol);
                    fd.append('side', tr.dataset.side);
                    fd.append('quantity', tr.dataset.quantity);
                    if (tr.dataset.positionSide && (tr.dataset.positionSide === 'LONG' || tr.dataset.positionSide === 'SHORT'))
                        fd.append('position_side', tr.dataset.positionSide);
                    fetch(closePositionUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            if (res.error) alert(res.error);
                            else refreshLight();
                        })
                        .catch(function() { alert('Ошибка запроса'); });
                };
                openModal('modalClosePosition');
            };
        });
        setStatus(false, 'Подключение к сокету Binance…');
        connectSocket();
        startRefreshEverySecond();
    })();
    </script>
    @endif
    </div>

{{-- Close position modal --}}
<div class="modal-overlay" id="modalClosePosition">
    <div class="modal">
        <div class="modal-icon">⚠️</div>
        <h3>Закрыть позицию?</h3>
        <p id="modalClosePosText">Позиция будет закрыта по рыночной цене.</p>
        <div class="modal-btns">
            <button class="modal-btn modal-btn-cancel" onclick="closeModal('modalClosePosition')">Отмена</button>
            <button class="modal-btn modal-btn-confirm" id="modalClosePosConfirm">Закрыть</button>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active').forEach(function(m) { m.classList.remove('active'); }); });
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('mousedown', function(e) { if (e.target === overlay) closeModal(overlay.id); });
});
</script>
@endsection
