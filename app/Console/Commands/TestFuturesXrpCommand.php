<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Services\BinanceFuturesService;
use Illuminate\Console\Command;

/**
 * Тест открытия позиции по XRP с той же логикой, что в EmaRsiMacdCommand.
 * Проверяет: marginType ISOLATED, leverage, market order, stop loss (Algo API), take profit (Algo API).
 */
class TestFuturesXrpCommand extends Command
{
    protected $signature = 'test:futures-xrp
                            {--profile= : Имя профиля (по умолчанию первый активный TEST)}
                            {--side=BUY : BUY или SELL}
                            {--dry-run : Только показать параметры, не отправлять ордера}';

    protected $description = 'Тест открытия позиции XRPUSDT (логика как в crypto:ema-rsi-macd)';

    public function handle(): int
    {
        $profileName = $this->option('profile');
        $side = strtoupper($this->option('side'));
        $dryRun = $this->option('dry-run');

        if (!in_array($side, ['BUY', 'SELL'], true)) {
            $this->error('--side должен быть BUY или SELL');
            return 1;
        }

        $profile = $profileName
            ? Profile::where('is_active', true)->where('profile_name', $profileName)->first()
            : Profile::where('is_active', true)->where('category', Profile::CATEGORY_TEST)->first();

        if (!$profile || !$profile->hasApiSecret()) {
            $this->error('Не найден активный профиль с API Secret (укажите --profile=имя или добавьте TEST профиль).');
            return 1;
        }

        $baseUrl = $profile->category === Profile::CATEGORY_PROD
            ? BinanceFuturesService::productionBaseUrl()
            : BinanceFuturesService::testnetBaseUrl();

        $this->info('Профиль: ' . $profile->profile_name . ' (' . $profile->category . ')');
        $this->info('URL: ' . $baseUrl);
        $this->info('Символ: XRPUSDT | Сторона: ' . $side);
        if ($dryRun) {
            $this->warn('Режим --dry-run: ордера не отправляются.');
        }
        $this->newLine();

        try {
            $client = new BinanceFuturesService(
                $baseUrl,
                $profile->profile_token,
                $profile->profile_secret
            );
        } catch (\Throwable $e) {
            $this->error('Ошибка инициализации: ' . $e->getMessage());
            return 1;
        }

        $symbol = 'XRPUSDT';
        $client->getExchangeInfo();
        $markPrice = $client->getMarkPrice($symbol);
        if ($markPrice === null) {
            $this->error('Не удалось получить mark price для ' . $symbol);
            return 1;
        }
        $price = $markPrice;
        $quantity = $client->quantityForNotionalForSymbol($symbol, $price, 10.0);
        $leverage = min(20, $client->getMaxLeverage($symbol));

        if ((float) $quantity <= 0) {
            $this->error('Рассчитанное количество = 0 (проверьте lot size для ' . $symbol . ')');
            return 1;
        }

        if ($side === 'BUY') {
            $stopLoss = (string) round($price * 0.98, 4);
            $takeProfit = (string) round($price * 1.02, 4);
        } else {
            $stopLoss = (string) round($price * 1.02, 4);
            $takeProfit = (string) round($price * 0.98, 4);
        }

        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Mark price', $price],
                ['Quantity', $quantity],
                ['Leverage', $leverage . 'x'],
                ['Stop Loss (trigger)', $stopLoss],
                ['Take Profit (trigger)', $takeProfit],
            ]
        );
        $this->newLine();

        if ($dryRun) {
            $this->info('Dry-run завершён. Запустите без --dry-run для отправки ордеров.');
            return 0;
        }

        $steps = [
            ['marginType', fn () => $client->setMarginType($symbol, 'ISOLATED')],
            ['leverage', fn () => $client->setLeverage($symbol, $leverage)],
            ['order', fn () => $client->placeMarketOrder($symbol, $side, $quantity)],
            ['stopLoss', fn () => $client->placeStopLossMarket($symbol, $side === 'BUY' ? 'SELL' : 'BUY', $quantity, $stopLoss)],
            ['takeProfit', fn () => $client->placeTakeProfitMarket($symbol, $side === 'BUY' ? 'SELL' : 'BUY', $quantity, $takeProfit)],
        ];

        foreach ($steps as [$step, $call]) {
            $this->info("Шаг: {$step}...");
            $result = $call();
            if (isset($result['_error'])) {
                $msg = $result['_error'];
                if (str_contains($msg, 'No need to change margin type') || str_contains($msg, 'No need to change leverage') || strtolower(trim($msg)) === 'success') {
                    $this->line("  → OK (уже установлено или success)");
                } else {
                    $this->error("  → Ошибка: {$msg}");
                    return 1;
                }
            } else {
                $this->line('  → OK');
                if (!empty($result['orderId'])) {
                    $this->line('  orderId: ' . $result['orderId']);
                }
                if (!empty($result['algoId'])) {
                    $this->line('  algoId: ' . $result['algoId']);
                }
            }
            usleep(500_000);
        }

        $this->newLine();
        $this->info('Тест завершён: все шаги выполнены. Проверьте позицию и условные ордера в Binance.');
        return 0;
    }
}
