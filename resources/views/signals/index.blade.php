<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Trading Helper – Сигналы и стратегии</title>
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
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px 16px 40px 16px;
        }

        .header {
            text-align: center;
            padding: 32px 16px 20px 16px;
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

        .logo-container {
            margin-bottom: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-image {
            max-width: 140px;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 4px 16px rgba(248, 113, 113, 0.4));
        }

        .header-title {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 6px;
        }

        .header-subtitle {
            font-size: 14px;
            color: #94a3b8;
        }

        .tabs {
            display: inline-flex;
            margin-top: 24px;
            padding: 4px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.5);
        }

        .tab {
            border-radius: 999px;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #e5e7eb;
            transition: all 0.25s ease;
        }

        .tab-active {
            background: linear-gradient(135deg, #a855f7, #ec4899);
            color: #f9fafb;
            box-shadow: 0 8px 20px rgba(168, 85, 247, 0.4);
        }

        .filters-bar {
            margin-top: 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }

        .filters-left {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .label {
            font-size: 12px;
            color: #cbd5e1;
        }

        .input {
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(15, 23, 42, 0.9);
            padding: 6px 10px;
            font-size: 12px;
            color: #e5e7eb;
            outline: none;
        }

        .input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 1px rgba(168, 85, 247, 0.7);
        }

        .filter-btn {
            border-radius: 8px;
            border: none;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 500;
            color: #f9fafb;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(168, 85, 247, 0.5);
        }

        .export-btn {
            border-radius: 8px;
            border: 1px solid rgba(52, 211, 153, 0.6);
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 500;
            color: #bbf7d0;
            background: rgba(6, 95, 70, 0.45);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .export-btn:hover {
            background: rgba(16, 185, 129, 0.4);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45);
        }

        .table-wrapper {
            margin-top: 24px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(15, 23, 42, 0.9);
            overflow: hidden;
        }

        .table-scroll {
            max-height: 520px;
            overflow: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        thead {
            position: sticky;
            top: 0;
            z-index: 1;
            background: rgba(15, 23, 42, 0.95);
        }

        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid rgba(51, 65, 85, 0.8);
            white-space: nowrap;
        }

        th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #94a3b8;
        }

        tbody tr:nth-child(even) {
            background: rgba(15, 23, 42, 0.7);
        }

        tbody tr:hover {
            background: rgba(30, 64, 175, 0.35);
        }

        .badge-buy {
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            background: rgba(34, 197, 94, 0.15);
            color: #bbf7d0;
            border: 1px solid rgba(34, 197, 94, 0.5);
        }

        .badge-sell {
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            background: rgba(239, 68, 68, 0.15);
            color: #fecaca;
            border: 1px solid rgba(239, 68, 68, 0.6);
        }

        .badge-strength {
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            border: 1px solid rgba(168, 85, 247, 0.6);
            background: rgba(168, 85, 247, 0.15);
            color: #e9d5ff;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            font-size: 11px;
            color: #94a3b8;
            background: rgba(15, 23, 42, 0.95);
        }

        .pagination-links a,
        .pagination-links span {
            margin-right: 6px;
            padding: 3px 7px;
            border-radius: 6px;
            text-decoration: none;
            color: #cbd5e1;
            border: 1px solid transparent;
        }

        .pagination-links a:hover {
            border-color: rgba(148, 163, 184, 0.7);
        }

        .pagination-links .active {
            border-color: rgba(168, 85, 247, 0.9);
            background: rgba(168, 85, 247, 0.3);
        }

        .empty-state {
            padding: 24px;
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
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

            <h1 class="header-title">Сигналы и стратегии</h1>
            <p class="header-subtitle">
                История сигналов EMA + RSI + MACD с сильным и слабым фильтром, экспорт в Excel
            </p>

            <div class="tabs">
                <button
                    class="tab {{ $filter === 'strong' ? 'tab-active' : '' }}"
                    onclick="window.location='{{ route('signals.index', array_filter(['filter' => 'strong', 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}'"
                >
                    Сильный фильтр
                </button>
                <button
                    class="tab {{ $filter === 'weak' ? 'tab-active' : '' }}"
                    onclick="window.location='{{ route('signals.index', array_filter(['filter' => 'weak', 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}'"
                >
                    Слабый фильтр
                </button>
            </div>
        </header>

        <section class="filters-bar">
            <form method="GET" action="{{ route('signals.index') }}" class="filters-left">
                <input type="hidden" name="filter" value="{{ $filter }}">

                <div>
                    <label class="label" for="date_from">Дата от</label>
                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        value="{{ $dateFrom }}"
                        class="input"
                    >
                </div>

                <div>
                    <label class="label" for="date_to">Дата до</label>
                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        value="{{ $dateTo }}"
                        class="input"
                    >
                </div>

                <div style="margin-top: 18px;">
                    <button type="submit" class="filter-btn">
                        Применить фильтр по дате
                    </button>
                </div>
            </form>

            <div style="margin-top: 18px;">
                <a
                    href="{{ route('signals.export', array_filter(['filter' => $filter, 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}"
                    class="export-btn"
                >
                    ⬇ Сохранить как Excel (CSV)
                </a>
            </div>
        </section>

        <section class="table-wrapper">
            @if ($signals->isEmpty())
                <div class="empty-state">
                    Нет сигналов по выбранным условиям.
                </div>
            @else
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Время сигнала</th>
                                <th>Символ</th>
                                <th>Тип</th>
                                <th>Сила</th>
                                <th>Цена</th>
                                <th>RSI</th>
                                <th>EMA20</th>
                                <th>EMA50</th>
                                <th>MACD</th>
                                <th>Hist</th>
                                <th>ATR</th>
                                <th>SL</th>
                                <th>TP</th>
                                <th>Long score</th>
                                <th>Short score</th>
                                <th>Long %</th>
                                <th>Short %</th>
                                <th>Интервал</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($signals as $signal)
                                <tr>
                                    <td>{{ optional($signal->signal_time ?? $signal->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>{{ $signal->symbol }}</td>
                                    <td>
                                        @if ($signal->type === 'BUY')
                                            <span class="badge-buy">BUY</span>
                                        @elseif ($signal->type === 'SELL')
                                            <span class="badge-sell">SELL</span>
                                        @else
                                            {{ $signal->type }}
                                        @endif
                                    </td>
                                    <td><span class="badge-strength">{{ $signal->strength }}</span></td>
                                    <td>{{ number_format((float) $signal->price, 4, '.', ' ') }}</td>
                                    <td>{{ number_format((float) $signal->rsi, 2, '.', ' ') }}</td>
                                    <td>{{ number_format((float) $signal->ema, 4, '.', ' ') }}</td>
                                    <td>{{ number_format((float) $signal->ema_slow, 4, '.', ' ') }}</td>
                                    <td>{{ number_format((float) $signal->macd, 4, '.', ' ') }}</td>
                                    <td>{{ number_format((float) $signal->macd_histogram, 4, '.', ' ') }}</td>
                                    <td>{{ number_format((float) $signal->atr, 4, '.', ' ') }}</td>
                                    <td>{{ number_format((float) $signal->stop_loss, 4, '.', ' ') }}</td>
                                    <td>{{ number_format((float) $signal->take_profit, 4, '.', ' ') }}</td>
                                    <td>{{ $signal->long_score }}</td>
                                    <td>{{ $signal->short_score }}</td>
                                    <td>{{ $signal->long_probability }}%</td>
                                    <td>{{ $signal->short_probability }}%</td>
                                    <td>{{ $signal->interval }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <div>
                        Показано {{ $signals->firstItem() }}–{{ $signals->lastItem() }} из {{ $signals->total() }} сигналов
                    </div>
                    <div class="pagination-links">
                        @if ($signals->onFirstPage())
                            <span>«</span>
                        @else
                            <a href="{{ $signals->previousPageUrl() }}">«</a>
                        @endif

                        @foreach ($signals->getUrlRange(1, $signals->lastPage()) as $page => $url)
                            @if ($page == $signals->currentPage())
                                <span class="active">{{ $page }}</span>
                            @elseif ($page == 1 || $page == $signals->lastPage() || abs($page - $signals->currentPage()) <= 2)
                                <a href="{{ $url }}">{{ $page }}</a>
                            @elseif ($page == 2 || $page == $signals->lastPage() - 1)
                                <span>…</span>
                            @endif
                        @endforeach

                        @if ($signals->hasMorePages())
                            <a href="{{ $signals->nextPageUrl() }}">»</a>
                        @else
                            <span>»</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
</body>
</html>

