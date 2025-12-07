<?php

namespace App\Http\Controllers\Admin\PoneWine;

use App\Http\Controllers\Controller;
use App\Models\PoneWineTransaction;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoneWineReportController extends Controller
{
    /**
     * Show the PoneWine game report grouped by agent
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PoneWineTransaction::forUser($user);

        // Filter out direct wins (bet_number = win_number) to show only complex game logic
        $query->whereColumn('bet_number', '!=', 'win_number');

        // Apply filters
        $query = $this->applyFilters($query, $request);

        // Group by agent and get summary data
        $agentReports = $query->select([
            'player_agent_id',
            'player_agent_name',
            DB::raw('COUNT(DISTINCT match_id) as total_games'),
            DB::raw('COUNT(DISTINCT user_id) as total_players'),
            DB::raw('COUNT(*) as total_bets'),
            DB::raw('SUM(bet_amount) as total_bet_amount'),
            DB::raw('SUM(CASE WHEN result = \'Win\' THEN win_lose_amount ELSE 0 END) as total_wins'),
            DB::raw('SUM(CASE WHEN result = \'Lose\' THEN ABS(win_lose_amount) ELSE 0 END) as total_losses'),
            DB::raw('SUM(win_lose_amount) as net_result'),
            DB::raw('MAX(created_at) as last_game_date'),
        ])
        ->groupBy('player_agent_id', 'player_agent_name')
        ->orderByDesc('total_bet_amount')
        ->paginate(20);

        // Calculate overall totals
        $totals = $this->calculateOverallTotals($user, $request);

        return view('admin.ponewine.report.index', compact('agentReports', 'totals'));
    }

    /**
     * Show detailed transactions for a specific agent
     */
    public function agentDetail(Request $request, $agentId)
    {
        $user = Auth::user();
        $agent = User::findOrFail($agentId);

        // Check if user has permission to view this agent's data
        $this->checkAgentAccess($user, $agent);

        $query = PoneWineTransaction::forUser($user)
            ->where('player_agent_id', $agentId);

        // Filter out direct wins (bet_number = win_number) to show only complex game logic
        $query->whereColumn('bet_number', '!=', 'win_number');

        // Apply filters
        $query = $this->applyFilters($query, $request);

        $transactions = $query->orderByDesc('created_at')->paginate(20);

        // Calculate agent totals
        $agentTotals = $this->calculateAgentTotals($agentId, $request);

        return view('admin.ponewine.report.agent_detail', compact('transactions', 'agent', 'agentTotals'));
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, Request $request)
    {
        // Apply date filter if provided
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->dateRange($request->input('date_from'), $request->input('date_to'));
        }

        // Apply player filter if provided
        if ($request->filled('player_name')) {
            $query->byPlayer($request->input('player_name'));
        }

        // Apply room filter if provided
        if ($request->filled('room_id')) {
            $query->byRoom($request->input('room_id'));
        }

        return $query;
    }

    /**
     * Calculate overall totals for the filtered data
     */
    private function calculateOverallTotals($user, Request $request)
    {
        $query = PoneWineTransaction::forUser($user);

        // Filter out direct wins (bet_number = win_number) to show only complex game logic
        $query->whereColumn('bet_number', '!=', 'win_number');

        $query = $this->applyFilters($query, $request);

        $totals = $query->select([
            DB::raw('COUNT(DISTINCT match_id) as total_games'),
            DB::raw('COUNT(DISTINCT user_id) as total_players'),
            DB::raw('COUNT(DISTINCT player_agent_id) as total_agents'),
            DB::raw('COUNT(*) as total_bets'),
            DB::raw('SUM(bet_amount) as total_bet_amount'),
            DB::raw('SUM(CASE WHEN result = \'Win\' THEN win_lose_amount ELSE 0 END) as total_wins'),
            DB::raw('SUM(CASE WHEN result = \'Lose\' THEN ABS(win_lose_amount) ELSE 0 END) as total_losses'),
            DB::raw('SUM(win_lose_amount) as net_result')
        ])->first();

        return $totals;
    }

    /**
     * Calculate totals for a specific agent
     */
    private function calculateAgentTotals($agentId, Request $request)
    {
        $query = PoneWineTransaction::where('player_agent_id', $agentId);

        // Filter out direct wins (bet_number = win_number) to show only complex game logic
        $query->whereColumn('bet_number', '!=', 'win_number');

        $query = $this->applyFilters($query, $request);

        $totals = $query->select([
            DB::raw('COUNT(DISTINCT match_id) as total_games'),
            DB::raw('COUNT(DISTINCT user_id) as total_players'),
            DB::raw('COUNT(*) as total_bets'),
            DB::raw('SUM(bet_amount) as total_bet_amount'),
            DB::raw('SUM(CASE WHEN result = \'Win\' THEN win_lose_amount ELSE 0 END) as total_wins'),
            DB::raw('SUM(CASE WHEN result = \'Lose\' THEN ABS(win_lose_amount) ELSE 0 END) as total_losses'),
            DB::raw('SUM(win_lose_amount) as net_result')
        ])->first();

        return $totals;
    }

    /**
     * Show detailed report for a specific player
     */
    public function playerDetail(Request $request, $playerId)
    {
        $user = Auth::user();
        $player = User::findOrFail($playerId);

        // Check if user has permission to view this player's data
        $this->checkPlayerAccess($user, $player);

        $baseQuery = PoneWineTransaction::where('user_id', $playerId);

        // Filter out direct wins (bet_number = win_number) to show only complex game logic
        $baseQuery->whereColumn('bet_number', '!=', 'win_number');
        $baseQuery = $this->applyFilters($baseQuery, $request);

        // Get paginated results
        $playerReports = $baseQuery->orderByDesc('created_at')->paginate(20);

        // Calculate player totals using a separate query (without orderBy)
        $playerTotals = PoneWineTransaction::where('user_id', $playerId)
            ->whereColumn('bet_number', '!=', 'win_number');
        $playerTotals = $this->applyFilters($playerTotals, $request);
        $playerTotals = $playerTotals->select([
            DB::raw('COUNT(DISTINCT match_id) as total_games'),
            DB::raw('COUNT(*) as total_bets'),
            DB::raw('SUM(bet_amount) as total_bet_amount'),
            DB::raw('SUM(CASE WHEN result = \'Win\' THEN win_lose_amount ELSE 0 END) as total_wins'),
            DB::raw('SUM(CASE WHEN result = \'Lose\' THEN ABS(win_lose_amount) ELSE 0 END) as total_losses'),
            DB::raw('SUM(win_lose_amount) as net_result')
        ])->first();

        return view('admin.ponewine.report.player_detail', compact('playerReports', 'player', 'playerTotals'));
    }

    /**
     * Check if user has access to view a specific player's data
     */
    private function checkPlayerAccess($user, $player)
    {
        switch ($user->type) {
            case UserType::Owner->value:
                // Owner can see all
                return true;

            case UserType::Agent->value:
                // Agent can see only their direct players
                $playerIds = User::where('agent_id', $user->id)
                    ->where('type', UserType::Player->value)
                    ->pluck('id');
                if (!$playerIds->contains($player->id)) {
                    abort(403, 'Unauthorized access to player data');
                }
                return true;

            case UserType::Player->value:
                // Player can see only their own data
                if ($user->id !== $player->id) {
                    abort(403, 'Unauthorized access to player data');
                }
                return true;

            default:
                abort(403, 'Unauthorized access');
        }
    }

    /**
     * Check if user has access to view a specific agent's data
     */
    private function checkAgentAccess($user, $agent)
    {
        switch ($user->type) {
            case UserType::Owner->value:
                // Owner can see all
                return true;

            case UserType::Agent->value:
                // Agent can see only their own data
                if ($user->id !== $agent->id) {
                    abort(403, 'Unauthorized access to agent data');
                }
                return true;

            default:
                abort(403, 'Unauthorized access');
        }
    }

    /**
     * Export report to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        $query = PoneWineTransaction::forUser($user);

        // Filter out direct wins (bet_number = win_number) to show only complex game logic
        $query->whereColumn('bet_number', '!=', 'win_number');

        $query = $this->applyFilters($query, $request);

        $reports = $query->orderByDesc('created_at')->get();

        $filename = 'ponewine_report_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($reports) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'No',
                'Room ID',
                'Match ID',
                'Win Number',
                'Player Name',
                'Agent Name',
                'Bet Number',
                'Bet Amount',
                'Win/Lose Amount',
                'Result',
                'Balance Before',
                'Balance After',
                'Game Date'
            ]);

            $counter = 1;
            foreach ($reports as $report) {
                fputcsv($file, [
                    $counter++,
                    $report->room_id,
                    $report->match_id,
                    $report->win_number,
                    $report->user_name,
                    $report->player_agent_name,
                    $report->bet_number,
                    $report->bet_amount,
                    $report->win_lose_amount,
                    $report->result,
                    $report->player_balance_before,
                    $report->player_balance_after,
                    $report->created_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
