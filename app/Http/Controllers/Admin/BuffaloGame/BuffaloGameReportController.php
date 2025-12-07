<?php

namespace App\Http\Controllers\Admin\BuffaloGame;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\BuffaloWagerTransaction;
use App\Enums\UserType;
use Carbon\Carbon;

class BuffaloGameReportController extends Controller
{

  public function index(Request $request)
{
    $currentUser = auth()->user();
    $currentUserType = UserType::tryFrom($currentUser->user_type ?? 0);

    $fromDate = $request->get('from_date', Carbon::now()->toDateString());
    $toDate   = $request->get('to_date', Carbon::now()->toDateString());
    $agentId  = $request->get('agent_id');
    $playerId = $request->get('player_id');
    $searchTerm = $request->get('search', '');

    $query = BuffaloWagerTransaction::whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
    ]);

    // Default to player view
    $viewType = 'player';
    $groupByColumn = 'user_id';

    if ($currentUser->type == UserType::Owner->value) {

        // If no agent selected => view agents
        $viewType = $agentId ? 'player' : 'agent';
        $groupByColumn = $agentId ? 'user_id' : 'player_agent_id';

        if ($agentId) {
            $query->where('player_agent_id', $agentId);

            if ($playerId) {
                $query->where('user_id', $playerId);
            }
        }

    } elseif ($currentUser->type == UserType::Agent->value) {

        $viewType = 'player';
        $groupByColumn = 'user_id';

        $query->where('player_agent_id', $currentUser->id);
        $agentId = $currentUser->id;

        if ($playerId) {
            $query->where('user_id', $playerId);
        }
    }

    if ($searchTerm) {
        $query->where('member_account', 'like', "%$searchTerm%");
    }

    // $reports = $query->select(
    //         "$groupByColumn as user_id",
    //         DB::raw('COUNT(id) as total_bets'),
    //         DB::raw('SUM(bet_amount) as total_bet_amount'),
    //         DB::raw('SUM(prize_amount) as total_prize_amount'),
    //         DB::raw('SUM(net_amount) as net_profit_loss')
    //     )
    //     ->groupBy($groupByColumn)
    //     ->paginate(20)
    //     ->appends($request->query());

    $reports = $query->select(
        "$groupByColumn as user_id",
        DB::raw('COUNT(id) as total_bets'),
        DB::raw('SUM(bet_amount) as total_bet_amount'),
        DB::raw('SUM(prize_amount) as total_win_amount'),
        DB::raw('SUM(net_amount) as net_profit_loss')
    )
    ->groupBy($groupByColumn)
    ->paginate(20)
    ->appends($request->query());


    // Attach Name
    $userData = User::whereIn('id', $reports->pluck('user_id'))
        ->select('id','user_name','name')
        ->get()
        ->keyBy('id');

    $reports->transform(function($row) use ($userData, $viewType) {
        $user = $userData[$row->user_id] ?? null;
        $row->username = $user->user_name ?? 'Unknown';
        $row->fullname = $user->name ?? '-';
        $row->type = $viewType;
        return $row;
    });

    // Filters
    $agentsFilter = collect();
    $playersFilter = collect();

    if ($currentUser->type  == UserType::Owner->value) {
        $agentsFilter = User::where('type', UserType::Agent->value)->get();

        if ($agentId) {
            $playersFilter = User::where('agent_id', $agentId)->get();
        }

    } else {
        $playersFilter = User::where('agent_id', $currentUser->id)->get();
    }

    return view('admin.buffalo_game.report.new_index', compact(
        'reports','fromDate','toDate','viewType','agentsFilter','playersFilter',
        'agentId','playerId','searchTerm'
    ));
}

   public function show($id, Request $request)
{
    $currentUser = auth()->user();
    $currentUserType = UserType::tryFrom($currentUser->user_type ?? 0);

    $fromDate = $request->get('from_date', now()->toDateString());
    $toDate   = $request->get('to_date', now()->toDateString());

    $targetUser = User::findOrFail($id);

    // Query Transactions
    $betsQuery = BuffaloWagerTransaction::whereBetween('created_at', [
            Carbon::parse($fromDate)->startOfDay(),
            Carbon::parse($toDate)->endOfDay()
        ])
        ->when($targetUser->user_type == UserType::Agent->value, function ($q) use ($targetUser) {
            $q->where('player_agent_id', $targetUser->id);
        }, function ($q) use ($targetUser) {
            $q->where('user_id', $targetUser->id);
        })
        ->orderBy('created_at', 'desc');

    $bets = $betsQuery->paginate(30)->appends($request->query());

    // Summary Calculation
    $summary = [
        'total_bets'        => $betsQuery->count(),
        'total_bet_amount'  => $betsQuery->sum('bet_amount'),
        'total_win_amount'  => $betsQuery->sum('prize_amount'),
    ];

    $summary['net_profit_loss'] = $summary['total_win_amount'] - $summary['total_bet_amount'];

    // Type label for blade (Owner view)
    $typeString = $targetUser->user_type == UserType::Agent->value ? 'Agent' : 'Player';

    return view('admin.buffalo_game.report.new_show', compact(
        'bets', 'summary', 'targetUser', 'fromDate', 'toDate', 'typeString'
    ));
}


 // public function show($id, Request $request)
 // {
 //     $currentUser = auth()->user();
 //     $currentUserType = UserType::tryFrom($currentUser->user_type ?? 0);

 //     // if (!in_array($currentUserType, [UserType::Owner, UserType::Agent])) {
 //     //     abort(403, 'Access restricted.');
 //     // }

 //     $fromDate = $request->get('from_date', Carbon::now()->toDateString());
 //     $toDate   = $request->get('to_date', Carbon::now()->toDateString());

 //     $viewedUser = User::findOrFail($id);

 //     // Only restrict Agents, not Owner
 //     // if ($currentUserType === UserType::Agent) {
 //     //     if ($viewedUser->agent_id != $currentUser->id) {
 //     //         abort(403, 'Access Denied.');
 //     //     }
 //     // }

 //     $details = BuffaloWagerTransaction::whereBetween('created_at', [
 //             Carbon::parse($fromDate)->startOfDay(),
 //             Carbon::parse($toDate)->endOfDay()
 //         ])
 //         ->where(function ($q) use ($viewedUser) {
 //             if ($viewedUser->user_type === UserType::Agent->value) {
 //                 $q->where('player_agent_id', $viewedUser->id);
 //             } else {
 //                 $q->where('user_id', $viewedUser->id);
 //             }
 //         })
 //         ->orderBy('created_at', 'desc')
 //         ->paginate(30)
 //         ->appends($request->query());

 //     return view('admin.buffalo_game.report.new_show', compact(
 //         'details', 'viewedUser', 'fromDate', 'toDate'
 //     ));
 // }


 //     public function index(Request $request)
