@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Player lists</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Players</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('agent.player.create') }}" class="btn btn-success" style="width: 120px;">
                            <i class="fas fa-plus text-white mr-2"></i>Create
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <table id="mytable" class="table table-bordered table-hover">
                                <thead class="text-center">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Balance</th>
                                        <th>Action</th>
                                        <th>Transfer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $user)
                                        <tr class="text-center">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->user_name }}</td>
                                            <td>{{ $user->phone }}</td>
                                            <td>
                                                <small
                                                    class="badge bg-gradient-{{ $user->status == 1 ? 'success' : 'danger' }}">
                                                    {{ $user->status == 1 ? 'Active' : 'Inactive' }}
                                                </small>
                                            </td>
                                            <td>{{ number_format($user->balanceFloat, 2) }}</td>
                                            <td>
                                                @if ($user->status == 1)
                                                    <a onclick="event.preventDefault(); document.getElementById('banUser-{{ $user->id }}').submit();"
                                                        class="me-2" href="#" data-bs-toggle="tooltip"
                                                        title="Deactivate Player">
                                                        <i class="fas fa-user-check text-success"
                                                            style="font-size: 20px;"></i>
                                                    </a>
                                                @else
                                                    <a onclick="event.preventDefault(); document.getElementById('banUser-{{ $user->id }}').submit();"
                                                        class="me-2" href="#" data-bs-toggle="tooltip"
                                                        title="Activate Player">
                                                        <i class="fas fa-user-slash text-danger"
                                                            style="font-size: 20px;"></i>
                                                    </a>
                                                @endif
                                                <form class="d-none" id="banUser-{{ $user->id }}"
                                                    action="{{ route('agent.player.ban', $user->id) }}" method="post">
                                                    @csrf
                                                    @method('PUT')
                                                </form>

                                                <a class="me-1"
                                                    href="{{ route('agent.player.getChangePassword', $user->id) }}"
                                                    data-bs-toggle="tooltip" title="Change Password">
                                                    <i class="fas fa-lock text-info" style="font-size: 20px;"></i>
                                                </a>

                                                <a class="me-1" href="{{ route('agent.player.edit', $user->id) }}"
                                                    data-bs-toggle="tooltip" title="Edit Player">
                                                    <i class="fas fa-edit text-info" style="font-size: 20px;"></i>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="{{ route('agent.player.getCashIn', $user->id) }}"
                                                    class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="Deposit">
                                                    <i class="fas fa-plus text-white mr-1"></i>Deposit
                                                </a>

                                                <a href="{{ route('agent.player.getCashOut', $user->id) }}"
                                                    class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="Withdraw">
                                                    <i class="fas fa-minus text-white mr-1"></i>Withdraw
                                                </a>

                                                <a href="{{ route('admin.player.game_report.index', $user->id) }}"
                                                    class="btn btn-info btn-sm mt-1" data-bs-toggle="tooltip"
                                                    title="Logs">
                                                    <i class="fas fa-right-left text-white mr-1"></i>Reports
                                                </a>

                                                <a href="{{ route('agent.player.logs', $user->id) }}"
                                                    class="btn btn-info btn-sm mt-1" data-bs-toggle="tooltip"
                                                    title="Logs">
                                                    <i class="fas fa-right-left text-white mr-1"></i>Logs
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No players found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <div class="d-flex justify-content-center mt-3">
                                {{ $users->links() }}
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="credentialsModal" tabindex="-1" role="dialog"
                    aria-labelledby="credentialsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="credentialsModalLabel">Player Created Successfully 🎉</h5>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body text-center">
                                <p><strong>Username:</strong> <span id="modal-username"></span></p>
                                <p><strong>Password:</strong> <span id="modal-password"></span></p>
                                <p><strong>Amount:</strong> <span id="modal-amount"></span></p>
                                <p><strong>URL:</strong> <a href="#" id="modal-url" target="_blank"></a></p>
                                <p><strong>App Link:</strong> <a href="#" id="modal-appLink"></a></p>
                               <button class="btn btn-primary mt-3" id="copyCredentialsBtn">
                                    <i class="fas fa-copy mr-1"></i> Copy
                                </button>
                                <button type="button" class="btn btn-secondary mt-3" data-dismiss="modal">
                                    <i class="fas fa-times mr-1"></i> Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var successMessage = @json(session('successMessage'));
            var username = @json(session('username'));
            var password = @json(session('password'));
            var amount = @json(session('amount'));
            var link = @json(session('link'));
            var appLink = @json(session('appLink'));


            @if (session()->has('successMessage'))
                toastr.success(successMessage);

                $('#modal-username').text(username);
                $('#modal-password').text(password);
                $('#modal-amount').text(amount);
                $('#modal-url').text(link).attr('href', link);
                $('#modal-appLink').text(appLink).attr('href', appLink);


                $('#credentialsModal').modal('show');
            @endif

            // Copy button
            $('#copyCredentialsBtn').on('click', function() {
                var textToCopy =
                    "Username: " + username + "\n" +
                    "Password: " + password + "\n" +
                    "Amount: " + amount + "\n" +
                    "URL: " + link + "\n" +
                    "App Link: " + appLink;

                navigator.clipboard.writeText(textToCopy)
                    .then(() => toastr.success("✅ Credentials copied to clipboard!"))
                    .catch(err => toastr.error("❌ Failed to copy: " + err));
            });
        });
    </script>
@endsection
