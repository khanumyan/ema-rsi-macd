<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'profile_name',
        'profile_token',
        'profile_secret',
        'is_active',
        'category',
    ];

    protected $hidden = [
        'profile_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const CATEGORY_PROD = 'PROD';
    public const CATEGORY_TEST = 'TEST';

    public static function categories(): array
    {
        return [self::CATEGORY_PROD, self::CATEGORY_TEST];
    }

    /** Есть ли сохранённый API Secret (для открытия позиций на Binance Futures). */
    public function hasApiSecret(): bool
    {
        return !empty($this->profile_secret);
    }
}
