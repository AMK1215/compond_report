<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuffaloProviderSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'rtp',
        'is_under_maintenance',
        'maintenance_reason',
        'rtp_request_time',
        'maintenance_request_time',
        'total_bet_amount',
        'total_prize_amount',
        'total_profit',
        'spin_count',
    ];

    protected $casts = [
        'rtp' => 'decimal:2',
        'is_under_maintenance' => 'boolean',
        'total_bet_amount' => 'decimal:2',
        'total_prize_amount' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'spin_count' => 'integer',
    ];

    public static function getSingleton(): self
    {
        return static::query()->firstOrCreate([], [
            'rtp' => 0.00,
            'is_under_maintenance' => false,
        ]);
    }

    public static function getSingletonForUpdate(): self
    {
        $query = static::query()->lockForUpdate();

        if ($existing = $query->first()) {
            return $existing;
        }

        return static::create([
            'rtp' => 0.00,
            'is_under_maintenance' => false,
        ]);
    }
}

