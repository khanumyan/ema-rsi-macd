<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\ResultController;

Route::get('/', function () {
    return view('welcome');
});

// Страница логина
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Обработка формы логина
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'email' => 'Неверный email или пароль.',
    ])->onlyInput('email');
})->name('login.post');

// Выход из системы
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

// Простой дашборд только для авторизованных пользователей
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// Страница сигналов и стратегий
Route::middleware('auth')->group(function () {
    Route::get('/signals', [SignalController::class, 'index'])->name('signals.index');
    Route::get('/signals/export', [SignalController::class, 'export'])->name('signals.export');
    Route::get('/results', [ResultController::class, 'index'])->name('results.index');
});
