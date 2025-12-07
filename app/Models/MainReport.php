<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainReport extends Model
{
    use HasFactory;

    protected $fillable = [
        // Batch-level data
        'member_account',
        'player_id',
        'player_agent_id',
        'product_code',
        'provider_name',
        'game_type',
        'operator_code',
        'request_time',
        'sign',
        'currency',
        // Transaction-level data
        'transaction_id',
        'action',
        'amount',
        'valid_bet_amount',
        'bet_amount',
        'prize_amount',
        'tip_amount',
        'wager_code',
        'wager_status',
        'round_id',
        'payload',
        'settle_at',
        'game_code',
        'game_name',
        'channel_code',
        'status',
        'before_balance',
        'balance',
        'game_type_id',
        'players',
        'banker_balance',
        'timestamp',
        'total_player_net',
        'banker_amount_change',
        'agent_name',
        // Game Information
        'room_id',
        'match_id',
        'win_number',
        // Player Information
        'user_id',
        // Bet Information
        'bet_number',
        // Result Information
        'win_lose_amount',
        'result',
        // Provider Data
        'provider_bet_id',
        'provider_player_bet_id',
        'provider_bet_info_id',
        // Transaction Status
        'is_processed',
        'processed_at',
        // Metadata
        'meta',
        'notes',
        'report_game_type',
    ];

    protected $casts = [
        'request_time' => 'datetime',
        'settle_at' => 'datetime',
        'timestamp' => 'datetime',
        'processed_at' => 'datetime',
        'amount' => 'decimal:4',
        'valid_bet_amount' => 'decimal:4',
        'bet_amount' => 'decimal:2',
        'prize_amount' => 'decimal:4',
        'tip_amount' => 'decimal:4',
        'before_balance' => 'decimal:4',
        'balance' => 'decimal:4',
        'banker_balance' => 'decimal:2',
        'total_player_net' => 'decimal:2',
        'banker_amount_change' => 'decimal:2',
        'win_lose_amount' => 'decimal:2',
        'report_game_type' => 'string',
        'payload' => 'array',
        'players' => 'array',
        'meta' => 'array',
        'is_processed' => 'boolean',
        'product_code' => 'integer',
        'player_id' => 'integer',
        'player_agent_id' => 'integer',
        'game_type_id' => 'integer',
        'room_id' => 'integer',
        'win_number' => 'integer',
        'user_id' => 'integer',
        'bet_number' => 'integer',
        'provider_bet_id' => 'integer',
        'provider_player_bet_id' => 'integer',
        'provider_bet_info_id' => 'integer',
    ];

    /**
     * Get the user that owns the report.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the agent (player_agent_id) that owns the report.
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'player_agent_id');
    }

    /**
     * Get the game type that owns the report.
     */
    public function gameType()
    {
        return $this->belongsTo(GameType::class, 'game_type_id');
    }
}
