@extends('layouts.app')
@section('page-title', 'Профили')
@push('styles')
<style>
    .toolbar { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .btn {
        border-radius: 8px; border: none; padding: 8px 16px; font-size: 13px; font-weight: 500;
        color: #f9fafb; background: linear-gradient(135deg, #a855f7, #ec4899);
        cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;
    }
    .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(168, 85, 247, 0.5); }
    .btn-sm { padding: 5px 10px; font-size: 12px; }
    .btn-danger { background: rgba(239, 68, 68, 0.6); color: #fecaca; }
    .btn-danger:hover { background: rgba(239, 68, 68, 0.8); box-shadow: none; }
    .table-wrapper {
        border-radius: 16px; border: 1px solid rgba(168, 85, 247, 0.2);
        background: rgba(15, 23, 42, 0.8); overflow: auto;
    }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    thead { background: rgba(10, 10, 25, 0.9); }
    th, td { padding: 11px 14px; border-bottom: 1px solid rgba(51, 65, 85, 0.6); text-align: left; }
    th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; }
    tbody tr:hover { background: rgba(168, 85, 247, 0.06); }
    .badge { padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; }
    .badge-prod { background: rgba(34, 197, 94, 0.15); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.35); }
    .badge-test { background: rgba(234, 179, 8, 0.15); color: #fde68a; border: 1px solid rgba(234, 179, 8, 0.35); }
    .badge-active { background: rgba(14, 203, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(14,203,129,.3); }
    .badge-inactive { background: rgba(100, 116, 139, 0.2); color: #94a3b8; border: 1px solid rgba(100,116,139,.3); }
    .actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .message { margin-bottom: 16px; padding: 10px 14px; border-radius: 10px; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.4); color: #bbf7d0; font-size: 13px; }
    .token-cell { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: monospace; font-size: 12px; color: #64748b; }
    .profile-name-link { color: #a855f7; text-decoration: none; font-weight: 600; }
    .profile-name-link:hover { color: #ec4899; }

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

    @if (session('message'))
        <div class="message">{{ session('message') }}</div>
    @endif

    <div class="toolbar">
        <p style="font-size:13px;color:#94a3b8;">
            API Key и Secret для Binance Futures. TEST → testnet, PROD → боевая сеть.
        </p>
        <a href="{{ route('profiles.create') }}" class="btn">+ Добавить профиль</a>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>API Key</th>
                    <th>API Secret</th>
                    <th>Активен</th>
                    <th>Категория</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($profiles as $p)
                    <tr>
                        <td>
                            <a href="{{ route('profiles.show', $p) }}" class="profile-name-link">
                                {{ $p->profile_name }}
                            </a>
                        </td>
                        <td class="token-cell" title="{{ $p->profile_token }}">{{ $p->profile_token }}</td>
                        <td>
                            <span class="badge {{ $p->hasApiSecret() ? 'badge-active' : 'badge-inactive' }}">
                                {{ $p->hasApiSecret() ? 'Задан' : '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $p->is_active ? 'badge-active' : 'badge-inactive' }}">
                                {{ $p->is_active ? 'Да' : 'Нет' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $p->category === 'PROD' ? 'badge-prod' : 'badge-test' }}">
                                {{ $p->category }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('profiles.show', $p) }}" class="btn btn-sm">Открыть</a>
                                <a href="{{ route('profiles.edit', $p) }}" class="btn btn-sm" style="background:rgba(168,85,247,.4)">Изменить</a>
                                <button type="button" class="btn btn-sm btn-danger"
                                    onclick="confirmDeleteProfile('{{ route('profiles.destroy', $p) }}', '{{ addslashes($p->profile_name) }}')">
                                    Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">
                            Нет профилей. <a href="{{ route('profiles.create') }}" style="color:#a855f7">Добавить →</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

{{-- Delete profile modal --}}
<div class="modal-overlay" id="modalDeleteProfile">
    <div class="modal">
        <div class="modal-icon">🗑️</div>
        <h3>Удалить профиль?</h3>
        <p id="modalDeleteProfileText">Профиль будет удалён безвозвратно.</p>
        <div class="modal-btns">
            <button class="modal-btn modal-btn-cancel" onclick="closeModal('modalDeleteProfile')">Отмена</button>
            <button class="modal-btn modal-btn-confirm" id="modalDeleteProfileConfirm">Удалить</button>
        </div>
    </div>
</div>

<form id="deleteProfileForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function confirmDeleteProfile(url, name) {
    document.getElementById('modalDeleteProfileText').textContent = 'Профиль «' + name + '» будет удалён безвозвратно.';
    document.getElementById('deleteProfileForm').action = url;
    document.getElementById('modalDeleteProfileConfirm').onclick = function() {
        document.getElementById('deleteProfileForm').submit();
    };
    openModal('modalDeleteProfile');
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active').forEach(function(m) { m.classList.remove('active'); }); });
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('mousedown', function(e) { if (e.target === overlay) closeModal(overlay.id); });
});
</script>
@endsection
