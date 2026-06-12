<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\StrategyController;

Route::get('/', function () {
    return view('welcome');
});

// Страница логина
Route::get('/login', function () {
    if (auth()->check()) return redirect('/dashboard');
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

// Страница регистрации
Route::get('/register', function () {
    if (auth()->check()) return redirect('/dashboard');
    return view('auth.register');
})->name('register');

// Обработка формы регистрации
Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ], [
        'name.required'      => 'Введите имя.',
        'email.required'     => 'Введите email.',
        'email.email'        => 'Некорректный email.',
        'email.unique'       => 'Этот email уже зарегистрирован.',
        'password.required'  => 'Введите пароль.',
        'password.min'       => 'Пароль должен содержать минимум 8 символов.',
        'password.confirmed' => 'Пароли не совпадают.',
    ]);

    $user = \App\Models\User::create([
        'name'     => $validated['name'],
        'email'    => $validated['email'],
        'password' => bcrypt($validated['password']),
    ]);

    Auth::login($user);
    $request->session()->regenerate();

    return redirect('/dashboard');
})->name('register.post');

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
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show');

    Route::get('/trading', [TradingController::class, 'index'])->name('trading.index');
    Route::post('/trading/{profile}/order', [TradingController::class, 'placeOrder'])->name('trading.order');
    Route::post('/trading/{profile}/cancel-order', [TradingController::class, 'cancelOrder'])->name('trading.cancel-order');
    Route::get('/signals', [SignalController::class, 'index'])->name('signals.index');
    Route::get('/signals/export', [SignalController::class, 'export'])->name('signals.export');
    Route::get('/results', [ResultController::class, 'index'])->name('results.index');
    Route::get('strategies/symbols', [StrategyController::class, 'symbols'])->name('strategies.symbols');
    Route::resource('strategies', StrategyController::class);
    Route::post('strategies/{strategy}/toggle', [StrategyController::class, 'toggleActive'])->name('strategies.toggle');
    Route::get('strategies/{strategy}/backtest', [StrategyController::class, 'backtest'])->name('strategies.backtest');
    Route::get('indicators/{indicator}/outputs', [StrategyController::class, 'indicatorOutputs'])->name('indicators.outputs');
    Route::resource('profiles', ProfileController::class);
    Route::get('profiles/{profile}/positions/{symbol}', [ProfileController::class, 'showPosition'])->name('profiles.positions.show');
    Route::get('profiles/{profile}/stream-url', [ProfileController::class, 'streamUrl'])->name('profiles.stream-url');
    Route::get('profiles/{profile}/account-data', [ProfileController::class, 'accountData'])->name('profiles.account-data');
    Route::post('profiles/{profile}/close-position', [ProfileController::class, 'closePosition'])->name('profiles.close-position');
});
