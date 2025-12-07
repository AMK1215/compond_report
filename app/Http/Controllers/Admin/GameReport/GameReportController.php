<?php

namespace App\Http\Controllers\Admin\GameReport;

use Carbon\Carbon;
use App\Models\GameReport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class GameReportController extends Controller
{
    public function index(Request $request) {

        $startDate = $request->startDate
            ? Carbon::parse($request->startDate)->startOfDay()
            : Carbon::today()->startOfDay();

        $endDate = $request->endDate
            ? Carbon::parse($request->endDate)->endOfDay()
            : Carbon::today()->endOfDay();

        $user = Auth::user();

        $reportsQuery = GameReport::leftJoin('users as u', 'u.id', '=', 'game_reports.user_id')
                                    ->leftJoin('users as a', 'a.id', '=', 'game_reports.agent_id')
                                    ->selectRaw(
                                            'game_reports.user_id,
                                            u.name as user_name,
                                            a.name as agent_name,
                                            SUM(game_reports.bet_amount) as total_bet,
                                            SUM(game_reports.prize_amount) as total_prize,
                                            SUM(game_reports.net_amount) as total_net,
                                            (SUM(game_reports.net_amount) - SUM(game_reports.bet_amount)) as total_win_lose,
                                            MAX(game_reports.created_at) as last_played'
                                );

        if($user->hasRole('Agent')) {
        $accessibleIds = $user->getAllDescendants();
        $reportsQuery->whereIn('game_reports.agent_id', $accessibleIds);
        }

        $reportsQuery->whereBetween('game_reports.created_at', [$startDate, $endDate])
                        ->filter([
                                'search' => $request->search,
                                'game_type'   => $request->game_type
                            ]);

        $cloneQuery = (clone $reportsQuery);
        $totalBetAmount =   $cloneQuery->sum('game_reports.bet_amount');
        $totalPrizeAmount =   $cloneQuery->sum('game_reports.prize_amount');
        $totalNetAmount = $cloneQuery->sum('game_reports.net_amount');
        $totalWinLose = $totalNetAmount - $totalBetAmount ;

        $reports = $reportsQuery->groupBy('game_reports.user_id', 'u.name', 'a.name')->orderByDesc('last_played')->paginate(10);

        return view('admin.game_report.index',compact('reports','totalBetAmount','totalPrizeAmount','totalWinLose'));
    }

    public function playerIndex(Request $request,$id) {

        $startDate = $request->startDate
            ? Carbon::parse($request->startDate)->startOfDay()
            : Carbon::today()->startOfDay();

        $endDate = $request->endDate
            ? Carbon::parse($request->endDate)->endOfDay()
            : Carbon::today()->endOfDay();

        $reportsQuery = GameReport::where('user_id',$id)
                            ->leftJoin('users as u', 'u.id', '=', 'game_reports.user_id')
                            ->leftJoin('users as a', 'a.id', '=', 'game_reports.agent_id')
                            ->select(
                                'game_reports.*',
                                'u.name as user_name',
                                'a.name as agent_name'
                            )
                            ->selectRaw('(game_reports.net_amount - game_reports.bet_amount) as win_lose')
                            ->whereBetween('game_reports.created_at', [$startDate, $endDate])
                            ->latest()
                            ->filter([
                                      'search' => $request->search,
                                      'game_type'   => $request->game_type
                            ]);

        $cloneQuery = (clone $reportsQuery);
        $totalBetAmount = $cloneQuery->sum('game_reports.bet_amount');
        $totalPrizeAmount = $cloneQuery->sum('game_reports.prize_amount');
        $totalNetAmount = $cloneQuery->sum('game_reports.net_amount');
        $totalWinLose = $totalNetAmount - $totalBetAmount ;
        $reports =  $reportsQuery->paginate(10);

        return view('agent.player.game_report.index',compact('reports','totalBetAmount','totalPrizeAmount','totalWinLose','id'));
    }

    public function agentIndex(Request $request,$id) {

        $startDate = $request->startDate
            ? Carbon::parse($request->startDate)->startOfDay()
            : Carbon::today()->startOfDay();

        $endDate = $request->endDate
            ? Carbon::parse($request->endDate)->endOfDay()
            : Carbon::today()->endOfDay();

        $reportsQuery = GameReport::where('game_reports.agent_id',$id)
                            ->leftJoin('users as u', 'u.id', '=', 'game_reports.user_id')
                            ->leftJoin('users as a', 'a.id', '=', 'game_reports.agent_id')
                            ->select(
                                'game_reports.*',
                                'u.name as user_name',
                                'a.name as agent_name'
                            )
                            ->selectRaw('(game_reports.net_amount - game_reports.bet_amount) as win_lose')
                            ->whereBetween('game_reports.created_at', [$startDate, $endDate])
                            ->latest()
                            ->filter([
                                      'search' => $request->search,
                                      'game_type'   => $request->game_type
                            ]);

        $cloneQuery = (clone $reportsQuery);
        $totalBetAmount = $cloneQuery->sum('game_reports.bet_amount');
        $totalPrizeAmount = $cloneQuery->sum('game_reports.prize_amount');
        $totalNetAmount = $cloneQuery->sum('game_reports.net_amount');
        $totalWinLose = $totalNetAmount - $totalBetAmount ;
        $reports =  $reportsQuery->paginate(10);

        return view('agent.sub_agent.game_report.index',compact('reports','totalBetAmount','totalPrizeAmount','totalWinLose','id'));
    }
}

