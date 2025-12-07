<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuffaloWagerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wager_code',
        'bet_amount',
        'prize_amount',
        'net_amount',
        'before_balance',
        'after_balance',
        'player_agent_id',
        'player_agent_name',
        'request_time',
        'meta',
    ];

    protected $casts = [
        'bet_amount' => 'decimal:2',
        'prize_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'before_balance' => 'decimal:2',
        'after_balance' => 'decimal:2',
        'meta' => 'array',
        
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

