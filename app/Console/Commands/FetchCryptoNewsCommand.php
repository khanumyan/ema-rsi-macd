<?php

namespace App\Console\Commands;

use App\Models\CryptoNews;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchCryptoNewsCommand extends Command
{
    protected $signature = 'crypto:fetch-news
                            {--delay= : Задержка в секундах между отправками в Telegram (по умолчанию из config, 300 = 5 мин)}';
    protected $description = 'Загрузка крипто-новостей из newsdata.io и отправка в Telegram (пауза 5 мин между отправками)';

    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle(): int
    {
        $this->info('🔄 Загрузка крипто-новостей...');

        $apiKey = config('crypto_news.api_key');
        if (empty($apiKey)) {
            $this->error('❌ Не задан CRYPTO_NEWS_API_KEY в .env или config/crypto_news.php');
            Log::error('Crypto news: missing API key');
            return Command::FAILURE;
        }

        $delaySeconds = (int) ($this->option('delay') ?? config('crypto_news.telegram.delay_between_sends_seconds', 300));
        if ($delaySeconds < 0) {
            $delaySeconds = 300;
        }
        $this->info("⏱ Задержка между отправками в Telegram: {$delaySeconds} сек (" . round($delaySeconds / 60, 1) . " мин)");

        try {
            $url = config('crypto_news.api_url', 'https://newsdata.io/api/1/crypto');
            $language = config('crypto_news.language', 'ru');
            $timeout = config('crypto_news.timeout', 30);
            $query = [
                'apikey' => $apiKey,
                'language' => $language,
            ];
            $response = Http::timeout($timeout)->get($url, $query);

            if (!$response->successful()) {
                $this->error('❌ Ошибка API: HTTP ' . $response->status());
                Log::error('Crypto news API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return Command::FAILURE;
            }

            $data = $response->json();
            $results = $data['results'] ?? null;
            if (!is_array($results)) {
                $this->warn('⚠️ В ответе API нет массива results');
                return Command::SUCCESS;
            }

            $newArticles = 0;
            $sentToTelegram = 0;
            $skippedBlacklisted = 0;
            $blacklistedSources = config('crypto_news.blacklisted_sources', []);

            foreach ($results as $article) {
                $link = $article['link'] ?? '';
                $articleId = $article['article_id'] ?? null;
                if (empty($articleId) && !empty($link)) {
                    $articleId = 'link_' . md5($link);
                }
                if (empty($articleId)) {
                    continue;
                }

                if (CryptoNews::where('article_id', $articleId)->exists()) {
                    continue;
                }

                $pubDate = now();
                if (!empty($article['pubDate'])) {
                    try {
                        $pubDate = Carbon::parse($article['pubDate']);
                    } catch (\Throwable $e) {
                        // leave now()
                    }
                }

                $sourceName = $article['source_name'] ?? $article['source_id'] ?? null;

                $cryptoNews = CryptoNews::create([
                    'article_id' => $articleId,
                    'title' => $article['title'] ?? '',
                    'description' => $article['description'] ?? null,
                    'link' => $link,
                    'pub_date' => $pubDate,
                    'creator' => $article['creator'] ?? null,
                    'coin' => $article['coin'] ?? null,
                    'image_url' => $article['image_url'] ?? null,
                    'source_name' => $sourceName,
                    'source_id' => $article['source_id'] ?? null,
                    'keywords' => $article['keywords'] ?? null,
                    'content' => $article['content'] ?? null,
                    'language' => $article['language'] ?? null,
                    'sent_to_telegram' => false,
                ]);
                $newArticles++;

                $isBlacklisted = false;
                if ($sourceName && !empty($blacklistedSources)) {
                    $sourceNameTrim = trim($sourceName);
                    foreach ($blacklistedSources as $blacklisted) {
                        if (strcasecmp($sourceNameTrim, trim($blacklisted)) === 0) {
                            $isBlacklisted = true;
                            $skippedBlacklisted++;
                            break;
                        }
                    }
                }

                if (!$isBlacklisted && $this->telegramService->sendCryptoNews($cryptoNews)) {
                    $cryptoNews->update(['sent_to_telegram' => true]);
                    $sentToTelegram++;
                    if ($delaySeconds > 0) {
                        $this->line("   ⏳ пауза {$delaySeconds} сек перед следующей отправкой...");
                        sleep($delaySeconds);
                    }
                }
            }

            $this->info("✅ Новых статей: {$newArticles}, отправлено в Telegram: {$sentToTelegram}" .
                ($skippedBlacklisted > 0 ? ", пропущено (чёрный список): {$skippedBlacklisted}" : ""));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Ошибка: ' . $e->getMessage());
            Log::error('Crypto news fetch error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
