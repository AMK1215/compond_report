@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('agent.sub-agent.index') }}">Sub-Agents</a></li>
                        <li class="breadcrumb-item active">Deposit</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card col-lg-6 offset-lg-3 col-md-8 offset-md-2" style="border-radius: 15px;">
                <div class="card-header">
                    <div class="card-title col-12">
                        <h5 class="d-inline fw-bold">Deposit to {{ $agent->name }}</h5>
                        <a href="{{ route('agent.sub-agent.index') }}" class="btn btn-primary d-inline float-right">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </a>
                    </div>
                </div>

                <form action="{{ route('agent.sub-agent.makeCashIn', $agent->id) }}" method="POST">
                    @csrf
                    <div class="card-body mt-2">
                        <div class="form-group">
                            <label>Agent Name</label>
                            <input type="text" class="form-control" value="{{ $agent->name }}" readonly>
                        </div>

                        <div class="form-group">
                            <label>Current Balance</label>
                            <input type="text" class="form-control" value="{{ number_format($agent->balanceFloat, 2) }}" readonly>
                        </div>

                        <div class="form-group">
                            <label>Amount<span class="text-danger">*</span></label>
                            <span class="badge badge-success">
                                My Balance: {{ number_format(auth()->user()->balanceFloat, 2) }}
                            </span>
                            <input type="number" step="0.01" class="form-control" name="amount" 
                                value="{{ old('amount') }}" placeholder="Enter amount">
                            @error('amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer col-12 bg-white">
                        <button type="submit" class="btn btn-success float-right">Deposit</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

