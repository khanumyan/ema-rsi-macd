@extends('layouts.app')

@section('title', 'Новая стратегия')
@section('page-title', 'Новая стратегия')

@push('styles')
<style>
.wizard { max-width:780px; margin:0 auto; }

/* Steps */
.wizard-steps { display:flex; gap:0; margin-bottom:32px; }
.wstep {
    flex:1; display:flex; flex-direction:column; align-items:center; gap:6px;
    font-size:12px; color:#64748b; cursor:pointer; position:relative;
}
.wstep::after {
    content:''; position:absolute; top:16px; left:50%; width:100%; height:2px;
    background:rgba(255,255,255,.08); z-index:0;
}
.wstep:last-child::after { display:none; }
.wstep-num {
    width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,.07);
    border:2px solid rgba(255,255,255,.15); display:flex; align-items:center;
    justify-content:center; font-weight:700; font-size:13px; z-index:1; position:relative;
}
.wstep.active .wstep-num { background:linear-gradient(135deg,#a855f7,#ec4899); border-color:transparent; color:#fff; }
.wstep.done   .wstep-num { background:rgba(14,203,129,.2); border-color:#0ecb81; color:#0ecb81; }
.wstep.active .wstep-label, .wstep.done .wstep-label { color:#f1f5f9; }

/* Form cards */
.form-card {
    background:rgba(20,14,40,.7); border:1px solid rgba(168,85,247,.15);
    border-radius:14px; padding:28px; margin-bottom:20px;
}
.form-card-title { font-size:16px; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
.form-card-title .step-badge {
    width:24px; height:24px; border-radius:50%;
    background:linear-gradient(135deg,#a855f7,#ec4899);
    display:inline-flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:800; color:#fff;
}

.form-row { display:grid; gap:16px; margin-bottom:16px; }
.form-row-2 { grid-template-columns:1fr 1fr; }
.form-row-3 { grid-template-columns:1fr 1fr 1fr; }
.form-row-4 { grid-template-columns:1fr 1fr 1fr 1fr; }

.form-group label { display:block; font-size:12px; font-weight:600; color:#94a3b8; margin-bottom:6px; text-transform:uppercase; letter-spacing:.4px; }
.form-group input, .form-group select, .form-group textarea {
    width:100%; padding:10px 12px; background:rgba(255,255,255,.06);
    border:1px solid rgba(168,85,247,.2); border-radius:8px; color:#f1f5f9;
    font-size:13px; outline:none; transition:border-color .2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:#a855f7; }
.form-group select option { background:#1a0a2e; }
.form-group input[type=checkbox] { width:auto; }

.toggle-row { display:flex; align-items:center; gap:12px; }
.toggle-row label { margin:0; font-size:13px; font-weight:500; color:#f1f5f9; text-transform:none; letter-spacing:0; }

/* Condition builder */
.conditions-split { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:700px){ .conditions-split { grid-template-columns:1fr; } }
.conditions-col { display:flex; flex-direction:column; gap:10px; }
.conditions-col-header {
    display:flex; align-items:center; gap:8px; padding:10px 14px;
    border-radius:10px; font-size:13px; font-weight:700; margin-bottom:2px;
}
.conditions-col-header.buy  { background:rgba(14,203,129,.1); border:1px solid rgba(14,203,129,.2); color:#0ecb81; }
.conditions-col-header.sell { background:rgba(246,70,93,.1);  border:1px solid rgba(246,70,93,.2);  color:#f6465d; }

.conditions-list { display:flex; flex-direction:column; gap:10px; }

.condition-row {
    background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);
    border-radius:10px; padding:12px 14px;
}
.condition-fields { display:flex; flex-direction:column; gap:8px; }
.form-row-cond { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.condition-logic { display:flex; gap:6px; margin-top:8px; padding-top:8px; border-top:1px solid rgba(255,255,255,.05); align-items:center; }
.logic-btn { padding:3px 12px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; border:1px solid; background:transparent; transition:all .15s; }
.logic-btn.and { border-color:rgba(168,85,247,.3); color:#a855f7; }
.logic-btn.and.selected { background:rgba(168,85,247,.2); }
.logic-btn.or  { border-color:rgba(59,130,246,.3); color:#60a5fa; }
.logic-btn.or.selected  { background:rgba(59,130,246,.2); }

.add-condition-btn {
    display:flex; align-items:center; gap:6px; padding:8px 16px;
    background:rgba(168,85,247,.1); border:1px dashed rgba(168,85,247,.3);
    border-radius:8px; color:#a855f7; font-size:13px; font-weight:600;
    cursor:pointer; width:100%; justify-content:center; transition:all .2s;
}
.add-condition-btn:hover { background:rgba(168,85,247,.2); }

.remove-btn { padding:6px 10px; background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.2); border-radius:6px; color:#fca5a5; cursor:pointer; font-size:14px; flex-shrink:0; transition:all .2s; }
.remove-btn:hover { background:rgba(239,68,68,.2); }

/* Form actions */
.form-actions { display:flex; justify-content:flex-end; gap:12px; margin-top:8px; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; text-decoration:none; transition:all .2s; }
.btn-primary { background:linear-gradient(135deg,#a855f7,#ec4899); color:#fff; }
.btn-primary:hover { opacity:.9; }
.btn-outline { background:transparent; border:1px solid rgba(168,85,247,.3); color:#a855f7; }

.err { color:#f87171; font-size:11px; margin-top:4px; }
.alert-error { padding:12px 16px; background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); border-radius:10px; color:#fca5a5; font-size:13px; margin-bottom:20px; }

/* Symbol picker */
.symbol-picker { position:relative; }
.symbol-picker-input-wrap { position:relative; }
.symbol-picker-input-wrap input {
    padding-right:40px !important;
    cursor:pointer;
}
.symbol-picker-arrow {
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    color:#64748b; pointer-events:none; font-size:12px;
}
.symbol-dropdown {
    position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:999;
    background:#13102a; border:1px solid rgba(168,85,247,.3); border-radius:10px;
    box-shadow:0 8px 32px rgba(0,0,0,.5);
    display:none; flex-direction:column; overflow:hidden;
    max-height:320px;
}
.symbol-dropdown.open { display:flex; }
.symbol-search {
    padding:10px 12px; border:none; border-bottom:1px solid rgba(168,85,247,.15);
    background:rgba(255,255,255,.05); color:#f1f5f9; font-size:13px; outline:none;
    flex-shrink:0;
}
.symbol-search::placeholder { color:#64748b; }
.symbol-list {
    overflow-y:auto; flex:1;
}
.symbol-list::-webkit-scrollbar { width:4px; }
.symbol-list::-webkit-scrollbar-thumb { background:rgba(168,85,247,.3); border-radius:4px; }
.symbol-option {
    padding:9px 14px; font-size:13px; cursor:pointer; color:#f1f5f9;
    transition:background .15s; display:flex; align-items:center; gap:8px;
}
.symbol-option:hover, .symbol-option.focused { background:rgba(168,85,247,.15); }
.symbol-option .sym-badge {
    font-size:10px; padding:1px 6px; border-radius:4px;
    background:rgba(168,85,247,.15); color:#a855f7; font-weight:700;
}
.symbol-loading { padding:20px; text-align:center; color:#64748b; font-size:13px; }
.all-pairs-option {
    border-bottom:1px solid rgba(168,85,247,.15);
    background:rgba(168,85,247,.05);
    font-weight:600;
}
.all-pairs-option:hover, .all-pairs-option.focused { background:rgba(168,85,247,.2) !important; }
</style>
@endpush

@section('content')
@if($errors->any())
<div class="alert-error">
    <strong>Ошибки:</strong>
    <ul style="margin:6px 0 0 16px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="wizard">
    <form method="POST" action="{{ route('strategies.store') }}" id="strategyForm">
        @csrf

        {{-- ── STEP 1: Basic info ── --}}
        <div class="form-card">
            <div class="form-card-title"><span class="step-badge">1</span> Основная информация</div>

            <div class="form-row form-row-2">
                <div class="form-group">
                    <label>Название стратегии *</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Моя RSI стратегия" required>
                </div>
                <div class="form-group">
                    <label>Торговая пара *</label>
                    <div class="symbol-picker" id="symbolPicker">
                        <div class="symbol-picker-input-wrap">
                            <input type="text" id="symbolDisplay"
                                   value="{{ old('symbol','BTCUSDT') === 'ALL' ? 'Все пары (ALL)' : old('symbol','BTCUSDT') }}"
                                   placeholder="Выберите или введите пару..."
                                   autocomplete="off" onclick="openSymbolDropdown()" oninput="filterSymbols(this.value)">
                            <span class="symbol-picker-arrow">▼</span>
                        </div>
                        <input type="hidden" name="symbol" id="symbolHidden" value="{{ old('symbol','BTCUSDT') }}" required>
                        <div class="symbol-dropdown" id="symbolDropdown">
                            <input class="symbol-search" type="text" id="symbolSearch"
                                   placeholder="Поиск по паре..." oninput="filterSymbols(this.value)"
                                   onkeydown="handleSymbolKey(event)">
                            <div class="symbol-list" id="symbolList">
                                <div class="symbol-loading">Загрузка пар...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" rows="2" placeholder="Кратко опишите логику стратегии...">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="form-row form-row-4">
                <div class="form-group">
                    <label>Таймфрейм *</label>
                    <select name="interval" required>
                        @foreach(['1m','3m','5m','15m','30m','1h','2h','4h','6h','12h','1d'] as $tf)
                            <option value="{{ $tf }}" {{ old('interval','4h')===$tf?'selected':'' }}>{{ $tf }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Свечей (расчёт)</label>
                    <input type="number" name="candles_limit" value="{{ old('candles_limit',200) }}" min="30" max="500">
                </div>
                <div class="form-group">
                    <label>TP/SL режим</label>
                    <select name="tp_sl_mode" id="tpSlMode" onchange="toggleTpSlLabels()">
                        <option value="atr" {{ old('tp_sl_mode','atr')==='atr'?'selected':'' }}>ATR (множитель)</option>
                        <option value="percent" {{ old('tp_sl_mode')==='percent'?'selected':'' }}>Процент (%)</option>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <div class="form-group">
                        <label id="tpLabel">TP ×ATR</label>
                        <input type="number" step="0.1" name="tp_multiplier" value="{{ old('tp_multiplier',3.5) }}" min="0.1">
                    </div>
                    <div class="form-group">
                        <label id="slLabel">SL ×ATR</label>
                        <input type="number" step="0.1" name="sl_multiplier" value="{{ old('sl_multiplier',2.3) }}" min="0.1">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── STEP 2: Conditions ── --}}
        <div class="form-card">
            <div class="form-card-title"><span class="step-badge">2</span> Условия входа</div>
            <p style="font-size:12px;color:#64748b;margin-bottom:16px;">
                Добавьте условия для BUY и/или SELL — оба работают одновременно в рамках одной стратегии.
            </p>

            <div class="conditions-split">
                @foreach(['BUY','SELL'] as $stype)
                <div class="conditions-col">
                    <div class="conditions-col-header {{ strtolower($stype) }}">
                        <span>{{ $stype === 'BUY' ? '▲' : '▼' }}</span>
                        {{ $stype }} — условия для лонга
                    </div>
                    <div class="conditions-list" id="conditions-{{ $stype }}">
                        @if(old('conditions'))
                            @foreach(old('conditions') as $ci => $cond)
                                @if(($cond['signal_type'] ?? '') === $stype)
                                    @php $oldIndicator = collect($indicators->flatten())->firstWhere('id', $cond['indicator_id']); @endphp
                                    <div class="condition-row">
                                        <input type="hidden" name="conditions[{{ $ci }}][signal_type]" value="{{ $stype }}">
                                        <div class="condition-fields">
                                            <div class="form-row-cond">
                                                <div class="form-group">
                                                    <label>Индикатор</label>
                                                    <select name="conditions[{{ $ci }}][indicator_id]" onchange="loadOutputs(this,'{{ $ci }}')">
                                                        @foreach($indicators->flatten() as $ind)
                                                            <option value="{{ $ind->id }}" {{ ($cond['indicator_id'] ?? '') == $ind->id ? 'selected' : '' }}>{{ $ind->short_name }} — {{ Str::before($ind->name,'(') }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Выход</label>
                                                    <select name="conditions[{{ $ci }}][indicator_output]" id="out-{{ $ci }}">
                                                        @if($oldIndicator)
                                                            @foreach($oldIndicator->outputs as $out)
                                                                <option value="{{ $out }}" {{ ($cond['indicator_output'] ?? '') === $out ? 'selected' : '' }}>{{ $out }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-row-cond">
                                                <div class="form-group">
                                                    <label>Оператор</label>
                                                    <select name="conditions[{{ $ci }}][operator]" onchange="toggleBetween(this,'{{ $ci }}')">
                                                        @foreach(['>','>=','<','<=','=','between','crosses_above','crosses_below'] as $op)
                                                            <option value="{{ $op }}" {{ ($cond['operator'] ?? '') === $op ? 'selected' : '' }}>{{ $op }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Значение A</label>
                                                    <input type="number" step="any" name="conditions[{{ $ci }}][value_a]" value="{{ $cond['value_a'] ?? '' }}" required>
                                                </div>
                                                <div class="form-group" id="valB-{{ $ci }}" style="{{ ($cond['operator'] ?? '') === 'between' ? '' : 'display:none' }}">
                                                    <label>Значение B</label>
                                                    <input type="number" step="any" name="conditions[{{ $ci }}][value_b]" value="{{ $cond['value_b'] ?? '' }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="condition-logic">
                                            <span style="font-size:11px;color:#64748b;margin-right:6px;">Следующее:</span>
                                            <button type="button" class="logic-btn and {{ ($cond['next_logic'] ?? 'AND') === 'AND' ? 'selected' : '' }}" onclick="selectLogic(this,'AND','{{ $ci }}')">AND</button>
                                            <button type="button" class="logic-btn or {{ ($cond['next_logic'] ?? '') === 'OR' ? 'selected' : '' }}" onclick="selectLogic(this,'OR','{{ $ci }}')">OR</button>
                                            <input type="hidden" name="conditions[{{ $ci }}][next_logic]" id="logic-{{ $ci }}" value="{{ $cond['next_logic'] ?? 'AND' }}">
                                            <button type="button" class="remove-btn" onclick="this.closest('.condition-row').remove()" style="margin-left:auto;">✕</button>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                    <button type="button" class="add-condition-btn" onclick="addCondition('{{ $stype }}')">
                        + Добавить {{ $stype }} условие
                    </button>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── STEP 3: Mode ── --}}
        <div class="form-card">
            <div class="form-card-title"><span class="step-badge">3</span> Режим работы</div>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Режим *</label>
                    <select name="mode" id="modeSelect" onchange="toggleModeFields()" required>
                        <option value="manual"      {{ old('mode','manual')==='manual'?'selected':'' }}>Ручной (только сигналы)</option>
                        <option value="telegram"    {{ old('mode')==='telegram'?'selected':'' }}>Telegram уведомления</option>
                        <option value="autotrading" {{ old('mode')==='autotrading'?'selected':'' }}>Авто-трейдинг</option>
                    </select>
                </div>
                <div class="form-group" id="telegramField" style="display:none;">
                    <label>Telegram Chat ID</label>
                    <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id') }}" placeholder="-100123456789">
                </div>
                <div class="form-group" id="profileField" style="display:none;">
                    <label>Binance профиль</label>
                    <select name="profile_id">
                        <option value="">— выберите —</option>
                        @foreach(auth()->user()->profiles ?? [] as $profile)
                            <option value="{{ $profile->id }}" {{ old('profile_id')==$profile->id?'selected':'' }}>
                                {{ $profile->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="toggle-row">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active',1)?'checked':'' }}>
                <label for="isActive">Запустить стратегию сразу после создания</label>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('strategies.index') }}" class="btn btn-outline">Отмена</a>
            <button type="submit" class="btn btn-primary">Создать стратегию →</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const indicatorsData = @json($indicators->flatten()->values());

function toggleTpSlLabels() {
    const mode = document.getElementById('tpSlMode').value;
    document.getElementById('tpLabel').textContent = mode === 'atr' ? 'TP ×ATR' : 'TP %';
    document.getElementById('slLabel').textContent = mode === 'atr' ? 'SL ×ATR' : 'SL %';
}

function toggleModeFields() {
    const mode = document.getElementById('modeSelect').value;
    document.getElementById('telegramField').style.display = mode === 'telegram' ? '' : 'none';
    document.getElementById('profileField').style.display  = mode === 'autotrading' ? '' : 'none';
}

function addCondition(stype) {
    const idx = Date.now();
    const list = document.getElementById('conditions-' + stype);
    const div  = document.createElement('div');
    div.className = 'condition-row';
    div.innerHTML = buildConditionHtml(stype, idx);
    list.appendChild(div);
}

function buildConditionHtml(stype, idx) {
    let indOpts = indicatorsData.map(ind =>
        `<option value="${ind.id}">${ind.short_name} — ${ind.name.split('(')[0].trim()}</option>`
    ).join('');

    return `
    <input type="hidden" name="conditions[${idx}][signal_type]" value="${stype}">
    <div class="condition-fields">
        <div class="form-row-cond">
            <div class="form-group">
                <label>Индикатор</label>
                <select name="conditions[${idx}][indicator_id]" onchange="loadOutputs(this,${idx})">
                    ${indOpts}
                </select>
            </div>
            <div class="form-group">
                <label>Выход</label>
                <select name="conditions[${idx}][indicator_output]" id="out-${idx}">
                    <option value="">—</option>
                </select>
            </div>
        </div>
        <div class="form-row-cond">
            <div class="form-group">
                <label>Оператор</label>
                <select name="conditions[${idx}][operator]" onchange="toggleBetween(this,${idx})">
                    <option value=">">&gt;</option>
                    <option value=">=">&ge;</option>
                    <option value="<">&lt;</option>
                    <option value="<=">&le;</option>
                    <option value="=">= (равно)</option>
                    <option value="between">between</option>
                    <option value="crosses_above">crosses ↑</option>
                    <option value="crosses_below">crosses ↓</option>
                </select>
            </div>
            <div class="form-group">
                <label>Значение A</label>
                <input type="number" step="any" name="conditions[${idx}][value_a]" placeholder="50" required>
            </div>
            <div class="form-group" id="valB-${idx}" style="display:none;">
                <label>Значение B</label>
                <input type="number" step="any" name="conditions[${idx}][value_b]" placeholder="70">
            </div>
        </div>
    </div>
    <div class="condition-logic">
        <span style="font-size:11px;color:#64748b;margin-right:6px;">Следующее:</span>
        <button type="button" class="logic-btn and selected" onclick="selectLogic(this,'AND',${idx})">AND</button>
        <button type="button" class="logic-btn or"           onclick="selectLogic(this,'OR',${idx})">OR</button>
        <input type="hidden" name="conditions[${idx}][next_logic]" id="logic-${idx}" value="AND">
        <button type="button" class="remove-btn" onclick="this.closest('.condition-row').remove()" style="margin-left:auto;">✕</button>
    </div>`;
}

function loadOutputs(sel, idx) {
    const indId = parseInt(sel.value);
    const ind   = indicatorsData.find(i => i.id === indId);
    const out   = document.getElementById('out-' + idx);
    if (!ind || !out) return;
    out.innerHTML = ind.outputs.map(o => `<option value="${o}">${o}</option>`).join('');
}

function toggleBetween(sel, idx) {
    document.getElementById('valB-' + idx).style.display = sel.value === 'between' ? '' : 'none';
}

function selectLogic(btn, val, idx) {
    btn.closest('.condition-logic').querySelectorAll('.logic-btn').forEach(b => {
        b.classList.remove('selected');
    });
    btn.classList.add('selected');
    document.getElementById('logic-' + idx).value = val;
}

// init
toggleModeFields();
toggleTpSlLabels();

// Auto-load outputs for any existing rows
document.querySelectorAll('[id^="out-"]').forEach(sel => {
    const idx    = sel.id.replace('out-','');
    const indSel = document.querySelector(`[name="conditions[${idx}][indicator_id]"]`);
    if (indSel) loadOutputs(indSel, idx);
});

// ── Symbol Picker ────────────────────────────────────────────────────────────
let allSymbols = [];
let focusedIdx = -1;

async function loadSymbols() {
    try {
        const res  = await fetch('{{ route('strategies.symbols') }}');
        allSymbols = await res.json();
        renderSymbols(allSymbols);
    } catch(e) {
        document.getElementById('symbolList').innerHTML =
            '<div class="symbol-loading">Ошибка загрузки. Введите пару вручную.</div>';
    }
}

function renderSymbols(list) {
    const el = document.getElementById('symbolList');
    // "All pairs" option always at top
    const allRow = `<div class="symbol-option all-pairs-option" data-sym="ALL" data-idx="0"
          onclick="selectSymbol('ALL')" onmouseover="setFocus(0)">
        <span class="sym-badge" style="background:rgba(168,85,247,.25);color:#c084fc;">ВСЕ</span>
        Все пары одновременно
    </div>`;

    if (!list.length) {
        el.innerHTML = allRow + '<div class="symbol-loading">Ничего не найдено</div>';
        return;
    }
    el.innerHTML = allRow + list.slice(0, 200).map((s, i) =>
        `<div class="symbol-option" data-sym="${s}" data-idx="${i + 1}"
              onclick="selectSymbol('${s}')" onmouseover="setFocus(${i + 1})">
            <span class="sym-badge">USDT</span> ${s}
         </div>`
    ).join('');
    focusedIdx = -1;
}

function filterSymbols(q) {
    const query = q.toUpperCase().trim();
    // update hidden value as user types
    document.getElementById('symbolHidden').value = query || 'ALL';

    const dd = document.getElementById('symbolDropdown');
    if (!dd.classList.contains('open')) openSymbolDropdown();

    // sync search box with display input
    document.getElementById('symbolSearch').value = query;

    const filtered = query ? allSymbols.filter(s => s.includes(query)) : allSymbols;
    renderSymbols(filtered);
}

function openSymbolDropdown() {
    const dd = document.getElementById('symbolDropdown');
    dd.classList.add('open');
    if (!allSymbols.length) loadSymbols();
    // don't steal focus from display input if user is typing there
    const active = document.activeElement;
    if (active !== document.getElementById('symbolDisplay')) {
        setTimeout(() => document.getElementById('symbolSearch').focus(), 50);
    }
}

function closeSymbolDropdown() {
    document.getElementById('symbolDropdown').classList.remove('open');
}

function selectSymbol(sym) {
    const label = sym === 'ALL' ? 'Все пары (ALL)' : sym;
    document.getElementById('symbolDisplay').value = label;
    document.getElementById('symbolHidden').value  = sym;
    document.getElementById('symbolSearch').value  = '';
    renderSymbols(allSymbols);
    closeSymbolDropdown();
    document.getElementById('symbolDisplay').blur();
}

function setFocus(idx) {
    document.querySelectorAll('#symbolList .symbol-option').forEach(el => el.classList.remove('focused'));
    const el = document.querySelector(`#symbolList .symbol-option[data-idx="${idx}"]`);
    if (el) { el.classList.add('focused'); focusedIdx = idx; }
}

function handleSymbolKey(e) {
    const opts = document.querySelectorAll('#symbolList .symbol-option');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        focusedIdx = Math.min(focusedIdx + 1, opts.length - 1);
        setFocus(focusedIdx);
        opts[focusedIdx]?.scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        focusedIdx = Math.max(focusedIdx - 1, 0);
        setFocus(focusedIdx);
        opts[focusedIdx]?.scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const focused = document.querySelector('#symbolList .symbol-option.focused');
        if (focused) selectSymbol(focused.dataset.sym);
    } else if (e.key === 'Escape') {
        closeSymbolDropdown();
    }
}

// close on outside click — use mousedown so it fires before blur
document.addEventListener('mousedown', e => {
    if (!document.getElementById('symbolPicker').contains(e.target)) {
        closeSymbolDropdown();
    }
});
</script>
@endpush
