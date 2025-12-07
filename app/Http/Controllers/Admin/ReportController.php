<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\UserType;
use App\Models\Product;
use App\Models\PlaceBet;
use Illuminate\Http\Request;
use App\Models\LogBuffaloBet;
use Illuminate\Support\Facades\DB;
use App\Models\PoneWineTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\ReportTransaction;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $agent = Auth::user();
        // $agent = $this->getAgent() ?? Auth::user();

        $report = $this->buildQuery($request, $agent);

        $totalstake = $report->sum('stake_count');
        $totalBetAmt = $report->sum('total_bet');
        $totalWinAmt = $report->sum('total_win');

        $total = [
            'totalstake' => $totalstake,
            'totalBetAmt' => $totalBetAmt,
            'totalWinAmt' => $totalWinAmt,
        ];

        return view('admin.report.index', compact('report', 'total'));
    }

    public function getReportDetails(Request $request, $member_account)
    {
        $player = User::where('user_name', $member_account)->first();
        if (! $player) {
            abort(404, 'Player not found');
        }

        $details = $this->getPlayerDetails($member_account, $request);
        $productTypes = Product::where('status', 1)->get();

        return view('admin.report.detail', compact('details', 'productTypes', 'member_account'));
    }

    private function getAgent()
    {
        $user = Auth::user();

        return $user;
    }

    private function buildQuery(Request $request, $agent)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        // Subquery for latest SETTLED per member_account, round_id
        $latestSettledIds = PlaceBet::select(DB::raw('MAX(id) as id'))
            ->where('wager_status', 'SETTLED')
            ->groupBy('member_account', 'round_id');

        $query = PlaceBet::query()
            ->select(
                'place_bets.member_account',
                'agent_user.user_name as agent_name',
                'agent_user.name as name',
                'player_user.name as player_name',
                DB::raw('COUNT(*) as stake_count'),
                DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN COALESCE(bet_amount, amount, 0) * 1000 ELSE COALESCE(bet_amount, amount, 0) END) as total_bet"),
                DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN prize_amount * 1000 ELSE prize_amount END) as total_win")
            )
            ->leftJoin('users as player_user','place_bets.player_id','=','player_user.id')
            ->leftJoin('users as agent_user', 'place_bets.player_agent_id', '=', 'agent_user.id')
            ->whereIn('place_bets.id', $latestSettledIds)
            ->whereBetween('place_bets.created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);

        // --- agent hierarchy filtering as before ---
        // (unchanged code)
        if ($agent->type == UserType::Owner->value) {
            $query->whereNotNull('place_bets.player_agent_id');
        } elseif ($agent->type == UserType::Agent->value) {
            // Get all players under this agent (including sub-agents' players)
            $playerIds = User::where('agent_id', $agent->id)
                ->where('type', UserType::Player->value)
                ->pluck('id');
            $query->whereIn('place_bets.player_id', $playerIds);
        } elseif ($agent->type == UserType::Player->value) {
            $query->where('place_bets.player_id', $agent->id);
        }

        if ($request->filled('member_account')) {
            $query->where('member_account', $request->member_account);
        }

        return $query->groupBy('place_bets.member_account', 'agent_user.user_name','player_user.name','agent_user.name')->get();
    }


    private function getPlayerDetails($member_account, $request)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        return PlaceBet::where('member_account', $member_account)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function show(Request $request, $member_account)
    {
        $query = PlaceBet::query()->where('member_account', $member_account);

        // Get the player user record
        $player = User::where('user_name', $member_account)->first();
        if (! $player) {
            abort(404, 'Player not found');
        }

        // $bets = $query->orderByDesc('created_at')->paginate(50);
        $sub = PlaceBet::selectRaw('MAX(id) as id')
            ->where('member_account', $member_account)
            ->where('wager_status', 'SETTLED')
            ->groupBy('round_id');

        $bets = PlaceBet::whereIn('id', $sub)->orderByDesc('created_at')->paginate(50);

        return view('admin.report.show', compact('bets', 'member_account'));
    }

    public function dailyWinLossReport(Request $request)
    {
        $agent = Auth::user();
        $playerIds = $agent->getAllDescendantPlayers()->pluck('id');

        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        // 1. Deduplicate: Get only the latest SETTLED per player/round_id for the day
        $ids = PlaceBet::select(DB::raw('MAX(id) as id'))
            ->whereIn('player_id', $playerIds)
            ->where('wager_status', 'SETTLED')
            ->whereDate('created_at', $date)
            ->groupBy('player_id', 'round_id')
            ->pluck('id');

        // 2. Aggregate on those unique records
        // $dailyReports = PlaceBet::whereIn('id', $ids)
        //     ->join('users', 'place_bets.player_id', '=', 'users.id')
        //     ->select(
        //         'users.user_name',
        //         'place_bets.player_id',
        //         DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.bet_amount * 1000 ELSE place_bets.bet_amount END) as total_turnover'),
        //         DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.prize_amount * 1000 ELSE place_bets.prize_amount END) as total_payout')
        //     )
        //     ->groupBy('users.user_name', 'place_bets.player_id')
        //     ->get();

        $dailyReports = PlaceBet::whereIn('place_bets.id', $ids)
            ->join('users', 'place_bets.player_id', '=', 'users.id')
            ->select(
                'users.user_name',
                'place_bets.player_id',
                DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.bet_amount * 1000 ELSE place_bets.bet_amount END) as total_turnover'),
                DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.prize_amount * 1000 ELSE place_bets.prize_amount END) as total_payout')
            )
            ->groupBy('users.user_name', 'place_bets.player_id')
            ->get();

        $totalTurnover = $dailyReports->sum('total_turnover');
        $totalPayout = $dailyReports->sum('total_payout');
        $totalWinLoss = $totalPayout - $totalTurnover;

        return view('admin.reports.daily_win_loss', compact('dailyReports', 'date', 'totalTurnover', 'totalPayout', 'totalWinLoss'));
    }

    // public function dailyWinLossReport(Request $request)
    // {
    //     $agent = Auth::user();
    //     $playerIds = $agent->getAllDescendantPlayers()->pluck('id');

    //     $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

    //     $dailyReports = PlaceBet::whereIn('player_id', $playerIds)
    //         ->where('place_bets.wager_status', 'SETTLED')
    //         ->whereDate('place_bets.created_at', $date)
    //         ->join('users', 'place_bets.player_id', '=', 'users.id')
    //         ->select(
    //             'users.user_name',
    //             'place_bets.player_id',
    //             DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.bet_amount * 1000 ELSE place_bets.bet_amount END) as total_turnover'),
    //             DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.prize_amount * 1000 ELSE place_bets.prize_amount END) as total_payout')
    //         )
    //         ->groupBy('users.user_name', 'place_bets.player_id')
    //         ->get();

    //     $totalTurnover = $dailyReports->sum('total_turnover');
    //     $totalPayout = $dailyReports->sum('total_payout');
    //     $totalWinLoss = $totalPayout - $totalTurnover;

    //     return view('admin.reports.daily_win_loss', compact('dailyReports', 'date', 'totalTurnover', 'totalPayout', 'totalWinLoss'));
    // }

    // public function dailyWinLossReport(Request $request)
    // {
    //     $agent = Auth::user();
    //     $playerIds = $agent->getAllDescendantPlayers()->pluck('id');

    //     $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

    //     $dailyReports = PlaceBet::whereIn('player_id', $playerIds)
    //         ->where('place_bets.wager_status', 'SETTLED')
    //         ->whereDate('place_bets.created_at', $date)
    //         ->join('users', 'place_bets.player_id', '=', 'users.id')
    //         ->select(
    //             'users.user_name',
    //             'place_bets.player_id',
    //             DB::raw('SUM(place_bets.bet_amount) as total_turnover'),
    //             DB::raw('SUM(place_bets.prize_amount) as total_payout')
    //         )
    //         ->groupBy('users.user_name', 'place_bets.player_id')
    //         ->get();

    //     $totalTurnover = $dailyReports->sum('total_turnover');
    //     $totalPayout = $dailyReports->sum('total_payout');
    //     $totalWinLoss = $totalPayout - $totalTurnover;

    //     return view('admin.reports.daily_win_loss', compact('dailyReports', 'date', 'totalTurnover', 'totalPayout', 'totalWinLoss'));
    // }

    public function gameLogReport(Request $request)
    {
        $agent = Auth::user();
        $playerIds = $agent->getAllDescendantPlayers()->pluck('id');

        $query = PlaceBet::whereIn('player_id', $playerIds)
            ->where('wager_status', 'SETTLED')
            ->select(
                'game_name',
                DB::raw('COUNT(*) as spin_count'),
                DB::raw('SUM(bet_amount) as turnover'),
                DB::raw('SUM(prize_amount) - SUM(bet_amount) as win_loss')
            )
            ->groupBy('game_name')
            ->orderBy('game_name');

        if ($request->has('from') && $request->has('to')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to = Carbon::parse($request->to)->endOfDay();
            $query->whereBetween('created_at', [$from, $to]);
        } else {
            // Default to today
            $query->whereDate('created_at', Carbon::today());
        }

        $gameLogs = $query->get();
        $from = $request->from ?? Carbon::today()->toDateString();
        $to = $request->to ?? Carbon::today()->toDateString();

        return view('admin.report.game_log_report', compact('gameLogs', 'from', 'to'));
    }

    public function getReport(Request $request,$playerId)
    {
        $fromDate = $request->input('from_date', now()->format('Y-m-d'));
        $toDate = $request->input('to_date', now()->format('Y-m-d'));

        $poneWines = PoneWineTransaction::where('player_id',$playerId)
                                        ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        $poneWineSummary = (clone $poneWines)
                            ->selectRaw('SUM(bet_amount) as total_bet, SUM(win_lose_amount) as total_win')
                            ->first();

        $poneWinesBetAmount =  $poneWineSummary->total_bet ?? 0;
        $poneWinesWinAmount = $poneWineSummary->total_win ?? 0;
        $poneWinesNetWin    = $poneWinesWinAmount - $poneWinesBetAmount;
        // $poneWines = $poneWines->paginate(10)->setPageName('pone_wine_page');
        $poneWines = $poneWines->orderBy('created_at', 'desc')->paginate(10, ['*'], 'pone_wine_page', $request->input('pone_wine_page'));

        $shans     = ReportTransaction::where('user_id',$playerId)
                                        ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        $shanSummary = (clone $shans)
                        ->selectRaw('SUM(bet_amount) as total_bet, SUM(valid_amount) as total_win')
                        ->first();

        $shansBetAmount = $shanSummary->total_bet ?? 0;
        $shansWinAmount = $shanSummary->total_win ?? 0;
        $shansNetWin    = $shansWinAmount - $shansBetAmount;
        // $shans = $shans->paginate(10)->setPageName('shan_page');
        $shans = $shans->orderBy('created_at', 'desc')->paginate(10, ['*'], 'shan_page', $request->input('shan_page'));

        $buffalos =  LogBuffaloBet::where('player_id',$playerId)
                                   ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        $buffaloSummary = (clone $buffalos)
                            ->selectRaw('SUM(bet_amount) as total_bet, SUM(win_amount) as total_win')
                            ->first();
        $buffalosBetAmount = $buffaloSummary->total_bet ?? 0;
        $buffalosWinAmount = $buffaloSummary->total_win ?? 0;
        $buffalosNetWin    = $buffalosWinAmount-$buffalosBetAmount;
        // $buffalos = $buffalos->paginate(10)->setPageName('buffalo_page');
        $buffalos = $buffalos->orderBy('created_at', 'desc')->paginate(10, ['*'], 'buffalo_page', $request->input('buffalo_page'));

        $slots    =  PlaceBet::where('player_id',$playerId)
                                ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        $slotSummary = (clone $slots)
                        ->selectRaw('SUM(bet_amount) as total_bet, SUM(valid_bet_amount) as total_win')
                        ->first();

        $slotsBetAmount = $slotSummary->total_bet ?? 0;
        $slotsWinAmount = $slotSummary->total_win ?? 0;
        $slotsNetWin    = $slotsWinAmount-$slotsBetAmount;
        // $slots = $slots->paginate(10)->setPageName('slot_page');
        $slots = $slots->orderBy('created_at', 'desc')->paginate(10, ['*'], 'slot_page', $request->input('slot_page'));

        $totalNetWin = $slotsNetWin + $buffalosNetWin + $poneWinesNetWin + $shansNetWin ;
        $totalBetAmount = $slotsBetAmount + $buffalosBetAmount + $poneWinesBetAmount + $shansBetAmount ;
        $totalWinAmount = $slotsWinAmount + $buffalosWinAmount + $poneWinesWinAmount + $shansWinAmount ;

        $data = [
        'ponewine' => [
            'bet' => $poneWinesBetAmount,
            'win' => $poneWinesWinAmount,
            'net' => $poneWinesNetWin,
        ],
        'shan' => [
            'bet' => $shansBetAmount,
            'win' => $shansWinAmount,
            'net' => $shansNetWin,
        ],
        'buffalo' => [
            'bet' => $buffalosBetAmount,
            'win' => $buffalosWinAmount,
            'net' => $buffalosNetWin,
        ],
        'slot' => [
            'bet' => $slotsBetAmount,
            'win' => $slotsWinAmount,
            'net' => $slotsNetWin,
        ],
        'total' => [
            'totalNetWin' => $totalNetWin,
            'totalBetAmount' => $totalBetAmount,
            'totalWinAmount' => $totalWinAmount
        ]
        ];

        return view('agent.player.report_index',compact(
            'poneWines',
            'shans',
            'buffalos',
            'slots',
            'playerId',
            'data'
        ));
    }
}
