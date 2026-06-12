@extends('layouts.app')

@section('title', 'Trading Helper – Dashboard')

@section('page-title', 'Dashboard')

@push('styles')
<style>
    .welcome-banner {
        background: linear-gradient(135deg, rgba(168,85,247,.15) 0%, rgba(236,72,153,.1) 100%);
        border: 1px solid rgba(168,85,247,.25);
        border-radius: 18px;
        padding: 28px 32px;
        margin-bottom: 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .welcome-text h2 {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 6px;
        background: linear-gradient(to right, #a855f7, #ec4899);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .welcome-text p { font-size: 14px; color: #94a3b8; }

    .welcome-logo img {
        height: 70px;
        filter: drop-shadow(0 4px 20px rgba(248,113,113,.5));
    }

    /* Quick stats */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: rgba(15, 10, 30, 0.7);
        border: 1px solid rgba(168,85,247,.2);
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        transition: all .25s;
    }

    .stat-card:hover {
        border-color: rgba(168,85,247,.45);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(168,85,247,.15);
    }

    .stat-icon { font-size: 24px; }
    .stat-value { font-size: 26px; font-weight: 700; color: #f1f5f9; }
    .stat-label { font-size: 12px; color: #94a3b8; }

    /* Section cards grid */
    .sections-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }

    .section-card {
        background: rgba(15, 10, 30, 0.65);
        border: 1px solid rgba(168,85,247,.2);
        border-radius: 16px;
        padding: 22px 22px 18px;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: all .25s;
        position: relative;
        overflow: hidden;
    }

    .section-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(to right, #a855f7, #ec4899);
        opacity: 0;
        transition: opacity .25s;
    }

    .section-card:hover {
        border-color: rgba(168,85,247,.5);
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(168,85,247,.18);
    }

    .section-card:hover::before { opacity: 1; }

    .section-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-card-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(168,85,247,.2), rgba(236,72,153,.15));
        border: 1px solid rgba(168,85,247,.3);
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
    }

    .section-card-title { font-size: 16px; font-weight: 700; color: #f1f5f9; }

    .section-card-desc {
        font-size: 12px;
        color: #94a3b8;
        line-height: 1.55;
    }

    .section-card-arrow {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #a855f7;
        margin-top: 4px;
    }

    /* Recent activity */
    .activity-section {
        background: rgba(15, 10, 30, 0.65);
        border: 1px solid rgba(168,85,247,.2);
        border-radius: 16px;
        padding: 20px;
    }

    .section-heading {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #f1f5f9;
    }

    .section-heading .dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #a855f7;
        box-shadow: 0 0 8px rgba(168,85,247,.8);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .3; }
    }

    .activity-list { display: flex; flex-direction: column; gap: 10px; }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: rgba(10, 5, 20, 0.5);
        border-radius: 10px;
        border: 1px solid rgba(168,85,247,.1);
    }

    .activity-badge {
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }

    .badge-buy  { background: rgba(14,203,129,.15); color: #0ecb81; border: 1px solid rgba(14,203,129,.3); }
    .badge-sell { background: rgba(246,70,93,.15);  color: #f6465d; border: 1px solid rgba(246,70,93,.3); }
    .badge-done { background: rgba(14,203,129,.15); color: #0ecb81; border: 1px solid rgba(14,203,129,.3); }
    .badge-miss { background: rgba(246,70,93,.15);  color: #f6465d; border: 1px solid rgba(246,70,93,.3); }
    .badge-proc { background: rgba(240,185,11,.12); color: #f0b90b; border: 1px solid rgba(240,185,11,.3); }

    .activity-info { flex: 1; }
    .activity-title { font-size: 13px; font-weight: 600; }
    .activity-meta  { font-size: 11px; color: #94a3b8; }
    .activity-time  { font-size: 11px; color: #64748b; white-space: nowrap; }
</style>
@endpush

@section('content')

    {{-- Welcome banner --}}
    <div class="welcome-banner">
        <div class="welcome-text">
            <h2>Добро пожаловать, {{ auth()->user()->name ?? 'Трейдер' }}!</h2>
            <p>Платформа технического анализа и автоматизации торговых сигналов на основе EMA + RSI + MACD</p>
        </div>
        <div class="welcome-logo">
            <img src="{{ asset('images/trading-helper-logo.png') }}" alt="Logo">
        </div>
    </div>

    {{-- Quick stats --}}
    @php
        $filteredBase = \App\Models\CryptoSignalNew::query()
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
            });

        $totalSignals  = (clone $filteredBase)->count();
        $doneSignals   = (clone $filteredBase)->where('status', 'DONE')->count();
        $missedSignals = (clone $filteredBase)->where('status', 'MISSED')->count();
        $todaySignals  = (clone $filteredBase)->whereDate('created_at', today())->count();
        $totalNews     = \App\Models\CryptoNews::count();
        $profiles      = \App\Models\Profile::count();
    @endphp

    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-icon">⚡</span>
            <div class="stat-value">{{ number_format($totalSignals) }}</div>
            <div class="stat-label">Всего сигналов</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">✅</span>
            <div class="stat-value" style="color:#0ecb81">{{ number_format($doneSignals) }}</div>
            <div class="stat-label">DONE (TP достигнут)</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">❌</span>
            <div class="stat-value" style="color:#f6465d">{{ number_format($missedSignals) }}</div>
            <div class="stat-label">MISSED (SL достигнут)</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">📅</span>
            <div class="stat-value">{{ $todaySignals }}</div>
            <div class="stat-label">Сигналов сегодня</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">📰</span>
            <div class="stat-value">{{ number_format($totalNews) }}</div>
            <div class="stat-label">Новостей в базе</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">👤</span>
            <div class="stat-value">{{ $profiles }}</div>
            <div class="stat-label">API профилей</div>
        </div>
    </div>

    {{-- Sections grid --}}
    <div class="sections-grid">
        <a href="{{ route('trading.index') }}" class="section-card">
            <div class="section-card-header">
                <div class="section-card-icon">📉</div>
                <div class="section-card-title">Трейдинг</div>
            </div>
            <div class="section-card-desc">
                Полный торговый терминал: TradingView график, стакан ордеров, BUY/SELL с плечом, Futures/Spot, TP/SL.
            </div>
            <div class="section-card-arrow">Открыть терминал <span>→</span></div>
        </a>

        <a href="{{ route('signals.index') }}" class="section-card">
            <div class="section-card-header">
                <div class="section-card-icon">⚡</div>
                <div class="section-card-title">Сигналы</div>
            </div>
            <div class="section-card-desc">
                История торговых сигналов EMA+RSI+MACD. Фильтрация по силе, типу и статусу исполнения.
            </div>
            <div class="section-card-arrow">Смотреть сигналы <span>→</span></div>
        </a>

        <a href="{{ route('results.index') }}" class="section-card">
            <div class="section-card-header">
                <div class="section-card-icon">📊</div>
                <div class="section-card-title">Результаты</div>
            </div>
            <div class="section-card-desc">
                Ежедневная статистика: DONE/MISSED сигналы, средний профит, итоговый P&L по дням.
            </div>
            <div class="section-card-arrow">Смотреть результаты <span>→</span></div>
        </a>

        <a href="{{ route('news.index') }}" class="section-card">
            <div class="section-card-header">
                <div class="section-card-icon">📰</div>
                <div class="section-card-title">Новости</div>
            </div>
            <div class="section-card-desc">
                Крипто-новости с фильтрацией по монетам. Клик на статью — связанные материалы.
            </div>
            <div class="section-card-arrow">Читать новости <span>→</span></div>
        </a>

        <a href="{{ route('profiles.index') }}" class="section-card">
            <div class="section-card-header">
                <div class="section-card-icon">👤</div>
                <div class="section-card-title">Профили</div>
            </div>
            <div class="section-card-desc">
                Управление API ключами Binance. PROD/TEST категории, баланс, открытые позиции.
            </div>
            <div class="section-card-arrow">Управление профилями <span>→</span></div>
        </a>
    </div>

    {{-- Recent signals --}}
    @php
        $recentSignals = \App\Models\CryptoSignalNew::whereIn('type',['BUY','SELL'])
            ->whereNotNull('status')
            ->latest()
            ->limit(6)
            ->get();
    @endphp

    @if($recentSignals->isNotEmpty())
    <div class="activity-section">
        <div class="section-heading">
            <div class="dot"></div>
            Последние сигналы
        </div>
        <div class="activity-list">
            @foreach($recentSignals as $sig)
            <div class="activity-item">
                <span class="activity-badge {{ $sig->type === 'BUY' ? 'badge-buy' : 'badge-sell' }}">
                    {{ $sig->type }}
                </span>
                <div class="activity-info">
                    <div class="activity-title">{{ $sig->symbol }}</div>
                    <div class="activity-meta">
                        Цена: {{ number_format($sig->price, 4) }} ·
                        TP: {{ $sig->take_profit ? number_format($sig->take_profit, 4) : '—' }} ·
                        SL: {{ $sig->stop_loss ? number_format($sig->stop_loss, 4) : '—' }}
                    </div>
                </div>
                <span class="activity-badge
                    {{ $sig->status === 'DONE' ? 'badge-done' : ($sig->status === 'MISSED' ? 'badge-miss' : 'badge-proc') }}">
                    {{ $sig->status ?? 'PROCESSING' }}
                </span>
                <span class="activity-time">{{ $sig->created_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

@endsection
