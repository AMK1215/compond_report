@extends('layouts.master')

@section('title', 'Buffalo Game Report')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-body">



            <form method="GET">
                <div class="row">

                    <div class="col-md-3">
                        <label>From</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>

                    <div class="col-md-3">
                        <label>To</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>

                    @if($viewType === 'agent')
                    <div class="col-md-3">
                        <label>Agent</label>
                        <select name="agent_id" class="form-select">
                            <option value="">All</option>
                            @foreach($agentsFilter as $agent)
                                <option value="{{ $agent->id }}" {{ $agentId == $agent->id ? 'selected' : '' }}>
                                    {{ $agent->user_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if(count($playersFilter) > 0)
                    <div class="col-md-3">
                        <label>Player</label>
                        <select name="player_id" class="form-select">
                            <option value="">All</option>
                            @foreach($playersFilter as $player)
                                <option value="{{ $player->id }}" {{ $playerId == $player->id ? 'selected' : '' }}>
                                    {{ $player->user_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.buffalo-report.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>


            <div class="table-responsive mt-4">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            {{-- <th>{{ $viewType === 'agent' ? 'Agent Name' : 'Player Name' }}</th> --}}
                            @if(Auth::user()->hasRole('Agent'))
                            <th>Player Name</th>
                            @endif
                            <th>Total Bets</th>
                            <th>Bet Amount</th>
                            <th>Win Amount</th>
                            <th>P/L</th>
                            <th>Action</th>

                        </tr>
                    </thead>

                    <tbody>
                        @forelse($reports as $index => $row)
                        <tr>
                            <td>{{ $reports->firstItem() + $index }}</td>
                            @if(Auth::user()->hasRole('Agent'))
                            <td>{{ $row->username }} <br><small>{{ $row->fullname }}</small></td>
                            @endif
                            <td>{{ $row->total_bets }}</td>
                            <td>{{ number_format($row->total_bet_amount,2) }}</td>
                            <td>{{ number_format($row->total_win_amount,2) }}</td>
                            <td class="{{ $row->net_profit_loss >= 0 ? 'text-success':'text-danger' }}">
                                {{ number_format($row->net_profit_loss,2) }}
                            </td>
                            <td>
                                <a href="{{ route('admin.new_buffalo_report.show', $row->user_id) }}?from_date={{ $fromDate }}&to_date={{ $toDate }}"
                                class="btn btn-sm btn-outline-primary">
                                    Details
                                </a>
                            </td>

                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No data found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $reports->links() }}
            </div>

        </div>
    </div>

</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('buffaloRtpForm');
    if (!form) {
        return;
    }

    const requestTimeInput = document.getElementById('rtpRequestTime');
    const submitBtn = document.getElementById('rtpSubmitBtn');
    const spinner = submitBtn.querySelector('.spinner-border');
    const btnLabel = submitBtn.querySelector('.btn-label');
    const currentRtpEl = document.getElementById('currentRtpValue');
    const lastUpdatedEl = document.getElementById('rtpLastUpdatedAt');
    const rtpInput = document.getElementById('rtpValue');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const rtpValue = rtpInput.value;
        if (!rtpValue) {
            toastr.error('Please enter an RTP value.');
            return;
        }

        const requestTime = Date.now();
        requestTimeInput.value = requestTime;

        submitBtn.disabled = true;
        btnLabel.classList.add('d-none');
        spinner.classList.remove('d-none');

        $.ajax({
            url: form.getAttribute('action'),
            method: 'POST',
            data: {
                rtp: rtpValue,
                requestTime: requestTime
            },
            success: function (response) {
                const message = response?.message ?? 'RTP updated successfully.';
                toastr.success(message);

                const newRtp = response?.data?.rtp ?? rtpValue;
                if (currentRtpEl && newRtp !== undefined) {
                    const parsedRtp = parseFloat(newRtp);
                    currentRtpEl.textContent = isNaN(parsedRtp) ? newRtp : parsedRtp.toFixed(2);
                }

                if (lastUpdatedEl && !isNaN(requestTime)) {
                    const formatted = new Date(requestTime).toLocaleString();
                    lastUpdatedEl.textContent = formatted;
                }
            },
            error: function (xhr) {
                let message = 'Failed to update RTP.';

                if (xhr?.responseJSON) {
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join(' ');
                    }
                }

                toastr.error(message);
            },
            complete: function () {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
                btnLabel.classList.remove('d-none');
            }
        });
    });
});
</script>
@endsection
