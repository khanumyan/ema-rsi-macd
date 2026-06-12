<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\UserStrategy;
use App\Models\StrategyCondition;
use App\Services\StrategyBacktestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StrategyController extends Controller
{
    public function index()
    {
        $strategies = UserStrategy::where('user_id', Auth::id())
            ->withCount('conditions', 'signals')
            ->with(['signals' => fn($q) => $q->latest()->limit(1)])
            ->latest()
            ->paginate(12);

        return view('strategies.index', compact('strategies'));
    }

    public function create()
    {
        $indicators = Indicator::orderBy('category')->orderBy('name')->get()->groupBy('category');
        return view('strategies.create', compact('indicators'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'description'    => 'nullable|string|max:500',
            'symbol'         => 'required|string|max:20',
            'interval'       => 'required|in:1m,3m,5m,15m,30m,1h,2h,4h,6h,12h,1d',
            'candles_limit'  => 'required|integer|min:30|max:500',
            'tp_sl_mode'     => 'required|in:atr,percent',
            'tp_multiplier'  => 'required|numeric|min:0.1',
            'sl_multiplier'  => 'required|numeric|min:0.1',
            'mode'           => 'required|in:telegram,autotrading,manual',
            'telegram_chat_id' => 'nullable|string|max:50',
            'profile_id'     => 'nullable|exists:profiles,id',
            'is_active'      => 'boolean',
            'conditions'     => 'required|array|min:1',
            'conditions.*.signal_type'      => 'required|in:BUY,SELL',
            'conditions.*.indicator_id'     => 'required|exists:indicators,id',
            'conditions.*.indicator_output' => 'required|string',
            'conditions.*.operator'         => 'required|string',
            'conditions.*.value_a'          => 'required|numeric',
            'conditions.*.value_b'          => 'nullable|numeric',
            'conditions.*.next_logic'       => 'required|in:AND,OR',
        ]);

        $strategy = UserStrategy::create([
            'user_id'          => Auth::id(),
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'symbol'           => strtoupper($data['symbol']),
            'interval'         => $data['interval'],
            'candles_limit'    => $data['candles_limit'],
            'tp_sl_mode'       => $data['tp_sl_mode'],
            'tp_multiplier'    => $data['tp_multiplier'],
            'sl_multiplier'    => $data['sl_multiplier'],
            'mode'             => $data['mode'],
            'telegram_chat_id' => $data['telegram_chat_id'] ?? null,
            'profile_id'       => $data['profile_id'] ?? null,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        foreach (array_values($data['conditions']) as $order => $cond) {
            StrategyCondition::create([
                'strategy_id'      => $strategy->id,
                'signal_type'      => $cond['signal_type'],
                'indicator_id'     => $cond['indicator_id'],
                'indicator_output' => $cond['indicator_output'],
                'param_overrides'  => null,
                'operator'         => $cond['operator'],
                'value_a'          => $cond['value_a'],
                'value_b'          => $cond['value_b'] ?? null,
                'next_logic'       => $cond['next_logic'],
                'sort_order'       => $order,
            ]);
        }

        return redirect()->route('strategies.show', $strategy)->with('success', 'Стратегия создана!');
    }

    public function show(UserStrategy $strategy)
    {
        abort_if($strategy->user_id !== Auth::id(), 403);
        $strategy->load(['conditions.indicator', 'signals' => fn($q) => $q->latest()->limit(50)]);

        $signals = $strategy->signals;
        $done    = $signals->where('status', 'DONE')->count();
        $total   = $signals->whereIn('status', ['DONE', 'MISSED'])->count();
        $winRate = $total > 0 ? round($done / $total * 100, 1) : 0;

        return view('strategies.show', compact('strategy', 'signals', 'winRate', 'done', 'total'));
    }

    public function edit(UserStrategy $strategy)
    {
        abort_if($strategy->user_id !== Auth::id(), 403);
        $strategy->load('conditions.indicator');
        $indicators = Indicator::orderBy('category')->orderBy('name')->get()->groupBy('category');
        return view('strategies.edit', compact('strategy', 'indicators'));
    }

    public function update(Request $request, UserStrategy $strategy)
    {
        abort_if($strategy->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'description'    => 'nullable|string|max:500',
            'symbol'         => 'required|string|max:20',
            'interval'       => 'required|in:1m,3m,5m,15m,30m,1h,2h,4h,6h,12h,1d',
            'candles_limit'  => 'required|integer|min:30|max:500',
            'tp_sl_mode'     => 'required|in:atr,percent',
            'tp_multiplier'  => 'required|numeric|min:0.1',
            'sl_multiplier'  => 'required|numeric|min:0.1',
            'mode'           => 'required|in:telegram,autotrading,manual',
            'telegram_chat_id' => 'nullable|string|max:50',
            'profile_id'     => 'nullable|exists:profiles,id',
            'is_active'      => 'boolean',
            'conditions'     => 'required|array|min:1',
            'conditions.*.signal_type'      => 'required|in:BUY,SELL',
            'conditions.*.indicator_id'     => 'required|exists:indicators,id',
            'conditions.*.indicator_output' => 'required|string',
            'conditions.*.operator'         => 'required|string',
            'conditions.*.value_a'          => 'required|numeric',
            'conditions.*.value_b'          => 'nullable|numeric',
            'conditions.*.next_logic'       => 'required|in:AND,OR',
        ]);

        $strategy->update([
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'symbol'           => strtoupper($data['symbol']),
            'interval'         => $data['interval'],
            'candles_limit'    => $data['candles_limit'],
            'tp_sl_mode'       => $data['tp_sl_mode'],
            'tp_multiplier'    => $data['tp_multiplier'],
            'sl_multiplier'    => $data['sl_multiplier'],
            'mode'             => $data['mode'],
            'telegram_chat_id' => $data['telegram_chat_id'] ?? null,
            'profile_id'       => $data['profile_id'] ?? null,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        $strategy->conditions()->delete();
        foreach (array_values($data['conditions']) as $order => $cond) {
            StrategyCondition::create([
                'strategy_id'      => $strategy->id,
                'signal_type'      => $cond['signal_type'],
                'indicator_id'     => $cond['indicator_id'],
                'indicator_output' => $cond['indicator_output'],
                'param_overrides'  => null,
                'operator'         => $cond['operator'],
                'value_a'          => $cond['value_a'],
                'value_b'          => $cond['value_b'] ?? null,
                'next_logic'       => $cond['next_logic'],
                'sort_order'       => $order,
            ]);
        }

        return redirect()->route('strategies.show', $strategy)->with('success', 'Стратегия обновлена!');
    }

    public function destroy(UserStrategy $strategy)
    {
        abort_if($strategy->user_id !== Auth::id(), 403);
        $strategy->signals()->delete();
        $strategy->conditions()->delete();
        $strategy->delete();
        return redirect()->route('strategies.index')->with('success', 'Стратегия удалена.');
    }

    public function toggleActive(UserStrategy $strategy)
    {
        abort_if($strategy->user_id !== Auth::id(), 403);
        $strategy->update(['is_active' => !$strategy->is_active]);
        return back()->with('success', $strategy->is_active ? 'Стратегия активирована.' : 'Стратегия остановлена.');
    }

    public function backtest(Request $request, UserStrategy $strategy, StrategyBacktestService $service)
    {
        abort_if($strategy->user_id !== Auth::id(), 403);
        $periods = (int)$request->get('periods', 300);

        set_time_limit(120);
        $result = $service->run($strategy, min($periods, 500));

        if (isset($result['error'])) {
            return back()->withErrors(['bt' => $result['error']]);
        }

        return view('strategies.backtest', compact('strategy', 'result'));
    }

    public function indicatorOutputs(Indicator $indicator)
    {
        return response()->json($indicator->outputs);
    }

    public function symbols()
    {
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->get('https://fapi.binance.com/fapi/v1/exchangeInfo');

        if (!$response->ok()) {
            return response()->json([]);
        }

        $symbols = collect($response->json('symbols', []))
            ->where('status', 'TRADING')
            ->where('quoteAsset', 'USDT')
            ->sortBy('symbol')
            ->pluck('symbol')
            ->values();

        return response()->json($symbols);
    }
}
