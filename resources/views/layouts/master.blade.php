<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>6tribet | Login</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo/6tribet.jpg') }}">

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/jqvmap/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/summernote/summernote-bs4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">

    {{-- @vite(['resources/js/app.js']) --}}


    <style>
        .dropdown-menu {
            z-index: 1050 !important;
        }
    </style>

    @yield('style')


</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">



        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light sticky-top">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('home') }}" class="nav-link">Home</a>
                </li>
            </ul>



            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">

                <!--begin::Messages Dropdown Menu-->

                <!--end::Messages Dropdown Menu-->
                <li class="nav-item">
                    <a class="nav-link"
                        href="{{ route('admin.changePassword', \Illuminate\Support\Facades\Auth::id()) }}">
                        {{ auth()->user()->name }}
                        @if (auth()->user()->referral_code)
                            | {{ auth()->user()->referral_code }}
                        @endif
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        | Balance: {{ number_format(auth()->user()->wallet->balanceFloat, 2) }}
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link" href="#"
                        onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>

                </li>

            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
             <a href="{{ route('home') }}" class="brand-link">
            <img src="{{ asset('img/logo/6tribet.jpg') }}" alt="AdminLTE Logo"
                class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">6tribet </span>

            </a>



            <!-- Sidebar -->
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-item menu-open">
                            <a href="{{ route('home') }}"
                                class="nav-link {{ Route::current()->getName() == 'home' ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>
                                    Dashboard
                                </p>
                            </a>
                        </li>

                        {{-- Master List removed - route does not exist --}}

                        {{-- Agent List - Only for Owners --}}
                        @can('owner_access')
                            <li class="nav-item">
                                <a href="{{ route('admin.agent.index') }}"
                                    class="nav-link {{ Route::current()->getName() == 'admin.agent.index' ? 'active' : '' }}">
                                    <i class="fas fa-users"></i>
                                    <p>
                                        Agent List
                                    </p>
                                </a>
                            </li>
                        @endcan

                        {{-- Agent-specific menus for managing sub-agents and players --}}
                        @if(Auth::user()->hasRole('Agent'))
                            <li class="nav-item">
                                <a href="{{ route('agent.sub-agent.index') }}"
                                    class="nav-link {{ request()->is('agent/sub-agent*') ? 'active' : '' }}">
                                    <i class="fas fa-user-tie"></i>
                                    <p>
                                        Agent Lists
                                    </p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('agent.player.index') }}"
                                    class="nav-link {{ request()->is('agent/player*') ? 'active' : '' }}">
                                    <i class="fas fa-users"></i>
                                    <p>
                                        Player Lists
                                    </p>
                                </a>
                            </li>
                        @endif

                        {{-- Player List - Only for Owners --}}
                        {{-- @can('owner_access')
                            <li class="nav-item">
                                <a href="{{ route('admin.playerListForAdmin') }}"
                                    class="nav-link {{ Route::current()->getName() == 'admin.playerListForAdmin' ? 'active' : '' }}">
                                    <i class="far fa-user"></i>
                                    <p>
                                        Player List
                                    </p>
                                </a>
                            </li>
                        @endcan --}}
                        {{-- @can('contact')
                            <li class="nav-item">
                                <a href="{{ route('admin.contact.index') }}"
                                    class="nav-link {{ Route::current()->getName() == 'admin.contact.index' ? 'active' : '' }}">
                                    <i class="fas fa-address-book"></i>
                                    <p>
                                        Contact
                                    </p>
                                </a>
                            </li>
                        @endcan --}}
                        {{-- Bank removed - route does not exist --}}
                        {{-- Withdraw/Deposit Requests removed - routes do not exist --}}
                        @can('transfer_log')
                        <li class="nav-item">
                            <a href="{{ route('admin.transfer-logs.index') }}"
                                class="nav-link {{ Route::current()->getName() == 'admin.transfer-logs.index' ? 'active' : '' }}">
                                <i class="fas fa-exchange-alt"></i>
                                <p>
                                    Transaction Log
                                </p>
                            </a>
                        </li>
                        @endcan

                        {{-- Sub Agent Account removed - route does not exist --}}

                        @can('agent_access')
                            <li class="nav-item">
                            <a href="{{ route('admin.reports.daily_win_loss') }}"
                                class="nav-link {{ Route::currentRouteName() == 'admin.reports.daily_win_loss' ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>
                                    Daily Win/Loss
                                </p>
                            </a>
                        </li>
                        @endcan
                        {{-- Sub Agent sections removed - routes do not exist --}}
                        @can('owner_access')
                           <li
                                class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-tools"></i>
                                    <p>
                                        GSCPLUS Settings
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.gameLists.index') }}"
                                            class="nav-link {{ Route::current()->getName() == 'admin.gameLists.index' ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>GSCPLUS GameList</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.gametypes.index') }}"
                                            class="nav-link {{ Route::current()->getName() == 'admin.gametypes.index' ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>GSCPLUS Provider</p>
                                        </a>
                                    </li>

                                     <!-- <li class="nav-item">
                                        <a href=""
                                            class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>GSC GameType</p>
                                        </a>
                                    </li>  -->
                                </ul>
                            </li>
                        @endcan

                         <!-- 2D -->

                        {{-- @can('owner_access')
                            <li
                                class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-tools"></i>
                                    <p>
                                        2D
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.twod.settings') }}"
                                            class="nav-link {{ Route::current()->getName() == 'admin.twod.settings' ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>2D Settings</p>
                                        </a>
                                    </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.twod.bet-slip-list') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.twod.bet-slip-list' ? 'active' : '' }}">
                                                <i class="fas fa-list-alt nav-icon"></i>
                                                <p>2D Bet Slip List</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.twod.daily-ledger') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.twod.daily-ledger' ? 'active' : '' }}">
                                                <i class="fas fa-book nav-icon"></i>
                                                <p>2D Daily Ledger</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.twod.daily-winners') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.twod.daily-winners' ? 'active' : '' }}">
                                                <i class="fas fa-trophy nav-icon"></i>
                                                <p>2D Daily Winners</p>
                                            </a>
                                        </li>


                                </ul>
                            </li>
                        @endcan --}}


                        <!-- agent 2d -->
                        {{-- @can('agent_access')
                            <li
                                class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-tools"></i>
                                    <p>
                                        2D
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">


                                <li class="nav-item">
                                            <a href="{{ route('admin.twod.bet-slip-list') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.twod.bet-slip-list' ? 'active' : '' }}">
                                                <i class="fas fa-list-alt nav-icon"></i>
                                                <p>2D Bet Slip List</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.twod.daily-ledger') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.twod.daily-ledger' ? 'active' : '' }}">
                                                <i class="fas fa-book nav-icon"></i>
                                                <p>2D Daily Ledger</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.twod.daily-winners') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.twod.daily-winners' ? 'active' : '' }}">
                                                <i class="fas fa-trophy nav-icon"></i>
                                                <p>2D Daily Winners</p>
                                            </a>
                                        </li>
                                </ul>
                            </li>
                        @endcan --}}

                        <!-- agent 2d -->

                        <!-- 3D -->

                        {{-- @can('owner_access')
                            <li
                                class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-cube"></i>
                                    <p>
                                        3D
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.threed.settings') }}"
                                            class="nav-link {{ Route::current()->getName() == 'admin.threed.settings' ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>3D Settings</p>
                                        </a>
                                    </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.threed.bet-slip-list') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.threed.bet-slip-list' ? 'active' : '' }}">
                                                <i class="fas fa-list-alt nav-icon"></i>
                                                <p>3D Bet Slip List</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.threed.daily-ledger') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.threed.daily-ledger' ? 'active' : '' }}">
                                                <i class="fas fa-book nav-icon"></i>
                                                <p>3D Daily Ledger</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.threed.daily-winners') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.threed.daily-winners' ? 'active' : '' }}">
                                                <i class="fas fa-trophy nav-icon"></i>
                                                <p>3D Daily Winners</p>
                                            </a>
                                        </li>


                                </ul>
                            </li>
                        @endcan --}}


                        <!-- agent 3d -->
                        {{-- @can('agent_access')
                            <li
                                class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-cube"></i>
                                    <p>
                                        3D
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">


                                <li class="nav-item">
                                            <a href="{{ route('admin.threed.bet-slip-list') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.threed.bet-slip-list' ? 'active' : '' }}">
                                                <i class="fas fa-list-alt nav-icon"></i>
                                                <p>3D Bet Slip List</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.threed.daily-ledger') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.threed.daily-ledger' ? 'active' : '' }}">
                                                <i class="fas fa-book nav-icon"></i>
                                                <p>3D Daily Ledger</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.threed.daily-winners') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.threed.daily-winners' ? 'active' : '' }}">
                                                <i class="fas fa-trophy nav-icon"></i>
                                                <p>3D Daily Winners</p>
                                            </a>
                                        </li>
                                </ul>
                            </li>
                        @endcan --}}

                        <!-- agent 3d -->

                        {{-- Reports Section - Consolidated to remove duplicates --}}
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fas fa-file-invoice"></i>
                                <p>
                                    Reports
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                {{-- Win/Lose Report --}}
                                @can('report_check')
                                <li class="nav-item">
                                    <a href="{{ route('admin.report.index') }}"
                                        class="nav-link {{ Route::current()->getName() == 'admin.report.index' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Win/Lose Report</p>
                                    </a>
                                </li>
                                @endcan

                                {{-- PoneWine Report --}}
                                @can('report_check')
                                <li class="nav-item">
                                    <a href="{{ route('admin.ponewine.report.index') }}"
                                        class="nav-link {{ Route::current()->getName() == 'admin.ponewine.report.index' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>PoneWine Report</p>
                                    </a>
                                </li>
                                @endcan

                                {{-- Buffalo Game Report --}}
                                @can('report_check')
                              {{--  <li class="nav-item">
                                    <a href="{{ route('admin.buffalo-report.index') }}"
                                        class="nav-link {{ Route::current()->getName() == 'admin.buffalo-report.index' || Route::current()->getName() == 'admin.buffalo-report.show' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Buffalo Game Report</p>
                                    </a>
                                </li> --}}

                                 <li class="nav-item">
                                    <a href="{{ route('admin.new_buffalo_report.index') }}"
                                        class="nav-link {{ Route::current()->getName() == 'admin.new_buffalo_report.index' || Route::current()->getName() == 'admin.new_buffalo_report.index' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Buffalo Game Report</p>
                                    </a>
                                </li>
                                @endcan

                                {{-- Shan Player Report --}}
                                @can('report_check')
                                <li class="nav-item">
                                    <a href="{{ route('admin.shan.player.report') }}"
                                        class="nav-link {{ Route::current()->getName() == 'admin.shan.player.report' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Shan Player Report</p>
                                    </a>
                                </li>
                                @endcan



                                   <li class="nav-item">
                                    <a href="{{ route('admin.game_report.index') }}"
                                        class="nav-link {{ Route::current()->getName() == 'admin.game_report.index' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>All Game Report</p>
                                    </a>
                                </li>

                                {{-- Combined Game Report @if(Auth::user()->type === '10' || Auth::user()->type === '20')
                                <li class="nav-item">
                                    <a href="{{ route('admin.reports.combined_game_report') }}"
                                        class="nav-link {{ Route::currentRouteName() == 'admin.reports.combined_game_report' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Combined Game Report</p>
                                    </a>
                                </li>
                                @endif --}}


                                {{-- Shan Report Transactions --}}
                                {{-- @can('report_check')
                                <li class="nav-item">
                                    <a href="{{ route('admin.shan.report.transactions') }}"
                                        class="nav-link {{ Route::current()->getName() == 'admin.shan.report.transactions' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Shan Report Transactions</p>
                                    </a>
                                </li>
                                @endcan --}}
                            </ul>
                        </li>
                        @can('owner_access')
                        <li class="nav-item {{ in_array(Route::currentRouteName(), ['admin.text.index', 'admin.banners.index', 'admin.adsbanners.index', 'admin.promotions.index', 'admin.buffalo.rtp.index']) ? 'menu-open' : '' }}">
    <a href="#" class="nav-link">
        <i class="fas fa-tools"></i>
        <p>
            General Settings
            <i class="fas fa-angle-left right"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.buffalo.rtp.index') }}"
               class="nav-link {{ Route::currentRouteName() == 'admin.buffalo.rtp.index' ? 'active' : '' }}">
                <i class="fas fa-sliders-h nav-icon"></i>
                <p>Buffalo RTP</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('admin.video-upload.index') }}"
               class="nav-link {{ Route::current()->getName() == 'admin.video-upload.index' ? 'active' : '' }}">
                <i class="fas fa-video nav-icon"></i>
                <p>AdsVideo</p>
            </a>
        </li>

        {{-- <li class="nav-item">
            <a href="{{ route('admin.winner_text.index') }}"
               class="nav-link {{ Route::current()->getName() == 'admin.winner_text.index' ? 'active' : '' }}">
                <i class="fas fa-trophy nav-icon"></i>
                <p>WinnerText</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('admin.top-10-withdraws.index') }}"
               class="nav-link {{ Route::current()->getName() == 'admin.top-10-withdraws.index' ? 'active' : '' }}">
                <i class="fas fa-list-ol nav-icon"></i>
                <p>WithdrawTopTen</p>
            </a>
        </li> --}}

        <li class="nav-item">
            <a href="{{ route('admin.text.index') }}"
               class="nav-link {{ Route::current()->getName() == 'admin.text.index' ? 'active' : '' }}">
                <i class="fas fa-font nav-icon"></i>
                <p>BannerText</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('admin.banners.index') }}"
               class="nav-link {{ Route::current()->getName() == 'admin.banners.index' ? 'active' : '' }}">
                <i class="fas fa-image nav-icon"></i>
                <p>Banner</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('admin.adsbanners.index') }}"
               class="nav-link {{ Route::current()->getName() == 'admin.adsbanners.index' ? 'active' : '' }}">
                <i class="fas fa-ad nav-icon"></i>
                <p>Banner Ads</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('admin.promotions.index') }}"
               class="nav-link {{ Route::current()->getName() == 'admin.promotions.index' ? 'active' : '' }}">
                <i class="fas fa-bullhorn nav-icon"></i>
                <p>Promotions</p>
            </a>
        </li>
    </ul>
</li>
@endcan
    </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <div class="content-wrapper">

            @yield('content')
        </div>
        <footer class="main-footer">
            <strong>Copyright &copy; 2025 <a href="">6TriBet</a>.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Version</b> 3.2.2
            </div>
        </footer>

        <aside class="control-sidebar control-sidebar-dark">
        </aside>
    </div>

    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        // $.widget.bridge('uibutton', $.ui.button)

        // Setup CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('plugins/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <script src="{{ asset('js/adminlte.js') }}"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>

    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

    @yield('script')
    <script>
        var errorMessage = @json(session('error'));
        var successMessage = @json(session('success'));

        @if (session()->has('success'))
            toastr.success(successMessage)
        @elseif (session()->has('error'))
            toastr.error(errorMessage)
        @endif
    </script>
    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });
            $('#ponewineTable').DataTable();
            $('#slotTable').DataTable();

            $("#mytable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "order": true,
                "pageLength": 10,
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl)
            })
        });
    </script>



</body>

</html>
