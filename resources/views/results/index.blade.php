<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Trading Helper – Результаты</title>
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

        .controls {
            margin-top: 28px;
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.6);
            display: flex;
            flex-wrap: wrap;
            gap: 12px 20px;
            align-items: flex-end;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 3px;
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
            width: 120px;
        }

        .input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 1px rgba(168, 85, 247, 0.7);
        }

        .hint {
            font-size: 11px;
            color: #9ca3af;
        }

        .apply-btn {
            border-radius: 8px;
            border: none;
            padding: 7px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #f9fafb;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .apply-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(168, 85, 247, 0.5);
        }

        .table-wrapper {
            margin-top: 22px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(15, 23, 42, 0.95);
            overflow: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        thead {
            background: rgba(15, 23, 42, 0.98);
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
            background: rgba(15, 23, 42, 0.85);
        }

        tbody tr:hover {
            background: rgba(30, 64, 175, 0.35);
        }

        .number-positive {
            color: #bbf7d0;
        }

        .number-negative {
            color: #fecaca;
        }

        .summary {
            margin-top: 14px;
            font-size: 12px;
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

            <h1 class="header-title">Результаты стратегии</h1>
            <p class="header-subtitle">
                Ежедневная сводка по выполненным и пропущенным сигналам, средним стопам/тейкам и общему профиту.
            </p>

            <div class="tabs">
                <button
                    class="tab {{ $filter === 'strong' ? 'tab-active' : '' }}"
                    onclick="window.location='{{ route('results.index', ['filter' => 'strong', 'capital' => $capital, 'leverage' => $leverage]) }}'"
                >
                    Сильный фильтр
                </button>
                <button
                    class="tab {{ $filter === 'weak' ? 'tab-active' : '' }}"
                    onclick="window.location='{{ route('results.index', ['filter' => 'weak', 'capital' => $capital, 'leverage' => $leverage]) }}'"
                >
                    Слабый фильтр
                </button>
            </div>
        </header>

        <section class="controls">
            <form method="GET" action="{{ route('results.index') }}" style="display:flex;flex-wrap:wrap;gap:12px 20px;align-items:flex-end;">
                <input type="hidden" name="filter" value="{{ $filter }}">

                <div class="field">
                    <label for="capital" class="label">Сумма входа (USD)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="1"
                        id="capital"
                        name="capital"
                        value="{{ $capital }}"
                        class="input"
                    >
                </div>

                <div class="field">
                    <label for="leverage" class="label">Плечо</label>
                    <input
                        type="number"
                        step="1"
                        min="1"
                        id="leverage"
                        name="leverage"
                        value="{{ $leverage }}"
                        class="input"
                    >
                </div>

                <button type="submit" class="apply-btn">
                    Пересчитать PROFIT
                </button>
            </form>

            <div class="hint">
                PROFIT считается как (прибыль − потери) при сумме входа = capital и плече = leverage.
            </div>
        </section>

        <section class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Done BUY</th>
                        <th>Done SELL</th>
                        <th>Missed BUY</th>
                        <th>Missed SELL</th>
                        <th>Total DONE</th>
                        <th>Total MISSED</th>
                        <th>Avg TP DONE %</th>
                        <th>Avg SL MISSED %</th>
                        <th>PROFIT (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalProfit = 0;
                        $totalDone = 0;
                        $totalMissed = 0;
                    @endphp
                    @forelse ($rows as $row)
                        @php
                            $profit = (float) $row->profit;
                            $totalProfit += $profit;
                            $totalDone += (int) $row->total_done;
                            $totalMissed += (int) $row->total_missed;
                        @endphp
                        <tr>
                            <td>{{ $row->day }}</td>
                            <td>{{ $row->done_buy }}</td>
                            <td>{{ $row->done_sell }}</td>
                            <td>{{ $row->missed_buy }}</td>
                            <td>{{ $row->missed_sell }}</td>
                            <td>{{ $row->total_done }}</td>
                            <td>{{ $row->total_missed }}</td>
                            <td>{{ number_format((float) $row->avg_take_done, 2, '.', ' ') }}</td>
                            <td>{{ number_format((float) $row->avg_stop_missed, 2, '.', ' ') }}</td>
                            <td class="{{ $profit >= 0 ? 'number-positive' : 'number-negative' }}">
                                {{ number_format($profit, 2, '.', ' ') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center;padding:16px 10px;color:#9ca3af;">
                                Нет данных для выбранных условий.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div class="summary">
            Всего дней: {{ count($rows) }}
            · DONE: {{ $totalDone }}
            · MISSED: {{ $totalMissed }}
            · Суммарный PROFIT: 
            <span class="{{ $totalProfit >= 0 ? 'number-positive' : 'number-negative' }}">
                {{ number_format($totalProfit, 2, '.', ' ') }} USD
            </span>
        </div>
    </div>
</body>
</html>

