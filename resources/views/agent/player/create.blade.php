@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('agent.player.index') }}">Players</a></li>
                        <li class="breadcrumb-item active">Create</li>
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
                        <h5 class="d-inline fw-bold">Create Player</h5>
                        <a href="{{ route('agent.player.index') }}" class="btn btn-primary d-inline float-right">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </a>
                    </div>
                </div>

                <form action="{{ route('agent.player.store') }}" method="POST">
                    @csrf
                    <div class="card-body mt-2">
                        <div class="form-group">
                            <label>Player ID<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="user_name" value="{{ $player_name }}" readonly>
                            @error('user_name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}">
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Phone<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Password<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="password" value="{{ old('password') }}">
                            @error('password')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Initial Amount</label>
                            <span class="badge badge-success">
                                My Balance: {{ number_format(auth()->user()->balanceFloat, 2) }}
                            </span>
                            <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount') }}">
                            @error('amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer col-12 bg-white">
                        <button type="submit" class="btn btn-success float-right">Create Player</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

