@extends('layouts.master')

@section('title', 'Buffalo RTP Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Buffalo RTP Settings</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Buffalo RTP</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Update RTP</h5>
                    <p class="text-muted mb-4">
                        This action sends the RTP update to the Buffalo provider and stores the latest value on this system.
                        Provide the RTP as a decimal (e.g. <code>0.95</code> for 95%).
                    </p>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <dl class="row mb-4">
                        <dt class="col-sm-4">Current RTP</dt>
                        <dd class="col-sm-8 fw-bold">{{ number_format($currentRtp, 2) }}</dd>

                        <dt class="col-sm-4">Last Updated</dt>
                        <dd class="col-sm-8">
                            {{ $lastUpdatedAt?->toDayDateTimeString() ?? 'Never' }}
                        </dd>

                        <dt class="col-sm-4">Provider Endpoint</dt>
                        <dd class="col-sm-8">
                            {{ rtrim(config('new_buffalo_key.buffalo_api_url'), '/') }}/ws/rtp
                        </dd>
                    </dl>

                    <form method="POST" action="{{ route('admin.buffalo.rtp.update') }}" class="needs-validation" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="rtp" class="form-label">RTP (0.00 - 1.00 or 0 - 100%)</label>
                            <input
                                type="number"
                                name="rtp"
                                id="rtp"
                                class="form-control @error('rtp') is-invalid @enderror"
                                step="0.01"
                                min="0"
                                max="100"
                                value="{{ old('rtp', number_format($currentRtp, 2, '.', '')) }}"
                                required
                            >
                            <div class="form-text">Example: enter <strong>0.95</strong> or <strong>95</strong> for 95% RTP.</div>
                            @error('rtp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-transfer-alt me-1"></i> Send Update
                            </button>
                            <small class="text-muted">
                                Signature generated automatically using <code>update_rtp + requestTime + secretKey</code>.
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

