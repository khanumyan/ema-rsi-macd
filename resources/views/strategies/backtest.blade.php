@extends('layouts.app')

@section('title', 'Бэктест: ' . $strategy->name)
@section('page-title', 'Бэктест')

@push('styles')
<style>
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; text-decoration:none; transition:all .2s; }
.btn-primary { background:linear-gradient(135deg,#a855f7,#ec4899); color:#fff; }
.btn-sm { padding:5px 12px; font-size:12px; }
.btn-outline { background:transparent; border:1px solid rgba(168,85,247,.4); color:#a855f7; }

.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }

.card { background:rgba(20,14,40,.7); border:1px solid rgba(168,85,247,.15); border-radius:12px; padding:20px; margin-bottom:20px; }
.card h3 { font-size:14px; font-weight:700; margin-bottom:16px; }

.summary-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:14px; margin-bottom:24px; }
.stat-box { background:rgba(20,14,40,.7); border:1px solid rgba(168,85,247,.15); border-radius:12px; padding:16px; text-align:center; }
.stat-num { font-size:24px; font-weight:800; margin-bottom:4px; }
.stat-lbl { font-size:11px; color:#64748b; }

.monthly-table { width:100%; border-collapse:collapse; font-size:13px; }
.monthly-table th { padding:10px 14px; border-bottom:1px solid rgba(255,255,255,.07); color:#64748b; font-size:11px; text-transform:uppercase; text-align:left; }
.monthly-table td { padding:10px 14px; border-bottom:1px solid rgba(255,255,255,.04); }
.monthly-table tr:last-child td { border-bottom:none; }
.monthly-table tr:hover td { background:rgba(168,85,247,.05); }

.profit-bar { height:6px; border-radius:3px; display:inline-block; min-width:4px; margin-left:8px; vertical-align:middle; }

.signals-list { max-height:400px; overflow-y:auto; }
.signal-row { display:flex; align-items:center; gap:12px; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,.04); font-size:12px; }
.signal-row:last-child { border-bottom:none; }
.badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; }
.badge-buy    { background:rgba(14,203,129,.15); border:1px solid rgba(14,203,129,.25); color:#0ecb81; }
.badge-sell   { background:rgba(246,70,93,.15);  border:1px solid rgba(246,70,93,.25);  color:#f6465d; }
.badge-done   { background:rgba(14,203,129,.15); border:1px solid rgba(14,203,129,.25); color:#0ecb81; }
.badge-missed { background:rgba(246,70,93,.15);  border:1px solid rgba(246,70,93,.25);  color:#f6465d; }
.badge-proc   { background:rgba(234,179,8,.15);  border:1px solid rgba(234,179,8,.25);  color:#fbbf24; }

.params-form { display:flex; align-items:flex-end; gap:12px; }
.params-form .form-group label { font-size:11px; color:#64748b; margin-bottom:4px; display:block; text-transform:uppercase; }
.params-form input, .params-form select { padding:8px 12px; background:rgba(255,255,255,.06); border:1px solid rgba(168,85,247,.2); border-radius:8px; color:#f1f5f9; font-size:13px; outline:none; }

/* Chart area */
.chart-bars { display:flex; align-items:flex-end; gap:6px; height:120px; padding-top:10px; }
.chart-bar-wrap { flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; }
.chart-bar { width:100%; border-radius:4px 4px 0 0; min-height:4px; transition:height .3s; }
.chart-bar.pos { background:linear-gradient(to top,rgba(14,203,129,.4),rgba(14,203,129,.8)); }
.chart-bar.neg { background:linear-gradient(to top,rgba(246,70,93,.4),rgba(246,70,93,.8)); }
.chart-month-label { font-size:9px; color:#64748b; white-space:nowrap; overflow:hidden; width:100%; text-align:center; }
.chart-val { font-size:10px; font-weight:700; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <div style="font-size:12px;color:#64748b;margin-bottom:4px;">
            <a href="{{ route('strategies.show', $strategy) }}" style="color:#a855f7;text-decoration:none;">← {{ $strategy->name }}</a>
        </div>
        <div style="font-size:13px;color:#94a3b8;">{{ $strategy->symbol }} · {{ $strategy->interval }} · Исторические данные</div>
    </div>
    <form method="GET" action="{{ route('strategies.backtest', $strategy) }}" class="params-form">
        <div class="form-group">
            <label>Свечей истории</label>
            <select name="periods">
                <option value="100"  {{ request('periods',300)==100?'selected':'' }}>100</option>
                <option value="200"  {{ request('periods',300)==200?'selected':'' }}>200</option>
                <option value="300"  {{ request('periods',300)==300?'selected':'' }}>300</option>
                <option value="500"  {{ request('periods',300)==500?'selected':'' }}>500</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Запустить бэктест</button>
        <a href="{{ route('strategies.show', $strategy) }}" class="btn btn-outline btn-sm">Назад</a>
    </form>
</div>

@php $summary = $result['summary']; $monthly = $result['monthly']; $signals = $result['signals']; @endphp

{{-- Summary --}}
<div class="summary-grid">
    <div class="stat-box">
        <div class="stat-num" style="color:#a855f7">{{ $summary['total'] }}</div>
        <div class="stat-lbl">Всего сигналов</div>
    </div>
    <div class="stat-box">
        <div class="stat-num" style="color:#0ecb81">{{ $summary['done'] }}</div>
        <div class="stat-lbl">DONE (TP)</div>
    </div>
    <div class="stat-box">
        <div class="stat-num" style="color:#f6465d">{{ $summary['missed'] }}</div>
        <div class="stat-lbl">MISSED (SL)</div>
    </div>
    <div class="stat-box">
        <div class="stat-num" style="color:{{ $summary['win_rate']>=50?'#0ecb81':'#f6465d' }}">
            {{ $summary['win_rate'] }}%
        </div>
        <div class="stat-lbl">Win Rate</div>
    </div>
    <div class="stat-box">
        <div class="stat-num" style="color:{{ $summary['total_pct']>=0?'#0ecb81':'#f6465d' }}">
            {{ $summary['total_pct'] >= 0 ? '+' : '' }}{{ $summary['total_pct'] }}%
        </div>
        <div class="stat-lbl">Общий P&L %</div>
    </div>
    <div class="stat-box">
        <div class="stat-num" style="color:{{ $summary['avg_pct']>=0?'#0ecb81':'#f6465d' }}">
            {{ $summary['avg_pct'] >= 0 ? '+' : '' }}{{ $summary['avg_pct'] }}%
        </div>
        <div class="stat-lbl">Средний P&L %</div>
    </div>
</div>

@if(!empty($monthly))
{{-- Monthly Chart --}}
<div class="card">
    <h3>Прибыль по месяцам (P&L %)</h3>
    @php
        $maxAbs = max(array_map(fn($m)=>abs($m['profit_pct']), $monthly));
        $maxAbs = max($maxAbs, 0.01);
    @endphp
    <div class="chart-bars">
        @foreach($monthly as $month => $m)
        @php $h = max(4, abs($m['profit_pct']) / $maxAbs * 100); @endphp
        <div class="chart-bar-wrap">
            <span class="chart-val" style="color:{{ $m['profit_pct']>=0?'#0ecb81':'#f6465d' }}">
                {{ $m['profit_pct']>=0?'+':'' }}{{ round($m['profit_pct'],1) }}%
            </span>
            <div class="chart-bar {{ $m['profit_pct']>=0?'pos':'neg' }}" style="height:{{ $h }}px;"></div>
            <span class="chart-month-label">{{ substr($month,5,2) }}/{{ substr($month,2,2) }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- Monthly Table --}}
<div class="card">
    <h3>Статистика по месяцам</h3>
    <table class="monthly-table">
        <thead>
            <tr><th>Месяц</th><th>Сигналов</th><th>DONE</th><th>MISSED</th><th>Win %</th><th>P&L %</th></tr>
        </thead>
        <tbody>
            @foreach($monthly as $month => $m)
            @php
                $winR = $m['total'] > 0 ? round($m['done']/$m['total']*100,1) : 0;
                $pct  = round($m['profit_pct'],2);
                $barW = $maxAbs > 0 ? min(120, abs($pct)/$maxAbs*120) : 0;
            @endphp
            <tr>
                <td style="font-weight:600;">{{ $month }}</td>
                <td>{{ $m['total'] }}</td>
                <td style="color:#0ecb81">{{ $m['done'] }}</td>
                <td style="color:#f6465d">{{ $m['missed'] }}</td>
                <td style="color:{{ $winR>=50?'#0ecb81':'#f6465d' }}">{{ $winR }}%</td>
                <td>
                    <span style="color:{{ $pct>=0?'#0ecb81':'#f6465d' }};font-weight:700;">
                        {{ $pct>=0?'+':'' }}{{ $pct }}%
                    </span>
                    <span class="profit-bar {{ $pct>=0?'pos':'neg' }}"
                          style="width:{{ $barW }}px;background:{{ $pct>=0?'rgba(14,203,129,.5)':'rgba(246,70,93,.5)' }}"></span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Signals list --}}
@if(!empty($signals))
<div class="card">
    <h3>Все сигналы бэктеста ({{ count($signals) }})</h3>
    <div class="signals-list">
        @foreach(array_slice(array_reverse($signals), 0, 200) as $s)
        <div class="signal-row">
            <span class="badge badge-{{ strtolower($s['type']) }}">{{ $s['type'] }}</span>
            <span style="font-family:monospace;color:#94a3b8;">{{ $s['price'] }}</span>
            <span style="color:#64748b;font-size:11px;">→ TP {{ round($s['take_profit'],4) }}</span>
            <span class="badge badge-{{ $s['status']==='DONE'?'done':($s['status']==='MISSED'?'missed':'proc') }}">
                {{ $s['status'] }}
            </span>
            <span style="color:{{ $s['profit_pct']>=0?'#0ecb81':'#f6465d' }};font-weight:700;margin-left:auto;">
                {{ $s['profit_pct']>=0?'+':'' }}{{ round($s['profit_pct'],2) }}%
            </span>
            <span style="color:#64748b;font-size:11px;">{{ substr($s['triggered_at'],0,10) }}</span>
        </div>
        @endforeach
        @if(count($signals) > 200)
            <div style="text-align:center;padding:10px;color:#64748b;font-size:12px;">...ещё {{ count($signals)-200 }} сигналов</div>
        @endif
    </div>
</div>
@else
<div class="card">
    <p style="color:#64748b;text-align:center;padding:30px 0;">
        Ни одно условие не сработало за выбранный период. Попробуйте изменить условия стратегии или увеличить период.
    </p>
</div>
@endif
@endsection