//     {
//         $currentUser = auth()->user();
//         $currentUserType = UserType::tryFrom($currentUser->user_type ?? 0);

//         if (!in_array($currentUserType, [UserType::Owner, UserType::Agent])) {
//             abort(403, 'Access restricted.');
//         }

//         $fromDate = $request->get('from_date', Carbon::now()->toDateString());
//         $toDate   = $request->get('to_date', Carbon::now()->toDateString());
//         $agentId  = $request->get('agent_id');
//         $playerId = $request->get('player_id');
//         $searchTerm = $request->get('search', '');

//         $query = BuffaloWagerTransaction::whereBetween('created_at', [
//             Carbon::parse($fromDate)->startOfDay(),
//             Carbon::parse($toDate)->endOfDay()
//         ]);

//         // Determine View Context
//         if ($currentUserType === UserType::Owner) {
//             $viewType = $agentId ? 'player' : 'agent';
//             $groupByColumn = $agentId ? 'player_id' : 'player_agent_id';

//             if ($agentId) {
//                 $query->where('player_agent_id', $agentId);
//                 if ($playerId) $query->where('player_id', $playerId);
//             }

//         } else {
//             $viewType = 'player';
//             $groupByColumn = 'player_id';
//             $query->where('player_agent_id', $currentUser->id);
//             $agentId = $currentUser->id;

//             if ($playerId) $query->where('player_id', $playerId);
//         }

//         // Search filter
//         if ($searchTerm) {
//             $query->where(function($q) use ($searchTerm) {
//                 $q->where('member_account', 'like', "%$searchTerm%");
//             });
//         }

//         $reports = $query->select(
//             "$groupByColumn as user_id",
//             DB::raw('COUNT(id) as total_bets'),
//             DB::raw('SUM(bet_amount) as total_bet_amount'),
//             DB::raw('SUM(win_amount) as total_win_amount'),
//             DB::raw('SUM(win_amount - bet_amount) as net_profit_loss')
//         )
//         ->groupBy($groupByColumn)
//         ->paginate(20)
//         ->appends($request->query());

//         $userData = User::whereIn('id', $reports->pluck('user_id'))
//             ->select('id','user_name','name')
//             ->get()
//             ->keyBy('id');

//         $reports->map(function($row) use ($userData, $viewType) {
//             $user = $userData[$row->user_id] ?? null;
//             $row->username = $user->user_name ?? 'Unknown';
//             $row->fullname = $user->name ?? '-';
//             $row->type = $viewType;
//         });

//         // Dropdown filters
//         $agentsFilter = collect();
//         $playersFilter = collect();

//         if ($currentUserType === UserType::Owner) {
//             $agentsFilter = User::where('user_type', UserType::Agent->value)->get();

//             if ($agentId) {
//                 $playersFilter = User::where('agent_id', $agentId)->get();
//             }

//         } else {
//             $playersFilter = User::where('agent_id', $currentUser->id)->get();
//         }

//         return view('admin.buffalo_game.report.new_index', compact(
//             'reports','fromDate','toDate','viewType','agentsFilter','playersFilter',
//             'agentId','playerId','searchTerm'
//         ));
//     }

//     public function show($id, Request $request)
// {
//     $currentUser = auth()->user();
//     $currentUserType = UserType::tryFrom($currentUser->user_type ?? 0);

//     if (!in_array($currentUserType, [UserType::Owner, UserType::Agent])) {
//         abort(403, 'Access restricted.');
//     }

//     $fromDate = $request->get('from_date', Carbon::now()->toDateString());
//     $toDate   = $request->get('to_date', Carbon::now()->toDateString());

//     // Check Viewing Mode
//     $viewedUser = User::findOrFail($id);

//     if ($currentUserType === UserType::Agent && $viewedUser->agent_id != $currentUser->id) {
//         abort(403, 'Access Denied.');
//     }

//     // Fetch Detailed Bets
//     $details = BuffaloWagerTransaction::whereBetween('created_at', [
//             Carbon::parse($fromDate)->startOfDay(),
//             Carbon::parse($toDate)->endOfDay()
//         ])
//         ->where(function ($q) use ($viewedUser) {
//             if ($viewedUser->user_type === UserType::Agent->value) {
//                 $q->where('player_agent_id', $viewedUser->id);
//             } else {
//                 $q->where('player_id', $viewedUser->id);
//             }
//         })
//         ->orderBy('created_at', 'desc')
//         ->paginate(30)
//         ->appends($request->query());

//     return view('admin.buffalo_game.report.new_show', compact(
//         'details', 'viewedUser', 'fromDate', 'toDate'
//     ));
// }

}
