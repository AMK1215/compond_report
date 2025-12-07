<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\LogBuffaloBet;
use App\Models\PlaceBet;
use App\Models\PoneWineTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CombinedGameReportController extends Controller
{
    /**
     * Display combined game report (PlaceBet + Buffalo + PoneWine)
     */
    public function index(Request $request)
    {
        $agent = Auth::user();
        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        // Get aggregated data from all three sources
        $report = $this->buildCombinedQuery($request, $agent, $startDate, $endDate);

        // Calculate totals
        $totalBet = $report->sum('total_bet');
        $totalWin = $report->sum('total_win');
        $totalLose = $report->sum('total_lose');
        $netWinLoss = $totalWin - $totalBet;

        $total = [
            'totalBet' => $totalBet,
            'totalWin' => $totalWin,
            'totalLose' => $totalLose,
            'netWinLoss' => $netWinLoss,
        ];

        // Get individual data sources for debugging and separate display
        $playerIds = $this->getPlayerIds($agent);
        $placeBetData = $this->getPlaceBetData($playerIds, $startDate, $endDate, $agent->type);
        $buffaloBetData = $this->getBuffaloBetData($playerIds, $startDate, $endDate, $agent->type);
        $poneWineData = $this->getPoneWineData($playerIds, $startDate, $endDate, $agent->type);

        // Get debug info for each source
        $debugInfo = [
            'place_bet_count' => $placeBetData->count(),
            'buffalo_bet_count' => $buffaloBetData->count(),
            'pone_wine_count' => $poneWineData->count(),
            'player_ids_count' => $playerIds->count(),
            'player_ids_sample' => $playerIds->take(5)->toArray(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'user_type' => $agent->type,
            'user_id' => $agent->id,
        ];

        // Additional debugging: Check raw data counts
        $placeBetRawQuery = PlaceBet::where('wager_status', 'SETTLED')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        if ($agent->type !== UserType::Owner->value && !$playerIds->isEmpty()) {
            $placeBetRawQuery->whereIn('player_id', $playerIds);
        } elseif ($agent->type === UserType::Owner->value) {
            $placeBetRawQuery->whereNotNull('player_agent_id');
        }
        $debugInfo['place_bet_raw_count'] = $placeBetRawQuery->count();
        
        $buffaloBetRawQuery = LogBuffaloBet::where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        if ($agent->type !== UserType::Owner->value && !$playerIds->isEmpty()) {
            $buffaloBetRawQuery->whereIn('player_id', $playerIds);
        }
        $debugInfo['buffalo_bet_raw_count'] = $buffaloBetRawQuery->count();
        
        $poneWineRawQuery = PoneWineTransaction::where('is_processed', true)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        if ($agent->type !== UserType::Owner->value && !$playerIds->isEmpty()) {
            $poneWineRawQuery->whereIn('user_id', $playerIds);
        }
        $debugInfo['pone_wine_raw_count'] = $poneWineRawQuery->count();

        return view('admin.report.combined_game_report', compact(
            'report', 
            'total', 
            'startDate', 
            'endDate', 
            'debugInfo',
            'placeBetData',
            'buffaloBetData',
            'poneWineData',
            'agent'
        ));
    }

    /**
     * Build combined query from PlaceBet, LogBuffaloBet, and PoneWineTransaction
     */
    private function buildCombinedQuery(Request $request, $agent, $startDate, $endDate)
    {
        // Get player IDs based on user type
        $playerIds = $this->getPlayerIds($agent);

        // 1. Get PlaceBet data (SETTLED wagers)
        $placeBetData = $this->getPlaceBetData($playerIds, $startDate, $endDate, $agent->type);

        // 2. Get LogBuffaloBet data (completed status)
        $buffaloBetData = $this->getBuffaloBetData($playerIds, $startDate, $endDate, $agent->type);

        // 3. Get PoneWineTransaction data (is_processed = true)
        $poneWineData = $this->getPoneWineData($playerIds, $startDate, $endDate, $agent->type);

        // For Owner: Combine data by agent_id (group by agent)
        // For Agent/Player: Combine data by member_account (group by player)
        if ($agent->type === UserType::Owner->value) {
            $combinedData = $this->combineDataByAgent($placeBetData, $buffaloBetData, $poneWineData);
        } else {
            $combinedData = $this->combineDataByMember($placeBetData, $buffaloBetData, $poneWineData);
        }

        return $combinedData->values();
    }

    /**
     * Get player IDs based on agent type
     */
    private function getPlayerIds($agent)
    {
        if ($agent->type === UserType::Owner->value) {
            // Owner sees all players
            return User::where('type', UserType::Player->value)->pluck('id');
        } elseif ($agent->type === UserType::Agent->value) {
            // Agent sees all descendant players
            return $agent->getAllDescendantPlayers()->pluck('id');
        } elseif ($agent->type === UserType::Player->value) {
            // Player sees only themselves
            return collect([$agent->id]);
        }

        return collect([]);
    }

    /**
     * Get PlaceBet aggregated data
     */
    private function getPlaceBetData($playerIds, $startDate, $endDate, $userType = null)
    {
        // Subquery for latest SETTLED per member_account, round_id (to avoid duplicates)
        $latestSettledQuery = PlaceBet::select(DB::raw('MAX(id) as id'))
            ->where('wager_status', 'SETTLED')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        // For Owner, don't filter by player_id (show all)
        // For Agent/Player, filter by player_ids
        if ($userType !== UserType::Owner->value && !$playerIds->isEmpty()) {
            $latestSettledQuery->whereIn('player_id', $playerIds);
        } elseif ($userType === UserType::Owner->value) {
            // Owner sees all, but only where agent_id is not null (like ReportController)
            $latestSettledQuery->whereNotNull('player_agent_id');
        }

        $latestSettledIds = $latestSettledQuery
            ->groupBy('member_account', 'round_id')
            ->pluck('id');

        // If no settled IDs found, return empty collection
        if ($latestSettledIds->isEmpty()) {
            return collect([]);
        }

        return PlaceBet::whereIn('place_bets.id', $latestSettledIds)
            ->leftJoin('users as agent_user', 'place_bets.player_agent_id', '=', 'agent_user.id')
            ->select(
                'place_bets.member_account',
                'place_bets.player_id',
                'place_bets.player_agent_id',
                'agent_user.user_name as agent_name',
                DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN COALESCE(place_bets.bet_amount, place_bets.amount, 0) * 1000 ELSE COALESCE(place_bets.bet_amount, place_bets.amount, 0) END) as total_bet"),
                DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN COALESCE(place_bets.prize_amount, 0) * 1000 ELSE COALESCE(place_bets.prize_amount, 0) END) as total_win"),
                DB::raw("SUM(
                    CASE 
                        WHEN place_bets.currency = 'MMK2' THEN 
                            CASE 
                                WHEN COALESCE(place_bets.prize_amount, 0) * 1000 < COALESCE(place_bets.bet_amount, place_bets.amount, 0) * 1000 
                                THEN (COALESCE(place_bets.bet_amount, place_bets.amount, 0) * 1000) - (COALESCE(place_bets.prize_amount, 0) * 1000) 
                                ELSE 0 
                            END
                        ELSE 
                            CASE 
                                WHEN COALESCE(place_bets.prize_amount, 0) < COALESCE(place_bets.bet_amount, place_bets.amount, 0) 
                                THEN COALESCE(place_bets.bet_amount, place_bets.amount, 0) - COALESCE(place_bets.prize_amount, 0) 
                                ELSE 0 
                            END
                    END
                ) as total_lose")
            )
            ->groupBy('place_bets.member_account', 'place_bets.player_id', 'place_bets.player_agent_id', 'agent_user.user_name')
            ->get();
    }

    /**
     * Get LogBuffaloBet aggregated data
     */
    private function getBuffaloBetData($playerIds, $startDate, $endDate, $userType = null)
    {
        $query = LogBuffaloBet::where('log_buffalo_bets.status', 'completed')
            ->whereBetween('log_buffalo_bets.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        // For Owner, don't filter by player_id (show all)
        // For Agent/Player, filter by player_ids
        if ($userType !== UserType::Owner->value && !$playerIds->isEmpty()) {
            $query->whereIn('log_buffalo_bets.player_id', $playerIds);
        }

        return $query->leftJoin('users as agent_user', 'log_buffalo_bets.player_agent_id', '=', 'agent_user.id')
            ->select(
                'log_buffalo_bets.member_account',
                'log_buffalo_bets.player_id',
                'log_buffalo_bets.player_agent_id',
                'agent_user.user_name as agent_name',
                DB::raw('SUM(log_buffalo_bets.bet_amount) as total_bet'),
                DB::raw('SUM(log_buffalo_bets.win_amount) as total_win'),
                DB::raw('SUM(CASE WHEN log_buffalo_bets.win_amount < log_buffalo_bets.bet_amount THEN log_buffalo_bets.bet_amount - log_buffalo_bets.win_amount ELSE 0 END) as total_lose')
            )
            ->groupBy('log_buffalo_bets.member_account', 'log_buffalo_bets.player_id', 'log_buffalo_bets.player_agent_id', 'agent_user.user_name')
            ->get();
    }

    /**
     * Get PoneWineTransaction aggregated data
     * Based on PoneWineReportController calculation logic
     */
    private function getPoneWineData($playerIds, $startDate, $endDate, $userType = null)
    {
        $query = PoneWineTransaction::where('pone_wine_transactions.is_processed', true)
            ->whereBetween('pone_wine_transactions.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        // For Owner, don't filter by user_id (show all)
        // For Agent/Player, filter by player_ids
        if ($userType !== UserType::Owner->value && !$playerIds->isEmpty()) {
            $query->whereIn('pone_wine_transactions.user_id', $playerIds);
        }

        return $query->leftJoin('users as agent_user', 'pone_wine_transactions.player_agent_id', '=', 'agent_user.id')
            ->select(
                'pone_wine_transactions.user_name as member_account',
                'pone_wine_transactions.user_id as player_id',
                'pone_wine_transactions.player_agent_id',
                'agent_user.user_name as agent_name',
                DB::raw('SUM(pone_wine_transactions.bet_amount) as total_bet'),
                DB::raw('SUM(CASE WHEN pone_wine_transactions.result = \'Win\' THEN pone_wine_transactions.win_lose_amount ELSE 0 END) as total_win'),
                DB::raw('SUM(CASE WHEN pone_wine_transactions.result = \'Lose\' THEN ABS(pone_wine_transactions.win_lose_amount) ELSE 0 END) as total_lose')
            )
            ->groupBy('pone_wine_transactions.user_name', 'pone_wine_transactions.user_id', 'pone_wine_transactions.player_agent_id', 'agent_user.user_name')
            ->get();
    }

    /**
     * Combine data from all three sources by agent_id (for Owner)
     */
    private function combineDataByAgent($placeBetData, $buffaloBetData, $poneWineData)
    {
        $combined = [];

        // Helper function to get or create agent entry
        $getOrCreateAgent = function ($playerAgentId) use (&$combined) {
            if (!$playerAgentId) {
                return null;
            }
            
            if (!isset($combined[$playerAgentId])) {
                $combined[$playerAgentId] = [
                    'agent_id' => $playerAgentId,
                    'agent_name' => $this->getAgentName($playerAgentId),
                    'total_bet' => 0,
                    'total_win' => 0,
                    'total_lose' => 0,
                ];
            }
            return $combined[$playerAgentId];
        };

        // Process PlaceBet data
        foreach ($placeBetData as $row) {
            if ($agent = $getOrCreateAgent($row->player_agent_id)) {
                $agent['total_bet'] += (float) ($row->total_bet ?? 0);
                $agent['total_win'] += (float) ($row->total_win ?? 0);
                $agent['total_lose'] += (float) ($row->total_lose ?? 0);
            }
        }

        // Process BuffaloBet data
        foreach ($buffaloBetData as $row) {
            if ($agent = $getOrCreateAgent($row->player_agent_id)) {
                $agent['total_bet'] += (float) ($row->total_bet ?? 0);
                $agent['total_win'] += (float) ($row->total_win ?? 0);
                $agent['total_lose'] += (float) ($row->total_lose ?? 0);
            }
        }

        // Process PoneWine data
        foreach ($poneWineData as $row) {
            if ($agent = $getOrCreateAgent($row->player_agent_id)) {
                $agent['total_bet'] += (float) ($row->total_bet ?? 0);
                $agent['total_win'] += (float) ($row->total_win ?? 0);
                $agent['total_lose'] += (float) ($row->total_lose ?? 0);
            }
        }

        // Convert to collection of objects for easier access
        return collect($combined)->map(function ($item) {
            return (object) $item;
        });
    }

    /**
     * Combine data from all three sources by member_account (for Agent/Player)
     */
    private function combineDataByMember($placeBetData, $buffaloBetData, $poneWineData)
    {
        $combined = [];

        // Helper function to get or create member entry
        $getOrCreateMember = function ($memberAccount, $playerId, $playerAgentId) use (&$combined) {
            $key = $memberAccount . '_' . $playerId;
            if (!isset($combined[$key])) {
                $combined[$key] = [
                    'member_account' => $memberAccount,
                    'player_id' => $playerId,
                    'player_agent_id' => $playerAgentId,
                    'agent_name' => $this->getAgentName($playerAgentId),
                    'total_bet' => 0,
                    'total_win' => 0,
                    'total_lose' => 0,
                ];
            }
            return $combined[$key];
        };

        // Process PlaceBet data
        foreach ($placeBetData as $row) {
            $member = $getOrCreateMember($row->member_account, $row->player_id, $row->player_agent_id);
            $member['total_bet'] += (float) ($row->total_bet ?? 0);
            $member['total_win'] += (float) ($row->total_win ?? 0);
            $member['total_lose'] += (float) ($row->total_lose ?? 0);
        }

        // Process BuffaloBet data
        foreach ($buffaloBetData as $row) {
            $member = $getOrCreateMember($row->member_account, $row->player_id, $row->player_agent_id);
            $member['total_bet'] += (float) ($row->total_bet ?? 0);
            $member['total_win'] += (float) ($row->total_win ?? 0);
            $member['total_lose'] += (float) ($row->total_lose ?? 0);
        }

        // Process PoneWine data
        foreach ($poneWineData as $row) {
            $member = $getOrCreateMember($row->member_account, $row->player_id, $row->player_agent_id);
            $member['total_bet'] += (float) ($row->total_bet ?? 0);
            $member['total_win'] += (float) ($row->total_win ?? 0);
            $member['total_lose'] += (float) ($row->total_lose ?? 0);
        }

        // Convert to collection of objects for easier access
        return collect($combined)->map(function ($item) {
            return (object) $item;
        });
    }

    /**
     * Get agent name by ID
     */
    private function getAgentName($agentId)
    {
        if (!$agentId) {
            return null;
        }

        $agent = User::find($agentId);
        return $agent ? $agent->user_name : null;
    }
}

