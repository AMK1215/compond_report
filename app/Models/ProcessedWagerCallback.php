<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ProcessedWagerCallback extends Model
{
    use HasFactory;
    protected $fillable = [
        'wager_code', 
        'game_type_id', 
        'agent_id', 
        'players', 
        'member_account', 
        'agent_name',
        'banker_balance', 
        'timestamp', 
        'total_player_net', 
        'banker_amount_change'
    ];
    protected $casts = [
        'players' => 'array', // 👈 This tells Laravel to automatically JSON encode/decode
    ];
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
