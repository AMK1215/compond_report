@extends('layouts.master')

@section('title', 'PoneWine Game Report')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-dice mr-2"></i>PoneWine Game Report
                        <small class="text-muted ml-2">
                            <i class="fas fa-filter mr-1"></i>Showing non-direct wins only
                        </small>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" onclick="exportToCsv()">
                            <i class="fas fa-download mr-1"></i>Export CSV
                        </button>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.ponewine.report.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_from">Date From:</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control"
                                           value="{{ request('date_from') }}" placeholder="Optional">
                                </div>
                            </div>
                            <div class="col-md-2">
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
                            {{-- <div class="col-md-2">
                                <div class="form-group">
                                    <label for="room_id">Room ID:</label>
                                    <input type="number" name="room_id" id="room_id" class="form-control"
                                           value="{{ request('room_id') }}" placeholder="Room ID (optional)">
                                </div>
                            </div> --}}
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary form-control" title="Search">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <a href="{{ route('admin.ponewine.report.index') }}" class="btn btn-secondary form-control" title="Clear all filters">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Summary Cards -->
                    {{-- <div class="row mb-4">
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $totals->total_games ?? 0 }}</h3>
                                    <p>Total Games</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-gamepad"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3>{{ $totals->total_agents ?? 0 }}</h3>
                                    <p>Total Agents</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $totals->total_players ?? 0 }}</h3>
                                    <p>Total Players</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ number_format($totals->total_bet_amount ?? 0, 2) }}</h3>
                                    <p>Total Bet Amount</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3>{{ $totals->total_bets ?? 0 }}</h3>
                                    <p>Total Bets</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <!-- <div class="small-box {{ ($totals->net_result ?? 0) >= 0 ? 'bg-success' : 'bg-danger' }}">
                                <div class="inner">
                                    <h3>{{ number_format($totals->net_result ?? 0, 2) }}</h3>
                                    <p>Net Result</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div> -->
                        </div>
                    </div> --}}

                    <!-- Agent Report Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="5%">No</th>
                                    @if (Auth::user()->hasRole('Agent'))
                                        <th width="15%">Player Name</th>
                                    @endif
                                    <th width="15%">Agent Name</th>
                                    <th width="8%">Total Games</th>
                                    <th width="8%">Total Players</th>
                                    <th width="8%">Total Bets</th>
                                    <th width="12%">Total Bet Amount</th>
                                    <!-- <th width="10%">Total Wins</th>
                                    <th width="10%">Total Losses</th>
                                    <th width="12%">Net Result</th> -->
                                    <th width="12%">Last Game Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agentReports as $index => $agent)
                                    <tr>
                                        <td>{{ ($agentReports->currentPage() - 1) * $agentReports->perPage() + $index + 1 }}</td>
                                        @if (Auth::user()->hasRole('Agent'))
                                        <td>{{ $agent->user_name }}</td>
                                        @endif
                                        <td>
                                            <a href="{{ route('admin.ponewine.report.agent.detail', $agent->player_agent_id ?? '') }}"
                                               class="text-primary font-weight-bold">
                                                <i class="fas fa-user-tie mr-1"></i>
                                                {{ $agent->player_agent_name ?? 'Unknown Agent' }}
                                            </a>
                                            <br>
                                            <small class="text-muted">ID: {{ $agent->player_agent_id }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $agent->total_games }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-success">{{ $agent->total_players }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-secondary">{{ $agent->total_bets }}</span>
                                        </td>
                                        <td class="text-right">
                                            <strong>{{ number_format($agent->total_bet_amount, 2) }}</strong>
                                        </td>
                                        <!-- <td class="text-right">
                                            <span class="text-success font-weight-bold">
                                                {{ number_format($agent->total_wins, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-danger font-weight-bold">
                                                {{ number_format($agent->total_losses, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <span class="font-weight-bold {{ $agent->net_result >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $agent->net_result >= 0 ? '+' : '' }}{{ number_format($agent->net_result, 2) }}
                                            </span>
                                        </td> -->
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($agent->last_game_date)->format('M d, Y H:i') }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                            No PoneWine game data found for the selected criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $agentReports->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToCsv() {
    // Get current form parameters
    const params = new URLSearchParams(window.location.search);

    // Add export parameter
    params.set('export', 'csv');

    // Redirect to export URL
    window.location.href = '{{ route("admin.ponewine.report.export") }}?' + params.toString();
}
</script>
@endsection
