<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Binance USDⓈ-M Futures API (signed).
 * Testnet: https://demo-fapi.binance.com
 * Production: https://fapi.binance.com
 *
 * @see https://developers.binance.com/docs/derivatives/usds-margined-futures/general-info
 */
class BinanceFuturesService
{
    private string $baseUrl;
    private string $apiKey;
    private string $secretKey;
    private int $recvWindow = 20000;

    /** Кэш exchangeInfo по baseUrl (для точности quantity). */
    private static array $exchangeInfoCache = [];

    public function __construct(string $baseUrl, string $apiKey, string $secretKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = trim($apiKey);
        $this->secretKey = trim($secretKey);
    }

    public static function testnetBaseUrl(): string
    {
        return 'https://demo-fapi.binance.com';
    }

    public static function productionBaseUrl(): string
    {
        return 'https://fapi.binance.com';
    }

    /** WebSocket User Data Stream: production. */
    public static function wsBaseUrlProduction(): string
    {
        return 'wss://fstream.binance.com';
    }

    /** WebSocket User Data Stream: testnet. */
    public static function wsBaseUrlTestnet(): string
    {
        return 'wss://fstream.binancefuture.com';
    }

    /**
     * Request with API key only (no signature). For USER_STREAM endpoints.
     */
    private function requestWithApiKey(string $method, string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey,
        ])->timeout(30)->{strtolower($method)}($url);

        $body = $response->json();
        if (!$response->successful()) {
            return ['_error' => $body['msg'] ?? $response->body(), '_code' => $response->status()];
        }
        if (isset($body['code']) && $body['code'] !== 0) {
            return ['_error' => $body['msg'] ?? 'Unknown error', '_code' => $body['code']];
        }
        return $body;
    }

    /**
     * Signed request (HMAC SHA256).
     * GET: params in query string. POST: params in body (application/x-www-form-urlencoded).
     */
    private function signedRequest(string $method, string $endpoint, array $params = []): array
    {
        $params['timestamp'] = round(microtime(true) * 1000);
        $params['recvWindow'] = $this->recvWindow;

        $query = http_build_query($params);
        $signature = hash_hmac('sha256', $query, $this->secretKey);
        $signed = $query . '&signature=' . $signature;

        $url = $this->baseUrl . $endpoint;

        if (strtoupper($method) === 'GET') {
            $url .= '?' . $signed;
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey,
            ])->timeout(35)->get($url);
        } else {
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->timeout(35)->withBody($signed, 'application/x-www-form-urlencoded')->post($url);
        }

        $body = $response->json() ?? [];
        $code = $body['code'] ?? 0;

        if (!$response->successful()) {
            if (in_array($code, [-4046, -4051], true)) {
                return [];
            }
            Log::warning('BinanceFutures: Request failed', [
                'url' => $this->baseUrl . $endpoint,
                'status' => $response->status(),
                'body' => $body,
            ]);
            return ['_error' => $body['msg'] ?? $response->body(), '_code' => $response->status()];
        }

        // Документация Binance: успех = code 0 (типично) или 200 (напр. Change Margin Type: {"code":200,"msg":"success"})
        $isSuccess = ($code === 0 || $code === 200);
        if (!$isSuccess) {
            if (in_array($code, [-4046, -4051], true)) {
                return [];
            }
            return ['_error' => $body['msg'] ?? 'Unknown error', '_code' => $code];
        }

        return $body;
    }

    /**
     * Set margin type to CROSSED for symbol.
     */
    public function setMarginType(string $symbol, string $marginType = 'CROSSED'): array
    {
        return $this->signedRequest('POST', '/fapi/v1/marginType', [
            'symbol' => $symbol,
            'marginType' => $marginType,
        ]);
    }

    /**
     * Set leverage for symbol (e.g. 1 for 1x).
     */
    public function setLeverage(string $symbol, int $leverage = 1): array
    {
        return $this->signedRequest('POST', '/fapi/v1/leverage', [
            'symbol' => $symbol,
            'leverage' => $leverage,
        ]);
    }

    /**
     * Place MARKET order. Quantity in base asset (e.g. BTC).
     */
    public function placeMarketOrder(string $symbol, string $side, string $quantity): array
    {
        return $this->signedRequest('POST', '/fapi/v1/order', [
            'symbol' => $symbol,
            'side' => $side,
            'type' => 'MARKET',
            'quantity' => $quantity,
        ]);
    }

    /**
     * Place LIMIT order. timeInForce defaults to GTC.
     */
    public function placeLimitOrder(string $symbol, string $side, string $quantity, string $price, string $timeInForce = 'GTC'): array
    {
        return $this->signedRequest('POST', '/fapi/v1/order', [
            'symbol'      => $symbol,
            'side'        => $side,
            'type'        => 'LIMIT',
            'timeInForce' => $timeInForce,
            'quantity'    => $quantity,
            'price'       => $price,
        ]);
    }

    /**
     * Cancel an open order.
     */
    public function cancelOrder(string $symbol, int $orderId): array
    {
        return $this->signedRequest('DELETE', '/fapi/v1/order', [
            'symbol'  => $symbol,
            'orderId' => $orderId,
        ]);
    }

    /**
     * Place STOP_MARKET (stop loss) via Algo Order API.
     * Binance требует STOP_MARKET/TAKE_PROFIT_MARKET через POST /fapi/v1/algoOrder (ошибка -4120 иначе).
     */
    public function placeStopLossMarket(string $symbol, string $side, string $quantity, string $stopPrice): array
    {
        return $this->placeAlgoConditionalOrder($symbol, $side, 'STOP_MARKET', $quantity, $stopPrice);
    }

    /**
     * Place TAKE_PROFIT_MARKET via Algo Order API.
     */
    public function placeTakeProfitMarket(string $symbol, string $side, string $quantity, string $stopPrice): array
    {
        return $this->placeAlgoConditionalOrder($symbol, $side, 'TAKE_PROFIT_MARKET', $quantity, $stopPrice);
    }

    /**
     * Условный ордер через Algo Order API (STOP_MARKET, TAKE_PROFIT_MARKET).
     * Endpoint: POST /fapi/v1/algoOrder. quantity и triggerPrice форматируются по правилам символа (-1111 Precision).
     */
    private function placeAlgoConditionalOrder(string $symbol, string $side, string $type, string $quantity, string $triggerPrice): array
    {
        $quantity = $this->formatQuantityForSymbol($symbol, $quantity);
        $triggerPrice = $this->formatPriceForSymbol($symbol, (float) $triggerPrice);
        $params = [
            'algoType' => 'CONDITIONAL',
            'symbol' => $symbol,
            'side' => $side,
            'type' => $type,
            'quantity' => $quantity,
            'triggerPrice' => $triggerPrice,
            'reduceOnly' => 'true',
        ];
        return $this->signedRequest('POST', '/fapi/v1/algoOrder', $params);
    }

    /**
     * Round quantity to reasonable precision (Binance often uses 5 decimal places for base).
     */
    public static function quantityForOneUsdt(float $price, int $decimals = 5): string
    {
        if ($price <= 0) {
            return '0';
        }
        $qty = 1 / $price;
        $decimals = max(0, min(8, $decimals));
        return number_format(round($qty, $decimals), $decimals, '.', '');
    }

    /**
     * GET /fapi/v1/exchangeInfo (public, без подписи). Кэшируется по baseUrl.
     */
    public function getExchangeInfo(): array
    {
        $key = $this->baseUrl;
        if (isset(self::$exchangeInfoCache[$key])) {
            return self::$exchangeInfoCache[$key];
        }
        $url = $this->baseUrl . '/fapi/v1/exchangeInfo';
        $response = Http::timeout(30)->get($url);
        $body = $response->json();
        if (!$response->successful() || empty($body['symbols'])) {
            return [];
        }
        self::$exchangeInfoCache[$key] = $body;
        return $body;
    }

    /**
     * GET /fapi/v1/premiumIndex — markPrice (public, без подписи).
     * Возвращает float markPrice или null.
     */
    public function getMarkPrice(string $symbol): ?float
    {
        $symbol = strtoupper($symbol);
        $url = $this->baseUrl . '/fapi/v1/premiumIndex?' . http_build_query(['symbol' => $symbol]);
        $response = Http::timeout(20)->get($url);
        if (!$response->successful()) {
            return null;
        }
        $body = $response->json();
        $mp = $body['markPrice'] ?? null;
        if ($mp === null) {
            return null;
        }
        $mpf = (float) $mp;
        return $mpf > 0 ? $mpf : null;
    }

    /**
     * LOT_SIZE для символа: stepSize (float), decimals (для форматирования), minQty (float).
     */
    public function getLotSize(string $symbol): ?array
    {
        $info = $this->getExchangeInfo();
        $symbol = strtoupper($symbol);
        foreach ($info['symbols'] ?? [] as $s) {
            if (($s['symbol'] ?? '') !== $symbol) {
                continue;
            }
            foreach ($s['filters'] ?? [] as $f) {
                if (($f['filterType'] ?? '') !== 'LOT_SIZE' || empty($f['stepSize'])) {
                    continue;
                }
                $stepStr = (string) $f['stepSize'];
                $step = (float) $stepStr;
                if ($step <= 0) {
                    return null;
                }
                $decimals = 0;
                if (str_contains($stepStr, '.')) {
                    $after = rtrim(explode('.', $stepStr)[1] ?? '0', '0');
                    $decimals = strlen($after);
                }
                $minQty = (float) ($f['minQty'] ?? 0);
                return ['stepSize' => $step, 'decimals' => $decimals, 'minQty' => $minQty];
            }
            $precision = (int) ($s['quantityPrecision'] ?? 3);
            $precision = max(0, min(8, $precision));
            return ['stepSize' => 10 ** (-$precision), 'decimals' => $precision, 'minQty' => 0.0];
        }
        return null;
    }

    /**
     * PRICE_FILTER для символа: tickSize (float), decimals (для форматирования цены/triggerPrice).
     */
    public function getPriceFilter(string $symbol): ?array
    {
        $info = $this->getExchangeInfo();
        $symbol = strtoupper($symbol);
        foreach ($info['symbols'] ?? [] as $s) {
            if (($s['symbol'] ?? '') !== $symbol) {
                continue;
            }
            foreach ($s['filters'] ?? [] as $f) {
                if (($f['filterType'] ?? '') !== 'PRICE_FILTER' || empty($f['tickSize'])) {
                    continue;
                }
                $tickStr = (string) $f['tickSize'];
                $tick = (float) $tickStr;
                if ($tick <= 0) {
                    return null;
                }
                $decimals = 0;
                if (str_contains($tickStr, '.')) {
                    $after = rtrim(explode('.', $tickStr)[1] ?? '0', '0');
                    $decimals = strlen($after);
                }
                return ['tickSize' => $tick, 'decimals' => $decimals];
            }
            $precision = (int) ($s['pricePrecision'] ?? 2);
            $precision = max(0, min(8, $precision));
            return ['tickSize' => 10 ** (-$precision), 'decimals' => $precision];
        }
        return null;
    }

    /**
     * Форматировать цену (triggerPrice и т.д.) по правилам символа — устраняет -1111 Precision.
     */
    public function formatPriceForSymbol(string $symbol, float $price): string
    {
        $filter = $this->getPriceFilter($symbol);
        if (!$filter) {
            return (string) round($price, 8);
        }
        $decimals = $filter['decimals'];
        $tick = $filter['tickSize'];
        $rounded = round($price / $tick) * $tick;
        return number_format(round($rounded, $decimals), $decimals, '.', '');
    }

    /**
     * Форматировать quantity по правилам символа (LOT_SIZE) — для Algo Order и др.
     */
    public function formatQuantityForSymbol(string $symbol, string $quantity): string
    {
        $lot = $this->getLotSize($symbol);
        if (!$lot) {
            return $quantity;
        }
        $step = $lot['stepSize'];
        $decimals = $lot['decimals'];
        $qty = (float) $quantity;
        if ($qty <= 0) {
            return $quantity;
        }
        $qty = floor($qty / $step) * $step;
        $qty = round($qty, $decimals);
        return number_format($qty, $decimals, '.', '');
    }

    /**
     * Количество для ордера с заданным номиналом (USDT): округление вниз до stepSize (LOT_SIZE).
     * Минимум на Binance — 5 USDT.
     */
    public function quantityForNotionalForSymbol(string $symbol, float $price, float $notionalUsdt = 5.0): string
    {
        if ($price <= 0 || $notionalUsdt <= 0) {
            return '0';
        }
        $lot = $this->getLotSize($symbol);
        if (!$lot) {
            $decimals = 3;
            $raw = $notionalUsdt / $price;
            return number_format(round($raw, $decimals), $decimals, '.', '');
        }
        $step = $lot['stepSize'];
        $decimals = $lot['decimals'];
        $minQty = $lot['minQty'];
        $raw = $notionalUsdt / $price;
        $qty = floor($raw / $step) * $step;
        if ($qty < $minQty) {
            return '0';
        }
        $qty = round($qty, $decimals);
        return number_format($qty, $decimals, '.', '');
    }

    /**
     * @deprecated Используйте quantityForNotionalForSymbol($symbol, $price, 5.0)
     */
    public function quantityForOneUsdtForSymbol(string $symbol, float $price): string
    {
        return $this->quantityForNotionalForSymbol($symbol, $price, 5.0);
    }

    /**
     * Максимальное допустимое плечо по символу (из leverageBracket). При ошибке — 20.
     */
    public function getMaxLeverage(string $symbol): int
    {
        $result = $this->signedRequest('GET', '/fapi/v1/leverageBracket', ['symbol' => strtoupper($symbol)]);
        if (isset($result['_error'])) {
            return 20;
        }
        $brackets = $result[0]['brackets'] ?? $result['brackets'] ?? [];
        $max = 0;
        foreach ($brackets as $b) {
            $lev = (int) ($b['initialLeverage'] ?? 0);
            if ($lev > $max) {
                $max = $lev;
            }
        }
        return $max > 0 ? $max : 20;
    }

    /**
     * GET /fapi/v2/account — баланс, позиции, totalUnrealizedProfit и т.д.
     */
    public function getAccount(): array
    {
        return $this->signedRequest('GET', '/fapi/v2/account');
    }

    /**
     * GET /fapi/v2/positionRisk — текущие позиции (риск по каждой).
     */
    public function getPositionRisk(): array
    {
        return $this->signedRequest('GET', '/fapi/v2/positionRisk');
    }

    /**
     * GET /fapi/v1/income — история доходов (realized PNL, funding и т.д.).
     */
    public function getIncome(?string $symbol = null, ?string $incomeType = null, int $limit = 100): array
    {
        $params = ['limit' => $limit];
        if ($symbol !== null) {
            $params['symbol'] = $symbol;
        }
        if ($incomeType !== null) {
            $params['incomeType'] = $incomeType;
        }
        return $this->signedRequest('GET', '/fapi/v1/income', $params);
    }

    /**
     * GET /fapi/v1/userTrades — история сделок по символу.
     */
    public function getUserTrades(string $symbol, int $limit = 100): array
    {
        return $this->signedRequest('GET', '/fapi/v1/userTrades', [
            'symbol' => $symbol,
            'limit' => $limit,
        ]);
    }

    /**
     * GET /fapi/v1/openOrders — открытые ордера (опционально по символу).
     */
    public function getOpenOrders(?string $symbol = null): array
    {
        $params = [];
        if ($symbol !== null && $symbol !== '') {
            $params['symbol'] = $symbol;
        }
        return $this->signedRequest('GET', '/fapi/v1/openOrders', $params);
    }

    /**
     * GET /fapi/v1/openAlgoOrders — открытые условные (algo) ордера (STOP_MARKET, TAKE_PROFIT_MARKET и т.д.).
     */
    public function getOpenAlgoOrders(?string $symbol = null): array
    {
        $params = [];
        if ($symbol !== null && $symbol !== '') {
            $params['symbol'] = $symbol;
        }
        $result = $this->signedRequest('GET', '/fapi/v1/openAlgoOrders', $params);
        if (isset($result['_error']) || !is_array($result)) {
            return [];
        }
        return $result;
    }

    /**
     * GET /fapi/v1/allOrders — история ордеров по символу.
     */
    public function getAllOrders(string $symbol, int $limit = 100): array
    {
        return $this->signedRequest('GET', '/fapi/v1/allOrders', [
            'symbol' => $symbol,
            'limit' => $limit,
        ]);
    }

    /**
     * Закрыть позицию: MARKET ордер с reduceOnly=true.
     * $side — противоположная позиции: для LONG закрываем SELL, для SHORT — BUY.
     * $positionSide — для Hedge Mode: LONG или SHORT; для One-Way можно null или BOTH.
     */
    public function closePosition(string $symbol, string $side, string $quantity, ?string $positionSide = null): array
    {
        $params = [
            'symbol' => $symbol,
            'side' => $side,
            'type' => 'MARKET',
            'quantity' => $quantity,
            'reduceOnly' => 'true',
        ];
        if ($positionSide !== null && $positionSide !== '' && in_array($positionSide, ['LONG', 'SHORT'], true)) {
            $params['positionSide'] = $positionSide;
        }
        return $this->signedRequest('POST', '/fapi/v1/order', $params);
    }

    /**
     * POST /fapi/v1/listenKey — создать User Data Stream (только API Key, без подписи).
     * Возвращает listenKey для подключения к WebSocket.
     */
    public function createListenKey(): array
    {
        return $this->requestWithApiKey('POST', '/fapi/v1/listenKey');
    }

    /**
     * PUT /fapi/v1/listenKey — продлить listenKey на 60 минут.
     */
    public function keepaliveListenKey(): array
    {
        return $this->requestWithApiKey('PUT', '/fapi/v1/listenKey');
    }

    /**
     * DELETE /fapi/v1/listenKey — закрыть стрим.
     */
    public function closeListenKey(): array
    {
        return $this->requestWithApiKey('DELETE', '/fapi/v1/listenKey');
    }
}
