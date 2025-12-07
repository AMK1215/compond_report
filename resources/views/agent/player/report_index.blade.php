@extends('layouts.master')


@section('content')
<style>
    body {
        background: #f5f6fa;
        font-family: 'Segoe UI', sans-serif;
    }

    .report-container {
        max-width: 2000px;
        margin: 40px auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        padding: 30px;
    }

    h2 {
        font-size: 26px;
        color: #222;
        font-weight: 700;
        margin-bottom: 25px;
    }

    /* Summary cards */
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
    }

    .summary-card {
        border-radius: 12px;
        padding: 20px;
        color: #fff;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        transition: transform 0.2s;
    }

    .summary-card:hover {
        transform: translateY(-4px);
    }

    .summary-card h3 {
        font-size: 15px;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .summary-card span {
        font-size: 22px;
        font-weight: 700;
    }

    .summary-buffalo { background: #f39c12; }
    .summary-ponewine { background: #3498db; }
    .summary-shan { background: #2ecc71; }
    .summary-slot { background: #e74c3c; }

    /* Filter form */
    form.filter-form {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: flex-end;
        margin-bottom: 30px;
    }

    form.filter-form label {
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
        display: block;
    }

    form.filter-form input {
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
    }

    form.filter-form button {
        background: #007bff;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    form.filter-form button:hover {
        background: #0056b3;
    }

    /* Tables */
    .card {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        margin-bottom: 25px;
        background: #fff;
    }

    .card-header {
        padding: 12px 18px;
        font-weight: 600;
        color: #fff;
    }

    .card-header.blue { background: #3498db; }
    .card-header.green { background: #2ecc71; }
    .card-header.orange { background: #f39c12; }
    .card-header.red { background: #e74c3c; }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table thead {
        background: #f2f2f2;
    }

    table th, table td {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        text-align: left;
        font-size: 14px;
    }

    table tr:hover {
        background: #fafafa;
    }

    .no-record {
        text-align: center;
        color: #888;
        padding: 12px 0;
    }
</style>

<div class="report-container">
    <h2>🎯 Player Report</h2>

    {{-- 💰 Summary Cards --}}
    <div class="summary-cards">
        <div class="summary-card summary-buffalo">
            <h3>Buffalo Total</h3>
            <span>{{ number_format($data['buffalo']['net'], 2) }}</span>
        </div>
        <div class="summary-card summary-ponewine">
            <h3>Pone Wine Total</h3>
            <span>{{ number_format($data['ponewine']['net'], 2) }}</span>
        </div>
        <div class="summary-card summary-shan">
            <h3>Shan Total</h3>
            <span>{{ number_format($data['shan']['net'], 2) }}</span>
        </div>
        <div class="summary-card summary-slot">
            <h3>Slot Total</h3>
            <span>{{ number_format($data['slot']['net'], 2) }}</span>
        </div>
          <div class="summary-card summary-slot">
            <h3>All Total</h3>
            <span>{{ number_format($data['total']['totalNetWin'], 2) }}</span>
        </div>
    </div>

    {{-- 🔍 Date Filter --}}
    <form method="GET" action="" class="filter-form">
        <div>
            <label for="from_date">From Date</label>
            <input type="date" id="from_date" name="from_date"
                   value="{{ request('from_date', now()->format('Y-m-d')) }}">
        </div>
        <div>
            <label for="to_date">To Date</label>
            <input type="date" id="to_date" name="to_date"
                   value="{{ request('to_date', now()->format('Y-m-d')) }}">
        </div>
        <button type="submit">Apply Filter</button>
    </form>

    {{-- 🧾 Table 1 --}}
    <div class="card">
        <div class="card-header blue">🍷 Pone Wine Transactions</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>BetAmount</th>
                    <th>WinAmount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($poneWines as $index => $wine)
                    <tr>
                        <td>{{ ($poneWines->currentPage() - 1) * $poneWines->perPage() + $index + 1 }}</td>
                        <td>{{ $wine->bet_amount }}</td>
                        <td>{{ $wine->win_lose_amount }}</td>
                        <td>{{ $wine->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="no-record">No records found</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
          {{ $poneWines->appends([
    'from_date' => request('from_date'),
    'to_date' => request('to_date'),
    'shan_page' => request('shan_page'),
    'buffalo_page' => request('buffalo_page'),
    'slot_page' => request('slot_page'),
])->links('pagination::bootstrap-5') }}

        </div>
    </div>

    {{-- 🧾 Table 2 --}}
    <div class="card">
        <div class="card-header green">🍀 Shan Transactions</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bet Amount</th>
                    <th>Win Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shans as $index => $shan)
                    <tr>
                        <td>{{ ($shans->currentPage() - 1) * $shans->perPage() + $index + 1 }}</td>
                        <td>{{ $shan->bet_amount }}</td>
                        <td>{{ $shan->valid_amount }}</td>
                        <td>{{ $shan->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="no-record">No records found</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
           {{ $shans->appends([
    'from_date' => request('from_date'),
    'to_date' => request('to_date'),
    'pone_wine_page' => request('pone_wine_page'),
    'buffalo_page' => request('buffalo_page'),
    'slot_page' => request('slot_page'),
])->links('pagination::bootstrap-5') }}
        </div>
    </div>

    {{-- 🧾 Table 3 --}}
    <div class="card">
        <div class="card-header orange">🐃 Buffalo Bets</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bet Amount</th>
                    <th>Win Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($buffalos as $index => $buffalo)
                    <tr>
                        <td>{{  ($buffalos->currentPage() - 1) * $buffalos->perPage() + $index + 1 }}</td>
                        <td>{{ $buffalo->bet_amount }}</td>
                        <td>{{ $buffalo->win_amount }}</td>
                        <td>{{ $buffalo->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="no-record">No records found</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
          {{ $buffalos->appends([
    'from_date' => request('from_date'),
    'to_date' => request('to_date'),
    'pone_wine_page' => request('pone_wine_page'),
    'shan_page' => request('shan_page'),
    'slot_page' => request('slot_page'),
])->links('pagination::bootstrap-5') }}
        </div>
    </div>

    {{-- 🧾 Table 4 --}}
    <div class="card">
        <div class="card-header red">🎰 Slot Bets</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bet Amount</th>
                    <th>Win Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $index => $slot)
                    <tr>
                        <td>{{  ($slots->currentPage() - 1) * $slots->perPage() + $index + 1 }}</td>
                        <td>{{ $slot->bet_amount }}</td>
                        <td>{{ $slot->valid_bet_amount }}</td>
                        <td>{{ $slot->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="no-record">No records found</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
          {{ $slots->appends([
    'from_date' => request('from_date'),
    'to_date' => request('to_date'),
    'pone_wine_page' => request('pone_wine_page'),
    'shan_page' => request('shan_page'),
    'buffalo_page' => request('buffalo_page'),
])->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
