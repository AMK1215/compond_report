@extends('layouts.master')

@section('title', 'PoneWine Player Report - ' . $player->user_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user mr-2"></i>PoneWine Player Report: {{ $player->user_name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.ponewine.report.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Report
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Player Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-user"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Player Name</span>
                                    <span class="info-box-number">{{ $player->user_name }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-wallet"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Current Balance</span>
                                    <span class="info-box-number">{{ number_format($player->balanceFloat, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Form -->
                    <form method="GET" action="{{ route('admin.ponewine.report.player.detail', $player->id) }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date_from">Date From:</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" 
                                           value="{{ request('date_from', now()->subDays(30)->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date_to">Date To:</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" 
                                           value="{{ request('date_to', now()->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary form-control">
                                        <i class="fas fa-search mr-1"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Player Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $playerTotals->total_games ?? 0 }}</h3>
                                    <p>Total Games</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-gamepad"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ number_format($playerTotals->total_bet_amount ?? 0, 2) }}</h3>
                                    <p>Total Bet Amount</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <!-- <div class="inner">
                                    <h3>{{ number_format($playerTotals->total_wins ?? 0, 2) }}</h3>
                                    <p>Total Wins</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-trophy"></i>
                                </div> -->
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <!-- <div class="small-box {{ ($playerTotals->net_result ?? 0) >= 0 ? 'bg-success' : 'bg-danger' }}">
                                <div class="inner">
                                    <h3>{{ number_format($playerTotals->net_result ?? 0, 2) }}</h3>
                                    <p>Net Result</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div> -->
                        </div>
                    </div>

                    <!-- Player Game History Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="4%">No</th>
                                    <th width="8%">ID</th>
                                    <th width="8%">Room ID</th>
                                    <th width="12%">Match ID</th>
                                    <th width="8%">Win Number</th>
                                    <th width="8%">Bet Number</th>
                                    <th width="10%">Bet Amount</th>
                                    <th width="10%">Win/Lose</th>
                                    <th width="8%">Result</th>
                                    <th width="10%">Balance Before</th>
                                    <th width="10%">Balance After</th>
                                    <th width="8%">Game Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($playerReports as $index => $report)
                                    <tr>
                                        <td>{{ ($playerReports->currentPage() - 1) * $playerReports->perPage() + $index + 1 }}</td>
                                        <td>
                                            <span class="badge badge-secondary">{{ $report->id }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $report->room_id }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($report->match_id, 20) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ $report->win_number }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning">{{ $report->bet_number }}</span>
                                        </td>
                                        <td class="text-right">
                                            <strong>{{ number_format($report->bet_amount, 2) }}</strong>
                                        </td>
                                        <td class="text-right">
                                            <span class="font-weight-bold {{ $report->win_lose_amount >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $report->win_lose_amount >= 0 ? '+' : '' }}{{ number_format($report->win_lose_amount, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($report->result == 'Win')
                                                <span class="badge badge-success">{{ $report->result }}</span>
                                            @elseif($report->result == 'Lose')
                                                <span class="badge badge-danger">{{ $report->result }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $report->result }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <small>{{ number_format($report->player_balance_before, 2) }}</small>
                                        </td>
                                        <td class="text-right">
                                            <small>{{ number_format($report->player_balance_after, 2) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($report->created_at)->format('M d, Y H:i') }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                            No game history found for this player.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $playerReports->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
