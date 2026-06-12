@extends('layouts.app')

@section('title', $strategy->name)
@section('page-title', e($strategy->name))

@push('styles')
<style>
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; text-decoration:none; transition:all .2s; }
.btn-primary { background:linear-gradient(135deg,#a855f7,#ec4899); color:#fff; }
.btn-sm { padding:5px 12px; font-size:12px; }
.btn-outline { background:transparent; border:1px solid rgba(168,85,247,.4); color:#a855f7; }
.btn-danger  { background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.3); color:#fca5a5; }

.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.page-header-actions { display:flex; gap:10px; }

.info-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; margin-bottom:24px; }
.info-card { background:rgba(20,14,40,.7); border:1px solid rgba(168,85,247,.15); border-radius:12px; padding:16px; }
.info-label { font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
.info-value { font-size:20px; font-weight:700; }
.info-value-sm { font-size:14px; font-weight:600; color:#f1f5f9; }

.two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
@media(max-width:700px){ .two-col { grid-template-columns:1fr; } }

.card { background:rgba(20,14,40,.7); border:1px solid rgba(168,85,247,.15); border-radius:12px; padding:20px; }
.card h3 { font-size:14px; font-weight:700; margin-bottom:14px; color:#f1f5f9; }

.condition-tag {
    display:inline-block; padding:4px 12px; border-radius:999px; font-size:12px; font-weight:600;
    margin:4px 3px; border:1px solid;
}
.condition-tag.buy  { background:rgba(14,203,129,.1); border-color:rgba(14,203,129,.3); color:#0ecb81; }
.condition-tag.sell { background:rgba(246,70,93,.1);  border-color:rgba(246,70,93,.3);  color:#f6465d; }
.logic-sep { font-size:10px; font-weight:700; color:#a855f7; margin:0 2px; }

.signals-table { width:100%; border-collapse:collapse; font-size:13px; }
.signals-table th { padding:10px 14px; border-bottom:1px solid rgba(255,255,255,.07); color:#64748b; font-weight:600; text-align:left; font-size:11px; text-transform:uppercase; }
.signals-table td { padding:10px 14px; border-bottom:1px solid rgba(255,255,255,.04); }
.signals-table tr:last-child td { border-bottom:none; }

.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:600; }
.badge-buy    { background:rgba(14,203,129,.15); border:1px solid rgba(14,203,129,.25); color:#0ecb81; }
.badge-sell   { background:rgba(246,70,93,.15);  border:1px solid rgba(246,70,93,.25);  color:#f6465d; }
.badge-done   { background:rgba(14,203,129,.15); border:1px solid rgba(14,203,129,.25); color:#0ecb81; }
.badge-missed { background:rgba(246,70,93,.15);  border:1px solid rgba(246,70,93,.25);  color:#f6465d; }
.badge-proc   { background:rgba(234,179,8,.15);  border:1px solid rgba(234,179,8,.25);  color:#fbbf24; }

.alert-success { padding:12px 16px; background:rgba(14,203,129,.1); border:1px solid rgba(14,203,129,.3); border-radius:10px; color:#0ecb81; font-size:13px; margin-bottom:20px; }

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
@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

<div class="page-header">
    <div>
        <div style="font-size:12px; color:#64748b; margin-bottom:4px;">
            <a href="{{ route('strategies.index') }}" style="color:#a855f7; text-decoration:none;">← Стратегии</a>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            @if($strategy->is_active)
                <span class="badge badge-done">● Активна</span>
            @else
                <span class="badge" style="background:rgba(100,116,139,.15);border:1px solid rgba(100,116,139,.3);color:#94a3b8;">○ Остановлена</span>
            @endif
            <span style="font-size:12px;color:#64748b;">{{ $strategy->symbol }} · {{ $strategy->interval }}</span>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('strategies.backtest', $strategy) }}" class="btn btn-sm btn-outline">Бэктест</a>
        <a href="{{ route('strategies.edit', $strategy) }}" class="btn btn-sm btn-outline">Изменить</a>
        <form method="POST" action="{{ route('strategies.toggle', $strategy) }}">
            @csrf
            <button type="submit" class="btn btn-sm {{ $strategy->is_active ? 'btn-danger' : 'btn-outline' }}">
                {{ $strategy->is_active ? 'Остановить' : 'Запустить' }}
            </button>
        </form>
        <form id="deleteStrategyForm" method="POST" action="{{ route('strategies.destroy', $strategy) }}">
            @csrf @method('DELETE')
            <button type="button" onclick="openModal('modalDeleteStrategy')" class="btn btn-sm btn-danger">Удалить</button>
        </form>
    </div>
</div>

{{-- Stats --}}
<div class="info-grid">
    <div class="info-card">
        <div class="info-label">Всего сигналов</div>
        <div class="info-value" style="color:#a855f7">{{ $signals->count() }}</div>
    </div>
    <div class="info-card">
        <div class="info-label">DONE</div>
        <div class="info-value" style="color:#0ecb81">{{ $done }}</div>
    </div>
    <div class="info-card">
        <div class="info-label">Win Rate</div>
        <div class="info-value" style="color:{{ $winRate >= 50 ? '#0ecb81' : '#f6465d' }}">
            {{ $total > 0 ? $winRate.'%' : '—' }}
        </div>
    </div>
    <div class="info-card">
        <div class="info-label">TP/SL режим</div>
        <div class="info-value-sm">
            {{ $strategy->tp_sl_mode === 'atr' ? 'ATR' : '%' }}
            · TP×{{ $strategy->tp_multiplier }} / SL×{{ $strategy->sl_multiplier }}
        </div>
    </div>
    <div class="info-card">
        <div class="info-label">Режим</div>
        <div class="info-value-sm">{{ match($strategy->mode){ 'telegram'=>'Telegram','autotrading'=>'Авто-трейд',default=>'Ручной' } }}</div>
    </div>
    @if($strategy->description)
    <div class="info-card" style="grid-column:span 2;">
        <div class="info-label">Описание</div>
        <div class="info-value-sm" style="font-weight:400;line-height:1.5;">{{ $strategy->description }}</div>
    </div>
    @endif
</div>

{{-- Conditions --}}
<div class="two-col">
    <div class="card">
        <h3>BUY условия ({{ $strategy->buyConditions->count() }})</h3>
        @forelse($strategy->buyConditions as $i => $cond)
            <span class="condition-tag buy">{{ $cond->humanLabel() }}</span>
            @if(!$loop->last)<span class="logic-sep">{{ $cond->next_logic }}</span>@endif
        @empty
            <span style="color:#64748b;font-size:12px;">Нет условий</span>
        @endforelse
    </div>
    <div class="card">
        <h3>SELL условия ({{ $strategy->sellConditions->count() }})</h3>
        @forelse($strategy->sellConditions as $i => $cond)
            <span class="condition-tag sell">{{ $cond->humanLabel() }}</span>
            @if(!$loop->last)<span class="logic-sep">{{ $cond->next_logic }}</span>@endif
        @empty
            <span style="color:#64748b;font-size:12px;">Нет условий</span>
        @endforelse
    </div>
</div>

{{-- Signals table --}}
<div class="card">
    <h3>Последние сигналы</h3>
    @if($signals->isEmpty())
        <p style="color:#64748b;font-size:13px;">Сигналов пока нет. Запустите стратегию: <code>php artisan strategies:run</code></p>
    @else
    <table class="signals-table">
        <thead>
            <tr>
                <th>Тип</th><th>Цена входа</th><th>TP</th><th>SL</th><th>Статус</th><th>Прибыль/убыток</th><th>Время</th>
            </tr>
        </thead>
        <tbody>
            @foreach($signals as $s)
            @php
                $pct = $s->status === 'DONE' ? $s->profitPct() : ($s->status === 'MISSED' ? -$s->lossPct() : null);
            @endphp
            <tr>
                <td><span class="badge badge-{{ strtolower($s->type) }}">{{ $s->type }}</span></td>
                <td style="font-family:monospace">{{ number_format($s->price,4) }}</td>
                <td style="color:#0ecb81;font-family:monospace">{{ number_format($s->take_profit,4) }}</td>
                <td style="color:#f6465d;font-family:monospace">{{ number_format($s->stop_loss,4) }}</td>
                <td>
                    <span class="badge badge-{{ strtolower($s->status === 'PROCESSING' ? 'proc' : strtolower($s->status)) }}">
                        {{ $s->status }}
                    </span>
                </td>
                <td>
                    @if($pct !== null)
                        <span style="color:{{ $pct >= 0 ? '#0ecb81' : '#f6465d' }};font-weight:700;">
                            {{ $pct >= 0 ? '+' : '' }}{{ round($pct,2) }}%
                        </span>
                    @else
                        <span style="color:#64748b">—</span>
                    @endif
                </td>
                <td style="color:#64748b;font-size:12px;">{{ $s->triggered_at?->format('d.m H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
{{-- Delete strategy modal --}}
<div class="modal-overlay" id="modalDeleteStrategy">
    <div class="modal">
        <div class="modal-icon">🗑️</div>
        <h3>Удалить стратегию?</h3>
        <p>Стратегия «{{ $strategy->name }}» и все её сигналы будут удалены безвозвратно.</p>
        <div class="modal-btns">
            <button class="modal-btn modal-btn-cancel" onclick="closeModal('modalDeleteStrategy')">Отмена</button>
            <button class="modal-btn modal-btn-confirm" onclick="document.getElementById('deleteStrategyForm').submit()">Удалить</button>
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
