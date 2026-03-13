<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Статический админ-пользователь для входа в систему
        // Email и пароль заданы жестко и могут быть изменены при необходимости.
        // Пароль будет автоматически захеширован благодаря cast'у в модели User.
        User::updateOrCreate(
            [
                'email' => 'admin@ema-rsi-macd.local',
            ],
            [
                'name' => 'Admin',
                'password' => 'RsiMacd!2026',
            ]
        );
    }
}
