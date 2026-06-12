@extends('layouts.app')
@section('page-title', $profile ? 'Редактировать профиль' : 'Добавить профиль')
@push('styles')
<style>
    .form-wrap { max-width: 560px; margin: 0 auto; }
    .card {
        padding: 28px; border-radius: 16px;
        background: rgba(15, 23, 42, 0.85);
        border: 1px solid rgba(168, 85, 247, 0.2);
    }
    .field { margin-bottom: 20px; }
    .label { display: block; font-size: 12px; color: #cbd5e1; margin-bottom: 5px; font-weight: 500; }
    .label-hint { font-size: 11px; color: #64748b; margin-top: 4px; }
    .input {
        width: 100%; border-radius: 10px; border: 1px solid rgba(148, 163, 184, 0.4);
        background: rgba(10, 10, 25, 0.8); padding: 10px 12px; font-size: 13px;
        color: #e5e7eb; outline: none; transition: border-color .2s;
    }
    .input:focus { border-color: #a855f7; box-shadow: 0 0 0 1px rgba(168, 85, 247, 0.4); }
    select.input { cursor: pointer; }
    .checkbox-wrap { display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .checkbox-wrap input[type=checkbox] { width: 16px; height: 16px; accent-color: #a855f7; cursor: pointer; }
    .btn {
        border-radius: 10px; border: none; padding: 10px 22px; font-size: 14px; font-weight: 600;
        color: #f9fafb; background: linear-gradient(135deg, #a855f7, #ec4899);
        cursor: pointer; transition: all 0.2s ease;
    }
    .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(168, 85, 247, 0.5); }
    .btn-secondary {
        background: rgba(51, 65, 85, 0.7); color: #e5e7eb; text-decoration: none;
        display: inline-block; margin-left: 10px; border-radius: 10px;
        padding: 10px 22px; font-size: 14px; font-weight: 600; transition: all .2s;
    }
    .btn-secondary:hover { background: rgba(71, 85, 105, 0.9); }
    .errors {
        margin-bottom: 20px; padding: 12px 16px; border-radius: 10px;
        background: rgba(185, 28, 28, 0.2); border: 1px solid rgba(248, 113, 113, 0.4);
        color: #fecaca; font-size: 13px;
    }
    .errors ul { margin-left: 16px; }
    .form-actions { display: flex; align-items: center; margin-top: 6px; }
</style>
@endpush

@section('content')
<div class="form-wrap">

    <div style="margin-bottom:20px">
        <a href="{{ route('profiles.index') }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">
            ← Все профили
        </a>
    </div>

    <p style="font-size:13px;color:#94a3b8;margin-bottom:20px">
        API Key и Secret для Binance Futures.
        TEST → testnet (demo-fapi.binance.com), PROD → боевая сеть (fapi.binance.com).
    </p>

    <div class="card">
        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $profile ? route('profiles.update', $profile) : route('profiles.store') }}">
            @csrf
            @if ($profile)
                @method('PUT')
            @endif

            <div class="field">
                <label for="profile_name" class="label">Имя профиля</label>
                <input type="text" id="profile_name" name="profile_name" class="input"
                    value="{{ old('profile_name', optional($profile)->profile_name) }}"
                    required maxlength="255" placeholder="Например: Мой Testnet">
                <div class="label-hint">Произвольное название для удобства.</div>
            </div>

            <div class="field">
                <label for="profile_token" class="label">API Key (Binance Futures)</label>
                <input type="text" id="profile_token" name="profile_token" class="input"
                    value="{{ old('profile_token', optional($profile)->profile_token) }}"
                    required maxlength="500" placeholder="API Key из Binance" autocomplete="off">
                <div class="label-hint">Подставляется в запросы как X-MBX-APIKEY.</div>
            </div>

            <div class="field">
                <label for="profile_secret" class="label">API Secret (Binance Futures)</label>
                <input type="password" id="profile_secret" name="profile_secret" class="input"
                    value="" maxlength="500" autocomplete="off"
                    placeholder="{{ $profile ? 'Оставьте пустым, чтобы не менять' : 'Обязательно для открытия позиций' }}">
                @if($profile && $profile->hasApiSecret())
                    <div class="label-hint">✓ Secret сохранён. Введите новый только для смены.</div>
                @else
                    <div class="label-hint">Используется для подписи запросов (HMAC SHA256).</div>
                @endif
            </div>

            <div class="field">
                <label for="category" class="label">Категория</label>
                <select id="category" name="category" class="input" required>
                    <option value="TEST" {{ old('category', optional($profile)->category ?? 'TEST') === 'TEST' ? 'selected' : '' }}>
                        TEST — testnet (demo-fapi.binance.com)
                    </option>
                    <option value="PROD" {{ old('category', optional($profile)->category) === 'PROD' ? 'selected' : '' }}>
                        PROD — боевая сеть (fapi.binance.com)
                    </option>
                </select>
            </div>

            <div class="field">
                <div class="checkbox-wrap">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                        {{ old('is_active', optional($profile)->is_active ?? true) ? 'checked' : '' }}>
                    <label for="is_active" class="label" style="margin-bottom:0;cursor:pointer">Профиль активен</label>
                </div>
                <div class="label-hint">Только активные профили участвуют в открытии позиций.</div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">
                    {{ $profile ? 'Сохранить изменения' : 'Добавить профиль' }}
                </button>
                <a href="{{ route('profiles.index') }}" class="btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>
@endsection
