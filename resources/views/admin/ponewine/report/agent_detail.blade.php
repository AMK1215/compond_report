@extends('layouts.master')

@section('title', 'PoneWine Agent Report - ' . ($agent->user_name ?? 'Unknown Agent'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-tie mr-2"></i>PoneWine Agent Report: {{ $agent->user_name ?? 'Unknown Agent' }}
                        <small class="text-muted ml-2">
                            <i class="fas fa-filter mr-1"></i>Showing non-direct wins only
                        </small>
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.ponewine.report.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Report
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Agent Info -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-user-tie"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Agent Name</span>
                                    <span class="info-box-number">{{ $agent->user_name ?? 'Unknown' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-wallet"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Current Balance</span>
                                    <span class="info-box-number">{{ number_format($agent->balanceFloat ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Players</span>
                                    <span class="info-box-number">{{ $agentTotals->total_players ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('admin.ponewine.report.agent.detail', $agent->id) }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_from">Date From:</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" 
                                           value="{{ request('date_from') }}" placeholder="Optional">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_to">Date To:</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" 
                                           value="{{ request('date_to') }}" placeholder="Optional">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="player_name">Player Name:</label>
                                    <input type="text" name="player_name" id="player_name" class="form-control" 
                                           value="{{ request('player_name') }}" placeholder="Search player (optional)...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="room_id">Room ID:</label>
                                    <input type="number" name="room_id" id="room_id" class="form-control" 
                                           value="{{ request('room_id') }}" placeholder="Room ID (optional)">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary form-control" title="Search">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Agent Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $agentTotals->total_games ?? 0 }}</h3>
                                    <p>Total Games</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-gamepad"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3>{{ $agentTotals->total_bets ?? 0 }}</h3>
                                    <p>Total Bets</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ number_format($agentTotals->total_bet_amount ?? 0, 2) }}</h3>
                                    <p>Total Bet Amount</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-success">
                                <!-- <div class="inner">
                                    <h3>{{ number_format($agentTotals->total_wins ?? 0, 2) }}</h3>
                                    <p>Total Wins</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-trophy"></i>
                                </div> -->
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-danger">
                                <!-- <div class="inner">
                                    <h3>{{ number_format($agentTotals->total_losses ?? 0, 2) }}</h3>
                                    <p>Total Losses</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-line-down"></i>
                                </div> -->
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <!-- <div class="small-box {{ ($agentTotals->net_result ?? 0) >= 0 ? 'bg-success' : 'bg-danger' }}">
                                <div class="inner">
                                    <h3>{{ number_format($agentTotals->net_result ?? 0, 2) }}</h3>
                                    <p>Net Result</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div> -->
                        </div>
                    </div>

                    <!-- Detailed Transactions Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="4%">No</th>
                                    <th width="8%">Room ID</th>
                                    <th width="12%">Match ID</th>
                                    <th width="8%">Win Number</th>
                                    <th width="12%">Player Name</th>
                                    <th width="8%">Bet Number</th>
                                    <th width="10%">Bet Amount</th>
                                    <th width="10%">Win/Lose</th>
                                    <th width="8%">Result</th>
                                    <th width="10%">Balance Before</th>
                                    <th width="10%">Balance After</th>
                                    <th width="10%">Game Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $index => $transaction)
                                    <tr>
                                        <td>{{ ($transactions->currentPage() - 1) * $transactions->perPage() + $index + 1 }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $transaction->room_id }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($transaction->match_id, 15) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ $transaction->win_number }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.ponewine.report.player.detail', $transaction->user_id) }}" 
                                               class="text-primary font-weight-bold">
                                                {{ $transaction->user_name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning">{{ $transaction->bet_number }}</span>
                                        </td>
                                        <td class="text-right">
                                            <strong>{{ number_format($transaction->bet_amount, 2) }}</strong>
                                        </td>
                                        <td class="text-right">
                                            <span class="font-weight-bold {{ $transaction->win_lose_amount >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $transaction->win_lose_amount >= 0 ? '+' : '' }}{{ number_format($transaction->win_lose_amount, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($transaction->result == 'Win')
                                                <span class="badge badge-success">{{ $transaction->result }}</span>
                                            @elseif($transaction->result == 'Lose')
                                                <span class="badge badge-danger">{{ $transaction->result }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $transaction->result }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <small>{{ number_format($transaction->player_balance_before, 2) }}</small>
                                        </td>
                                        <td class="text-right">
                                            <small>{{ number_format($transaction->player_balance_after, 2) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($transaction->created_at)->format('M d, Y H:i') }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                            No transactions found for this agent.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
