@extends('layouts.master')
@section('content')
<div class="container mt-4">

    <h4 class="mb-3">Game Reports</h4>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('admin.game_report.index')}}" class="row g-3 mb-3">
        <div class="col-md-3">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search user or agent">
        </div>

        <div class="col-md-2">
            <select name="game_type" class="form-control">
                <option value="">All Game Types</option>
                <option value="Slot" {{ request('game_type') == 'Slot' ? 'selected' : '' }}>Slot</option>
                <option value="Buffalo" {{ request('game_type') == 'Buffalo' ? 'selected' : '' }}>Buffalo</option>
                <option value="Shan" {{ request('game_type') == 'Shan' ? 'selected' : '' }}>Shan</option>
                <option value="PoneWine" {{ request('game_type') == 'PoneWine' ? 'selected' : '' }}>PoneWine</option>
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" name="startDate" value="{{ request('startDate') }}" class="form-control">
        </div>

        <div class="col-md-2">
            <input type="date" name="endDate" value="{{ request('endDate') }}" class="form-control">
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>User Name</th>
                        <th>Agent Name</th>
                        {{-- <th>Provider</th> --}}
                        {{-- <th>Game Type</th> --}}
                        {{-- <th>Wager Code</th> --}}
                        <th>Bet Amount</th>
                        <th>Payout Amount</th>
                        <th>Win/Lose Amount</th>
                        <th>Detail</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>{{ ($reports->currentPage() - 1) * $reports->perPage() + $loop->iteration }}</td>
                            <td>{{ $report->user_name }}</td>
                            <td>{{ $report->agent_name }}</td>
                            {{-- <td>{{ $report->provider_name }}</td> --}}
                            {{-- <td>{{ $report->game_type }}</td> --}}
                            {{-- <td>{{ $report->wager_code }}</td> --}}
                            <td>{{ number_format($report->total_bet, 2) }}</td>
                            <td>{{ number_format($report->total_prize, 2) }}</td>
                            <td class="{{ $report->total_win_lose > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($report->total_win_lose, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.player.game_report.index', $report->user_id) }}" class="btn btn-sm btn-primary">
                                    Detail
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-3">No Data Found</td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="table-secondary fw-bold">
                        <td colspan="3" class="text-end">Total:</td>
                        <td>{{ number_format($totalBetAmount, 2) }}</td>
                        <td>{{ number_format($totalPrizeAmount, 2) }}</td>
                        <td class="{{ $totalWinLose > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($totalWinLose, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-3 px-3">
            {{ $reports->appends(request()->all())->links() }} {{-- Pagination with filter --}}
        </div>
    </div>

</div>
@endsection

