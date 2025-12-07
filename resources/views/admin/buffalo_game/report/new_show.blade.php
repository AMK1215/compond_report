@extends('layouts.master')

@section('title', 'Buffalo Game Report Detail')

@section('content')
<div class="container-fluid">

    <div class="card mb-3">
        <div class="card-body">

            <h4 class="mb-1">
                <strong>{{ $targetUser->user_name }}</strong>
            </h4>

            <small class="text-muted">{{ $targetUser->name }}</small>

            <form method="GET" class="mt-3">

                <input type="hidden" name="type" value="{{ $typeString }}">

                <div class="row">
                    <div class="col-md-3">
                        <label>From</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>

                    <div class="col-md-3">
                        <label>To</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.new_buffalo_report.index') }}" class="btn btn-secondary">Back</a>
                </div>

            </form>
        </div>
    </div>


    {{-- Summary Section --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <strong>Total Bets</strong>
                <h4>{{ number_format($summary['total_bets']) }}</h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <strong>Total Bet Amount</strong>
                <h4>{{ number_format($summary['total_bet_amount'],2) }}</h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <strong>Total Win Amount</strong>
                <h4>{{ number_format($summary['total_win_amount'],2) }}</h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <strong>Net P/L</strong>
                <h4 class="{{ ($summary['net_profit_loss']) >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($summary['net_profit_loss'], 2) }}
                </h4>
            </div>
        </div>
    </div>


    {{-- Table --}}
    <div class="card">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>PLAYERID</th>
                            <th>Bet Amount</th>
                            <th>Win Amount</th>
                            <th>Result</th>
                            <th>Time</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($bets as $index => $bet)
                        <tr>
                            <td>{{ $bets->firstItem() + $index }}</td>
                            <td>{{ $bet->user->user_name }}</td>
                            <td>{{ number_format($bet->bet_amount,2) }}</td>
                            <td>{{ number_format($bet->prize_amount,2) }}</td>
                            <td class="{{ ($bet->prize_amount - $bet->bet_amount) >= 0 ? 'text-success':'text-danger' }}">
                                {{ number_format($bet->prize_amount - $bet->bet_amount, 2) }}
                            </td>
                            <td>{{ $bet->created_at }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">
                                No data available.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $bets->links() }}
            </div>

        </div>
    </div>

</div>
@endsection
