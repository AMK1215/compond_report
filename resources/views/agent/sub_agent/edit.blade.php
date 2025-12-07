@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('agent.sub-agent.index') }}">Sub-Agents</a></li>
                        <li class="breadcrumb-item active">Edit</li>
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
                        <h5 class="d-inline fw-bold">Edit Sub-Agent</h5>
                        <a href="{{ route('agent.sub-agent.index') }}" class="btn btn-primary d-inline float-right">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </a>
                    </div>
                </div>

                <form action="{{ route('agent.sub-agent.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body mt-2">
                        <div class="form-group">
                            <label>Agent ID</label>
                            <input type="text" class="form-control" value="{{ $user->user_name }}" readonly>
                        </div>

                        <div class="form-group">
                            <label>Referral Code<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="referral_code" 
                                value="{{ old('referral_code', $user->referral_code) }}">
                            @error('referral_code')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" 
                                value="{{ old('name', $user->name) }}">
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Phone<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" 
                                value="{{ old('phone', $user->phone) }}">
                            @error('phone')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer col-12 bg-white">
                        <button type="submit" class="btn btn-success float-right">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

