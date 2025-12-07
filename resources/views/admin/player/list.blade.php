@extends('layouts.master')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Player List</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Player</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @forelse ($agents as $agent)
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user-tie"></i> 
                                            Agent: {{ $agent->name }} ({{ $agent->user_name }})
                                        </h5>
                                        <button class="btn btn-sm btn-light" type="button" 
                                            data-toggle="collapse" 
                                            data-target="#agent-{{ $agent->id }}" 
                                            aria-expanded="false">
                                            <i class="fas fa-chevron-down"></i> View Players ({{ $agent->players->count() }})
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="collapse" id="agent-{{ $agent->id }}">
                                    <div class="card-body p-0">
                                        <table class="table table-bordered table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Player Name</th>
                                                    <th>PlayerId</th>
                                                    <th>Phone</th>
                                                    <th>Status</th>
                                                    <th>Balance</th>
                                                    <th>CreatedAt</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($agent->players as $player)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $player->name }}</td>
                                                    <td>{{ $player->user_name }}</td>
                                                    <td>{{ $player->phone }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $player->status == 1 ? 'success' : 'danger' }}">
                                                            {{ $player->status == 1 ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ number_format($player->balanceFloat) }}</td>
                                                    <td>{{ $player->created_at->timezone('Asia/Yangon')->format('d-m-Y H:i:s') }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">No players found for this agent.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No agents with players found.
                            </div>
                        @endforelse
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
    </div>
</section>
@endsection