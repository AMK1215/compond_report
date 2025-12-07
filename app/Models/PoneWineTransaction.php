<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoneWineTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'match_id',
        'win_number',
        'player_id',
        'user_id',
        'user_name',
        'client_agent_name',
        'client_agent_id',
        'bet_number',
        'bet_amount',
        'win_lose_amount',
        'player_balance_before',
        'player_balance_after',
        'result',
        'provider_bet_id',
        'provider_player_bet_id',
        'provider_bet_info_id',
        'is_processed',
        'processed_at',
        'provider_payload',
        'notes',
        'player_agent_id',
        'player_agent_name',
    ];

    protected $casts = [
        'bet_amount' => 'decimal:2',
        'win_lose_amount' => 'decimal:2',
        'player_balance_before' => 'decimal:2',
        'player_balance_after' => 'decimal:2',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
        'provider_payload' => 'array',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Store a complete PoneWine transaction from provider payload
     */
        public static function storeFromProviderPayload(array $gameData, array $playerData, array $betInfo, User $user, float $balanceBefore, float $balanceAfter, $player_agent_id, $player_agent_name): self
    {
        return self::create([
            'room_id' => $gameData['roomId'],
            'match_id' => $gameData['matchId'],
            'win_number' => $gameData['winNumber'],
            'player_id' => $playerData['player_id'],
            'user_id' => $user->id,
            'user_name' => $user->user_name,
            'client_agent_name' => $playerData['client_agent_name'] ?? null,
            'client_agent_id' => $playerData['client_agent_id'] ?? null,
            'bet_number' => $betInfo['betNumber'],
            'bet_amount' => $betInfo['betAmount'],
            'win_lose_amount' => $playerData['winLoseAmount'],
            'player_balance_before' => $balanceBefore,
            'player_balance_after' => $balanceAfter,
            'result' => self::determineResult($playerData['winLoseAmount']),
            'provider_bet_id' => $playerData['pone_wine_player_bet']['pone_wine_bet_id'] ?? null,
            'provider_player_bet_id' => $playerData['pone_wine_player_bet']['id'] ?? null,
            'provider_bet_info_id' => $betInfo['id'] ?? null,
            'is_processed' => true,
            'processed_at' => now(),
            'provider_payload' => [
                'game_data' => $gameData,
                'player_data' => $playerData,
                'bet_info' => $betInfo,
            ],
            'player_agent_id' => $player_agent_id,
            'player_agent_name' => $player_agent_name,
        ]);
    }

    /**
     * Determine the result based on win/lose amount
     */
    private static function determineResult(float $winLoseAmount): string
    {
        if ($winLoseAmount > 0) {
            return 'Win';
        } elseif ($winLoseAmount < 0) {
            return 'Lose';
        } else {
            return 'Draw';
        }
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $dateFrom = null, $dateTo = null)
    {
        if ($dateFrom && $dateTo) {
            return $query->whereBetween('created_at', [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);
        }
        return $query;
    }

    /**
     * Scope for filtering by player
     */
    public function scopeByPlayer($query, $playerName = null)
    {
        if ($playerName) {
            return $query->where('player_id', 'like', '%' . $playerName . '%');
        }
        return $query;
    }

    /**
     * Scope for filtering by room
     */
    public function scopeByRoom($query, $roomId = null)
    {
        if ($roomId) {
            return $query->where('room_id', $roomId);
        }
        return $query;
    }

    /**
     * Scope for role-based access
     */
    public function scopeForUser($query, User $user)
    {
        switch ($user->type) {
            case \App\Enums\UserType::Owner->value:
                // Owner can see all
                return $query;

            case \App\Enums\UserType::Agent->value:
                // Agent can see all their descendant players (including multi-level)
                $playerIds = $user->getAllDescendantPlayers()->pluck('id');
                return $query->whereIn('user_id', $playerIds);

            case \App\Enums\UserType::Player->value:
                // Player can see only their own data
                return $query->where('user_id', $user->id);

            default:
                // No data for unknown user types
                return $query->whereRaw('1 = 0');
        }
    }
}