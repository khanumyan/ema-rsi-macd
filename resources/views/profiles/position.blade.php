@extends('layouts.app')
@section('page-title', $symbol . ' — позиция')
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
        .container { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; }
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
        .home-chip { padding: 4px 10px; border-radius: 999px; border: 1px solid rgba(148, 163, 184, 0.7); background: rgba(15, 23, 42, 0.9); color: #e5e7eb; text-decoration: none; font-size: 12px; }
        .home-chip:hover { border-color: #a855f7; color: #e9d5ff; }
        .user-email { font-size: 12px; color: #9ca3af; }
        .logo-container { margin-bottom: 16px; display: flex; justify-content: center; align-items: center; }
        .logo-image { max-width: 140px; height: auto; filter: drop-shadow(0 4px 16px rgba(248, 113, 113, 0.4)); }
        .header-title { font-size: 28px; font-weight: bold; background: linear-gradient(to right, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 6px; }
        .header-subtitle { font-size: 14px; color: #94a3b8; }
        .breadcrumb { margin-bottom: 20px; font-size: 13px; color: #94a3b8; }
        .breadcrumb a { color: #a855f7; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .section-title { font-size: 16px; font-weight: 600; margin: 24px 0 12px; color: #e5e7eb; }
        .info-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px 24px;
            background: rgba(15, 23, 42, 0.95); border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.4);
            padding: 18px; margin-bottom: 20px; font-size: 13px;
        }
        .info-row { display: flex; justify-content: space-between; gap: 16px; padding: 6px 0; border-bottom: 1px solid rgba(51, 65, 85, 0.5); }
        .info-row:last-child { border-bottom: none; }
        .info-key { color: #94a3b8; }
        .info-val { color: #e5e7eb; word-break: break-all; }
        .chart-wrap { margin: 24px 0; border-radius: 16px; overflow: hidden; border: 1px solid rgba(148, 163, 184, 0.4); background: rgba(15, 23, 42, 0.6); padding: 12px; }
        .error-box { padding: 16px; border-radius: 12px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fecaca; margin-bottom: 20px; }
        .table-wrapper { margin-top: 12px; border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.5); background: rgba(15, 23, 42, 0.95); overflow: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 12px; border-bottom: 1px solid rgba(51, 65, 85, 0.8); text-align: left; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; color: #94a3b8; }
        tbody tr:hover { background: rgba(30, 64, 175, 0.25); }
    </style>
@endpush
@section('content')
    <div class="container" style="max-width:100%;padding:0">
        <div class="breadcrumb">
            <a href="{{ route('profiles.index') }}">Профили</a> &rarr;
            <a href="{{ route('profiles.show', $profile) }}">{{ $profile->profile_name }}</a> &rarr;
            {{ $symbol }}
        </div>

        @if ($error)
            <div class="error-box">{{ $error }}</div>
        @else
            @if ($position)
                <div class="section-title">Данные позиции (Binance)</div>
                <div class="info-grid">
                    @foreach ($position as $key => $value)
                        <div class="info-row">
                            <span class="info-key">{{ $key }}</span>
                            <span class="info-val">{{ is_array($value) || is_object($value) ? json_encode($value) : $value }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color:#94a3b8;">Позиция по этому символу не найдена. Возможно, она уже закрыта.</p>
            @endif

            <div class="section-title">График TradingView — {{ $symbol }}</div>
            <div class="chart-wrap">
                <div id="tradingview_chart" style="width:100%; height:500px;"></div>
            </div>

            @if (count($trades) > 0)
                <div class="section-title">История сделок по символу (последние {{ count($trades) }})</div>
                <section class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>OrderId</th>
                                <th>Сторона</th>
                                <th>Цена</th>
                                <th>Кол-во</th>
                                <th>Комиссия</th>
                                <th>Реализ. PNL</th>
                                <th>Время</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trades as $t)
                                <tr>
                                    <td>{{ $t['id'] ?? '—' }}</td>
                                    <td>{{ $t['orderId'] ?? '—' }}</td>
                                    <td>{{ $t['side'] ?? '—' }}</td>
                                    <td>{{ $t['price'] ?? '—' }}</td>
                                    <td>{{ $t['qty'] ?? '—' }}</td>
                                    <td>{{ $t['commission'] ?? '—' }} {{ $t['commissionAsset'] ?? '' }}</td>
                                    <td>{{ $t['realizedPnl'] ?? '—' }}</td>
                                    <td>{{ isset($t['time']) ? date('Y-m-d H:i', $t['time'] / 1000) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endif
        @endif
    </div>

    @if (!$error)
    <script src="https://s3.tradingview.com/tv.js"></script>
    <script>
        new TradingView.widget({
            "width": "100%",
            "height": 500,
            "symbol": "BINANCE:{{ $symbol }}",
            "interval": "D",
            "timezone": "Etc/UTC",
            "theme": "dark",
            "style": "1",
            "locale": "en",
            "toolbar_bg": "#f1f3f6",
            "enable_publishing": false,
            "container_id": "tradingview_chart"
        });
    </script>
    @endif
    </div>
@endsection
