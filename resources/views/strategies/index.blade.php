@extends('layouts.app')

@section('title', 'Стратегии')
@section('page-title', 'Стратегии')

@push('styles')
<style>
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; text-decoration:none; transition:all .2s; }
.btn-primary { background:linear-gradient(135deg,#a855f7,#ec4899); color:#fff; }
.btn-primary:hover { opacity:.9; }
.btn-sm { padding:5px 12px; font-size:12px; }
.btn-outline { background:transparent; border:1px solid rgba(168,85,247,.4); color:#a855f7; }
.btn-outline:hover { background:rgba(168,85,247,.1); }
.btn-danger { background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.3); color:#fca5a5; }
.btn-danger:hover { background:rgba(239,68,68,.25); }

.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.page-header h1 { font-size:22px; font-weight:700; }
.page-header p { color:#94a3b8; font-size:13px; margin-top:2px; }

.strategies-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:20px; }

.strategy-card {
    background:rgba(20,14,40,.7);
    border:1px solid rgba(168,85,247,.15);
    border-radius:14px;
    padding:20px;
    display:flex;
    flex-direction:column;
    gap:14px;
    transition:border-color .2s;
}
.strategy-card:hover { border-color:rgba(168,85,247,.35); }
.strategy-card.inactive { opacity:.6; }

.card-header { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
.card-title { font-size:15px; font-weight:700; color:#f1f5f9; }
.card-desc { font-size:12px; color:#94a3b8; margin-top:3px; }

.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; }
.badge-active   { background:rgba(14,203,129,.15); border:1px solid rgba(14,203,129,.3); color:#0ecb81; }
.badge-inactive { background:rgba(100,116,139,.15); border:1px solid rgba(100,116,139,.3); color:#94a3b8; }
.badge-telegram    { background:rgba(0,136,204,.15); border:1px solid rgba(0,136,204,.3); color:#38bdf8; }
.badge-autotrading { background:rgba(168,85,247,.15); border:1px solid rgba(168,85,247,.3); color:#a855f7; }
.badge-manual      { background:rgba(100,116,139,.15); border:1px solid rgba(100,116,139,.3); color:#94a3b8; }

.card-meta { display:flex; flex-wrap:wrap; gap:8px; font-size:12px; color:#94a3b8; }
.card-meta-item { display:flex; align-items:center; gap:4px; }

.card-stats { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
.stat-box { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07); border-radius:8px; padding:10px; text-align:center; }
.stat-num { font-size:18px; font-weight:700; }
.stat-lbl { font-size:10px; color:#94a3b8; margin-top:2px; }
.stat-green { color:#0ecb81; }
.stat-red   { color:#f6465d; }
.stat-acc   { color:#a855f7; }

.card-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:auto; }

.empty-state {
    grid-column: 1/-1;
    text-align:center;
    padding:80px 20px;
    color:#94a3b8;
}
.empty-state h2 { font-size:20px; margin-bottom:10px; color:#f1f5f9; }
.empty-state p { font-size:14px; margin-bottom:24px; }

.alert-success {
    padding:12px 16px;
    background:rgba(14,203,129,.1);
    border:1px solid rgba(14,203,129,.3);
    border-radius:10px;
    color:#0ecb81;
    font-size:13px;
    margin-bottom:20px;
}
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

<div class="page-header">
    <div>
        <h1>Мои стратегии</h1>
        <p>Создавайте автоматические торговые стратегии на основе индикаторов</p>
    </div>
    <a href="{{ route('strategies.create') }}" class="btn btn-primary">+ Новая стратегия</a>
</div>

<div class="strategies-grid">
    @forelse($strategies as $strategy)
        @php
            $done   = $strategy->signals->where('status','DONE')->count();
            $total  = $strategy->signals->whereIn('status',['DONE','MISSED'])->count();
            $win    = $total > 0 ? round($done/$total*100,1) : 0;
            $sigCnt = $strategy->signals_count ?? 0;
        @endphp
        <div class="strategy-card {{ $strategy->is_active ? '' : 'inactive' }}">
            <div class="card-header">
                <div>
                    <div class="card-title">{{ $strategy->name }}</div>
                    @if($strategy->description)
                        <div class="card-desc">{{ Str::limit($strategy->description, 60) }}</div>
                    @endif
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;align-items:flex-end;">
                    <span class="badge {{ $strategy->is_active ? 'badge-active' : 'badge-inactive' }}">
                        {{ $strategy->is_active ? '● Активна' : '○ Стоп' }}
                    </span>
                    <span class="badge badge-{{ $strategy->mode }}">
                        {{ match($strategy->mode) { 'telegram'=>'Telegram','autotrading'=>'Авто-трейд',default=>'Ручной' } }}
                    </span>
                </div>
            </div>

            <div class="card-meta">
                <span class="card-meta-item">📈 {{ $strategy->symbol }}</span>
                <span class="card-meta-item">⏱ {{ $strategy->interval }}</span>
                <span class="card-meta-item">🔢 {{ $strategy->conditions_count }} условий</span>
            </div>

            <div class="card-stats">
                <div class="stat-box">
                    <div class="stat-num stat-acc">{{ $sigCnt }}</div>
                    <div class="stat-lbl">Сигналов</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num stat-green">{{ $done }}</div>
                    <div class="stat-lbl">DONE</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num {{ $win >= 50 ? 'stat-green' : ($win > 0 ? 'stat-red' : '') }}">
                        {{ $total > 0 ? $win.'%' : '—' }}
                    </div>
                    <div class="stat-lbl">Win rate</div>
                </div>
            </div>

            <div class="card-actions">
                <a href="{{ route('strategies.show', $strategy) }}" class="btn btn-sm btn-outline">Открыть</a>
                <a href="{{ route('strategies.edit', $strategy) }}" class="btn btn-sm btn-outline">Изменить</a>
                <a href="{{ route('strategies.backtest', $strategy) }}" class="btn btn-sm btn-outline">Бэктест</a>
                <form method="POST" action="{{ route('strategies.toggle', $strategy) }}" style="margin-left:auto;">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $strategy->is_active ? 'btn-danger' : 'btn-outline' }}">
                        {{ $strategy->is_active ? 'Стоп' : 'Старт' }}
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <h2>Нет стратегий</h2>
            <p>Создайте первую автоматическую торговую стратегию на основе ваших индикаторов</p>
            <a href="{{ route('strategies.create') }}" class="btn btn-primary">Создать стратегию</a>
        </div>
    @endforelse
</div>

@if($strategies->hasPages())
    <div style="margin-top:24px; display:flex; justify-content:center;">
        {{ $strategies->links() }}
    </div>
@endif
@endsection
