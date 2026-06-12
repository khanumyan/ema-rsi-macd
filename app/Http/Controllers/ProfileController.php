<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\BinanceFuturesService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $profiles = Profile::orderBy('category')->orderBy('profile_name')->get();

        return view('profiles.index', compact('profiles'));
    }

    public function create()
    {
        $profile = null;

        return view('profiles.form', compact('profile'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'profile_name' => ['required', 'string', 'max:255'],
            'profile_token' => ['required', 'string', 'max:500'],
            'profile_secret' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'category' => ['required', Rule::in(Profile::categories())],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Profile::create($validated);

        return redirect()->route('profiles.index')->with('message', 'Профиль добавлен.');
    }

    public function edit(Profile $profile)
    {
        return view('profiles.form', compact('profile'));
    }

    public function update(Request $request, Profile $profile)
    {
        $validated = $request->validate([
            'profile_name' => ['required', 'string', 'max:255'],
            'profile_token' => ['required', 'string', 'max:500'],
            'profile_secret' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'category' => ['required', Rule::in(Profile::categories())],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        if (array_key_exists('profile_secret', $validated) && $validated['profile_secret'] === '') {
            unset($validated['profile_secret']);
        }

        $profile->update($validated);

        return redirect()->route('profiles.index')->with('message', 'Профиль обновлён.');
    }

    public function destroy(Profile $profile)
    {
        $profile->delete();

        return redirect()->route('profiles.index')->with('message', 'Профиль удалён.');
    }

    /**
     * Страница профиля: PNL, баланс, вкладки «Активные позиции» / «Все позиции».
     */
    public function show(Profile $profile)
    {
        $account = null;
        $positionRisk = null;
        $error = null;
        $baseUrl = $profile->category === Profile::CATEGORY_PROD
            ? BinanceFuturesService::productionBaseUrl()
            : BinanceFuturesService::testnetBaseUrl();

        if ($profile->hasApiSecret()) {
            try {
                $client = new BinanceFuturesService(
                    $baseUrl,
                    $profile->profile_token,
                    $profile->profile_secret
                );
                $account = $client->getAccount();
                if (isset($account['_error'])) {
                    $error = $account['_error'];
                    $account = null;
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'У профиля не задан API Secret. Добавьте его в редактировании профиля.';
        }

        $positions = ($account['positions'] ?? []);
        $activePositions = array_values(array_filter($positions, function ($p) {
            $amt = (float) ($p['positionAmt'] ?? 0);
            return $amt != 0;
        }));
        $totalUnrealized = (float) ($account['totalUnrealizedProfit'] ?? 0);
        $totalWallet = (float) ($account['totalWalletBalance'] ?? 0);
        $totalMargin = (float) ($account['totalMarginBalance'] ?? 0);

        $openOrders = [];
        $positionRisk = null;
        $income = [];
        $orderHistory = [];
        $tradeHistory = [];
        $assets = $account['assets'] ?? [];
        $historySymbol = null;
        $historySymbolsCandidates = [];

        if ($profile->hasApiSecret() && !$error) {
            try {
                $client = new BinanceFuturesService(
                    $baseUrl,
                    $profile->profile_token,
                    $profile->profile_secret
                );
                $positionRisk = $client->getPositionRisk();
                if (is_array($positionRisk) && !isset($positionRisk['_error'])) {
                    $positions = $positionRisk;
                    $activePositions = array_values(array_filter($positions, function ($p) {
                        return (float) ($p['positionAmt'] ?? 0) != 0;
                    }));
                }
                $openOrdersResp = $client->getOpenOrders();
                if (!isset($openOrdersResp['_error']) && is_array($openOrdersResp)) {
                    $openOrders = $openOrdersResp;
                }
                $algoOrders = $client->getOpenAlgoOrders();
                foreach ($algoOrders as $ao) {
                    $openOrders[] = [
                        'symbol' => $ao['symbol'] ?? '',
                        'side' => $ao['side'] ?? '',
                        'type' => $ao['orderType'] ?? 'ALGO',
                        'stopPrice' => $ao['triggerPrice'] ?? null,
                        'price' => $ao['price'] ?? null,
                        'origQty' => $ao['quantity'] ?? null,
                        'status' => $ao['algoStatus'] ?? 'NEW',
                        'time' => $ao['createTime'] ?? 0,
                        'orderId' => $ao['algoId'] ?? null,
                        '_isAlgo' => true,
                    ];
                }
                usort($openOrders, fn ($a, $b) => ($b['time'] ?? 0) <=> ($a['time'] ?? 0));

                $incomeResp = $client->getIncome(null, null, 100);
                if (!isset($incomeResp['_error']) && is_array($incomeResp)) {
                    $income = $incomeResp;
                }

                $historySymbolsCandidates = array_values(array_unique(array_filter(array_merge(
                    array_map(fn($p) => $p['symbol'] ?? '', $activePositions),
                    array_map(fn($o) => $o['symbol'] ?? '', $openOrders),
                    array_map(fn($i) => $i['symbol'] ?? '', $income)
                ))));

                $requestedHistorySymbol = strtoupper(trim((string) request('history_symbol', '')));
                $manual = strtoupper(trim((string) request('history_symbol_manual', '')));
                if ($manual !== '') {
                    $requestedHistorySymbol = $manual;
                }
                $historyTab = request('tab');
                $onOrderOrTradeHistoryTab = ($historyTab === 'order_history' || $historyTab === 'trade_history');
                $fetchAllHistory = ($requestedHistorySymbol === '__ALL__' || $requestedHistorySymbol === 'ВСЕ' || $requestedHistorySymbol === 'ALL' || $onOrderOrTradeHistoryTab);
                if (!$fetchAllHistory && $requestedHistorySymbol !== '') {
                    if (!str_ends_with($requestedHistorySymbol, 'USDT')) {
                        $requestedHistorySymbol .= 'USDT';
                    }
                    $historySymbol = $requestedHistorySymbol;
                }

                $symbolsToFetch = $fetchAllHistory
                    ? array_slice($historySymbolsCandidates, 0, 40)
                    : ($historySymbol !== null ? [$historySymbol] : []);

                foreach ($symbolsToFetch as $sym) {
                    if ($sym === '') continue;
                    $orders = $client->getAllOrders($sym, 50);
                    if (!isset($orders['_error']) && is_array($orders)) {
                        foreach ($orders as $o) {
                            $o['_symbol'] = $sym;
                            $orderHistory[] = $o;
                        }
                    }
                    $trades = $client->getUserTrades($sym, 50);
                    if (!isset($trades['_error']) && is_array($trades)) {
                        foreach ($trades as $t) {
                            $t['_symbol'] = $sym;
                            $tradeHistory[] = $t;
                        }
                    }
                }
                if (!empty($orderHistory)) {
                    usort($orderHistory, function ($a, $b) {
                        return ($b['time'] ?? 0) <=> ($a['time'] ?? 0);
                    });
                }
                if (!empty($tradeHistory)) {
                    usort($tradeHistory, function ($a, $b) {
                        return ($b['time'] ?? 0) <=> ($a['time'] ?? 0);
                    });
                }
                if ($fetchAllHistory) {
                    $historySymbol = '__ALL__';
                }
            } catch (\Throwable $e) {
                // keep empty arrays
            }
        }

        return view('profiles.show', [
            'profile' => $profile,
            'account' => $account,
            'positions' => $positions,
            'activePositions' => $activePositions,
            'openOrders' => $openOrders,
            'orderHistory' => $orderHistory,
            'tradeHistory' => $tradeHistory,
            'income' => $income,
            'assets' => $assets,
            'historySymbol' => $historySymbol,
            'historySymbolsCandidates' => $historySymbolsCandidates,
            'totalUnrealizedProfit' => $totalUnrealized,
            'totalWalletBalance' => $totalWallet,
            'totalMarginBalance' => $totalMargin,
            'error' => $error,
        ]);
    }

    /**
     * URL для WebSocket User Data Stream (для реального времени: баланс, позиции, ордера).
     */
    public function streamUrl(Profile $profile)
    {
        if (!$profile->hasApiSecret()) {
            return response()->json(['error' => 'У профиля не задан API Secret.'], 400);
        }
        $baseUrl = $profile->category === Profile::CATEGORY_PROD
            ? BinanceFuturesService::productionBaseUrl()
            : BinanceFuturesService::testnetBaseUrl();
        $wsBase = $profile->category === Profile::CATEGORY_PROD
            ? BinanceFuturesService::wsBaseUrlProduction()
            : BinanceFuturesService::wsBaseUrlTestnet();

        $client = new BinanceFuturesService(
            $baseUrl,
            $profile->profile_token,
            $profile->profile_secret
        );
        $result = $client->createListenKey();
        if (isset($result['_error'])) {
            return response()->json(['error' => $result['_error']], 400);
        }
        $listenKey = $result['listenKey'] ?? '';
        if ($listenKey === '') {
            return response()->json(['error' => 'Binance не вернул listenKey.'], 502);
        }
        $wsUrl = rtrim($wsBase, '/') . '/ws/' . $listenKey;
        return response()->json(['wsUrl' => $wsUrl, 'listenKey' => $listenKey]);
    }

    /**
     * JSON со всеми данными для вкладок (обновление по WebSocket).
     * ?light=1 — только баланс, позиции и открытые ордера (2 запроса к Binance), для real-time.
     */
    public function accountData(Request $request, Profile $profile)
    {
        if (!$profile->hasApiSecret()) {
            return response()->json(['error' => 'API Secret не задан.'], 400);
        }
        $baseUrl = $profile->category === Profile::CATEGORY_PROD
            ? BinanceFuturesService::productionBaseUrl()
            : BinanceFuturesService::testnetBaseUrl();
        $client = new BinanceFuturesService(
            $baseUrl,
            $profile->profile_token,
            $profile->profile_secret
        );
        $account = $client->getAccount();
        if (isset($account['_error'])) {
            return response()->json(['error' => $account['_error']], 400);
        }
        $positionRisk = $client->getPositionRisk();
        $positions = (is_array($positionRisk) && !isset($positionRisk['_error']))
            ? $positionRisk
            : ($account['positions'] ?? []);
        $activePositions = array_values(array_filter($positions, function ($p) {
            return (float) ($p['positionAmt'] ?? 0) != 0;
        }));
        $openOrdersResp = $client->getOpenOrders();
        $openOrders = !isset($openOrdersResp['_error']) && is_array($openOrdersResp) ? $openOrdersResp : [];
        $algoOrders = $client->getOpenAlgoOrders();
        foreach ($algoOrders as $ao) {
            $openOrders[] = [
                'symbol' => $ao['symbol'] ?? '',
                'side' => $ao['side'] ?? '',
                'type' => $ao['orderType'] ?? 'ALGO',
                'stopPrice' => $ao['triggerPrice'] ?? null,
                'price' => $ao['price'] ?? null,
                'origQty' => $ao['quantity'] ?? null,
                'status' => $ao['algoStatus'] ?? 'NEW',
                'time' => $ao['createTime'] ?? 0,
                'orderId' => $ao['algoId'] ?? null,
                '_isAlgo' => true,
            ];
        }
        usort($openOrders, fn ($a, $b) => ($b['time'] ?? 0) <=> ($a['time'] ?? 0));

        if ($request->boolean('light')) {
            return response()->json([
                'totalUnrealizedProfit' => (float) ($account['totalUnrealizedProfit'] ?? 0),
                'totalWalletBalance' => (float) ($account['totalWalletBalance'] ?? 0),
                'totalMarginBalance' => (float) ($account['totalMarginBalance'] ?? 0),
                'positions' => $positions,
                'activePositions' => $activePositions,
                'openOrders' => $openOrders,
            ]);
        }

        $tab = $request->string('tab')->toString();
        if ($tab === 'income') {
            $incomeResp = $client->getIncome(null, null, 50);
            $income = !isset($incomeResp['_error']) && is_array($incomeResp) ? $incomeResp : [];
            return response()->json(['income' => $income]);
        }
        $historySymbol = $request->string('symbol')->trim()->upper();
        if ($historySymbol !== '' && !str_ends_with($historySymbol, 'USDT')) {
            $historySymbol .= 'USDT';
        }
        if ($tab === 'order_history' && $historySymbol !== '') {
            $orders = $client->getAllOrders($historySymbol, 50);
            $list = [];
            if (!isset($orders['_error']) && is_array($orders)) {
                foreach ($orders as $o) {
                    $o['_symbol'] = $historySymbol;
                    $list[] = $o;
                }
            }
            usort($list, fn ($a, $b) => ($b['time'] ?? 0) <=> ($a['time'] ?? 0));
            return response()->json(['orderHistory' => $list]);
        }
        if ($tab === 'trade_history' && $historySymbol !== '') {
            $trades = $client->getUserTrades($historySymbol, 50);
            $list = [];
            if (!isset($trades['_error']) && is_array($trades)) {
                foreach ($trades as $t) {
                    $t['_symbol'] = $historySymbol;
                    $list[] = $t;
                }
            }
            usort($list, fn ($a, $b) => ($b['time'] ?? 0) <=> ($a['time'] ?? 0));
            return response()->json(['tradeHistory' => $list]);
        }

        $incomeResp = $client->getIncome(null, null, 50);
        $income = !isset($incomeResp['_error']) && is_array($incomeResp) ? $incomeResp : [];
        $assets = $account['assets'] ?? [];
        return response()->json([
            'totalUnrealizedProfit' => (float) ($account['totalUnrealizedProfit'] ?? 0),
            'totalWalletBalance' => (float) ($account['totalWalletBalance'] ?? 0),
            'totalMarginBalance' => (float) ($account['totalMarginBalance'] ?? 0),
            'positions' => $positions,
            'activePositions' => $activePositions,
            'openOrders' => $openOrders,
            'orderHistory' => [],
            'tradeHistory' => [],
            'income' => $income,
            'assets' => $assets,
        ]);
    }

    /**
     * Закрыть позицию (MARKET reduceOnly).
     */
    public function closePosition(Request $request, Profile $profile)
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', 'max:20'],
            'side' => ['required', 'string', 'in:BUY,SELL'],
            'quantity' => ['required', 'string'],
            'position_side' => ['nullable', 'string', 'in:LONG,SHORT,BOTH'],
        ]);
        if (!$profile->hasApiSecret()) {
            return response()->json(['error' => 'API Secret не задан.'], 400);
        }
        $baseUrl = $profile->category === Profile::CATEGORY_PROD
            ? BinanceFuturesService::productionBaseUrl()
            : BinanceFuturesService::testnetBaseUrl();
        $client = new BinanceFuturesService(
            $baseUrl,
            $profile->profile_token,
            $profile->profile_secret
        );
        $result = $client->closePosition(
            strtoupper($validated['symbol']),
            $validated['side'],
            $validated['quantity'],
            $validated['position_side'] ?? null
        );
        if (isset($result['_error'])) {
            return response()->json(['error' => $result['_error']], 400);
        }
        return response()->json(['success' => true, 'order' => $result]);
    }

    /**
     * Детальная страница одной позиции: полная инфа от Binance + график TradingView.
     */
    public function showPosition(Request $request, Profile $profile, string $symbol)
    {
        $symbol = strtoupper($symbol);
        if (!str_ends_with($symbol, 'USDT')) {
            $symbol .= 'USDT';
        }

        $position = null;
        $trades = [];
        $error = null;
        $baseUrl = $profile->category === Profile::CATEGORY_PROD
            ? BinanceFuturesService::productionBaseUrl()
            : BinanceFuturesService::testnetBaseUrl();

        if ($profile->hasApiSecret()) {
            try {
                $client = new BinanceFuturesService(
                    $baseUrl,
                    $profile->profile_token,
                    $profile->profile_secret
                );
                $positionRisk = $client->getPositionRisk();
                if (!isset($positionRisk['_error'])) {
                    $all = is_array($positionRisk) ? $positionRisk : [];
                    foreach ($all as $p) {
                        if (($p['symbol'] ?? '') === $symbol) {
                            $position = $p;
                            break;
                        }
                    }
                } else {
                    $error = $positionRisk['_error'];
                }
                if ($position === null && !$error) {
                    $account = $client->getAccount();
                    if (!isset($account['_error']) && !empty($account['positions'])) {
                        foreach ($account['positions'] as $p) {
                            if (($p['symbol'] ?? '') === $symbol) {
                                $position = $p;
                                break;
                            }
                        }
                    }
                }
                $tradesResp = $client->getUserTrades($symbol, 50);
                if (!isset($tradesResp['_error']) && is_array($tradesResp)) {
                    $trades = $tradesResp;
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'У профиля не задан API Secret.';
        }

        return view('profiles.position', [
            'profile' => $profile,
            'symbol' => $symbol,
            'position' => $position,
            'trades' => $trades,
            'error' => $error,
        ]);
    }
}
