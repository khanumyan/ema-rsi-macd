<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\BinanceFuturesService;
use Illuminate\Http\Request;

class TradingController extends Controller
{
    private const POPULAR_SYMBOLS = [
        'BTCUSDT','ETHUSDT','SOLUSDT','BNBUSDT','XRPUSDT',
        'ADAUSDT','DOGEUSDT','AVAXUSDT','DOTUSDT','LINKUSDT',
        'LTCUSDT','MATICUSDT','ATOMUSDT','NEARUSDT','APTUSDT',
        'ARBUSDT','OPUSDT','INJUSDT','SEIUSDT','SUIUSDT',
    ];

    public function index(Request $request)
    {
        $profiles = Profile::orderByRaw("FIELD(category,'PROD','TEST')")
            ->orderBy('profile_name')
            ->get();

        $symbol    = strtoupper($request->query('symbol', 'BTCUSDT'));
        $market    = $request->query('market', 'futures');
        $profileId = $request->query('profile_id');

        $selectedProfile = $profileId
            ? $profiles->firstWhere('id', $profileId)
            : $profiles->first();

        $profilesJson = json_encode($profiles->map(function ($p) {
            return [
                'id'        => $p->id,
                'name'      => $p->profile_name,
                'category'  => $p->category,
                'hasSecret' => $p->hasApiSecret(),
            ];
        })->values());

        return view('trading.index', [
            'profiles'        => $profiles,
            'profilesJson'    => $profilesJson,
            'selectedProfile' => $selectedProfile,
            'symbol'          => $symbol,
            'market'          => $market,
            'popularSymbols'  => self::POPULAR_SYMBOLS,
        ]);
    }

    public function placeOrder(Request $request, Profile $profile)
    {
        $validated = $request->validate([
            'symbol'      => ['required', 'string', 'max:20'],
            'side'        => ['required', 'in:BUY,SELL'],
            'order_type'  => ['required', 'in:MARKET,LIMIT'],
            'quantity'    => ['required', 'numeric', 'gt:0'],
            'price'       => ['nullable', 'numeric', 'gt:0'],
            'margin_type' => ['nullable', 'in:CROSSED,ISOLATED'],
            'leverage'    => ['nullable', 'integer', 'min:1', 'max:125'],
            'tp'          => ['nullable', 'numeric', 'gt:0'],
            'sl'          => ['nullable', 'numeric', 'gt:0'],
        ]);

        if (!$profile->hasApiSecret()) {
            return response()->json(['error' => 'У профиля не задан API Secret.'], 400);
        }

        $baseUrl = $profile->category === Profile::CATEGORY_PROD
            ? BinanceFuturesService::productionBaseUrl()
            : BinanceFuturesService::testnetBaseUrl();

        $client = new BinanceFuturesService($baseUrl, $profile->profile_token, $profile->profile_secret);
        $symbol = strtoupper($validated['symbol']);

        try {
            // Установка типа маржи и плеча
            if (!empty($validated['margin_type'])) {
                $client->setMarginType($symbol, $validated['margin_type']);
            }
            if (!empty($validated['leverage'])) {
                $client->setLeverage($symbol, (int) $validated['leverage']);
            }

            $qty = $client->formatQuantityForSymbol($symbol, (string) $validated['quantity']);

            // Основной ордер
            if ($validated['order_type'] === 'MARKET') {
                $result = $client->placeMarketOrder($symbol, $validated['side'], $qty);
            } else {
                if (empty($validated['price'])) {
                    return response()->json(['error' => 'Для лимитного ордера нужна цена.'], 422);
                }
                $price  = $client->formatPriceForSymbol($symbol, (float) $validated['price']);
                $result = $client->placeLimitOrder($symbol, $validated['side'], $qty, $price);
            }

            if (isset($result['_error'])) {
                return response()->json(['error' => $result['_error']], 400);
            }

            $responses = ['order' => $result];

            // Take Profit
            if (!empty($validated['tp'])) {
                $tpSide  = $validated['side'] === 'BUY' ? 'SELL' : 'BUY';
                $tpPrice = $client->formatPriceForSymbol($symbol, (float) $validated['tp']);
                $tpRes   = $client->placeTakeProfitMarket($symbol, $tpSide, $qty, $tpPrice);
                $responses['tp'] = isset($tpRes['_error']) ? ['error' => $tpRes['_error']] : $tpRes;
            }

            // Stop Loss
            if (!empty($validated['sl'])) {
                $slSide  = $validated['side'] === 'BUY' ? 'SELL' : 'BUY';
                $slPrice = $client->formatPriceForSymbol($symbol, (float) $validated['sl']);
                $slRes   = $client->placeStopLossMarket($symbol, $slSide, $qty, $slPrice);
                $responses['sl'] = isset($slRes['_error']) ? ['error' => $slRes['_error']] : $slRes;
            }

            return response()->json(['success' => true, 'data' => $responses]);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelOrder(Request $request, Profile $profile)
    {
        $validated = $request->validate([
            'symbol'   => ['required', 'string'],
            'order_id' => ['required', 'integer'],
        ]);

        if (!$profile->hasApiSecret()) {
            return response()->json(['error' => 'API Secret не задан.'], 400);
        }

        $baseUrl = $profile->category === Profile::CATEGORY_PROD
            ? BinanceFuturesService::productionBaseUrl()
            : BinanceFuturesService::testnetBaseUrl();

        $client = new BinanceFuturesService($baseUrl, $profile->profile_token, $profile->profile_secret);
        $result = $client->cancelOrder(strtoupper($validated['symbol']), (int) $validated['order_id']);

        if (isset($result['_error'])) {
            return response()->json(['error' => $result['_error']], 400);
        }

        return response()->json(['success' => true]);
    }
}
