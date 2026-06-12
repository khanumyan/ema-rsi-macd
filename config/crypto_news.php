<?php

return [
    'api_key' => env('CRYPTO_NEWS_API_KEY', 'pub_982554f4728846ee996752dadf730ff9'),
    'api_url' => 'https://newsdata.io/api/1/crypto',
    'language' => env('CRYPTO_NEWS_LANGUAGE', 'ru'),
    'timeout' => (int) env('CRYPTO_NEWS_TIMEOUT', 30),

    'telegram' => [
        'delay_between_sends_seconds' => (int) env('CRYPTO_NEWS_TELEGRAM_DELAY_SECONDS', 300), // 5 минут
    ],

    /*
    |--------------------------------------------------------------------------
    | Blacklisted News Sources
    |--------------------------------------------------------------------------
    |
    | Список источников новостей, которые нужно фильтровать (не отправлять в Telegram).
    | Если source_name новости совпадает с одним из этих значений (без учёта регистра), новость не будет отправлена.
    |
    */
    'blacklisted_sources' => [
        'Watcher Guru News',
        'Coin Gabbar',
        'Tronweekly',
        'Analytics And Insight',
        'Coincu News',
        'CryptoFrontNews',
        'BlockchainReporter',
        'SFCToday',
        'Bitcoinworld.co.in',
        'The Crypto Basic',
        'The Coin Republic',
        'Times Tabloid',
        'Cryptonewsland',
        'Cryptonewsbytes',
        'Coinfomania',
        'Optimisus',
        'Crypto Intelligence News',
        'Thebittimes',
        'Techannouncer',
        'News4social',
        'Visionary Financial',
        'Crypto Boom',
        'Chainbits',
        'Ccnnewscrypto',
        'Blockhead Co',
        'Icodesk Io',
        'Crypto Ro',
        'Fxcryptonews',
        'Crypto Press',
        'Newsdata',
        'Alexablockchain',
        'Deythere',
        'Blockzeit',
        'The Crypto News Wire',
        'Egamers.io',
        'Blockchaingamer Biz',
        'Namecoinnews',
        'Technext24',
        'Unlock-bc',
        'Cointrust',
    ],
];
