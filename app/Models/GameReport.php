<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_id',
        'provider_name',
        'game_type',
        'wager_code',
        'bet_amount',
        'prize_amount',
        'net_amount',
        'before_balance',
        'after_balance'
    ];

    public function scopeFilter($query,$filter) {
        $query->when($filter['search']??false,function($query,$search) {
            $query->where(function($query) use($search) {
                $query->where('u.name', 'LIKE', "%{$search}%")
                    ->orWhere('a.name', 'LIKE', "%{$search}%");
            });
        });

        $query->when($filter['game_type']??false,function($query,$gameType) {
            $query->where(function($query) use($gameType) {
                $query->where('game_reports.game_type', $gameType);
            });
        });
    }
}
