@extends('layouts.master')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Shan Report Transactions</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Shan Report Transactions</li>
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
                    <div class="card-header">
                        <h3 class="card-title">Filter Options</h3>
                    </div>
                    <div class="card-body">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="agent_code">Agent Code *</label>
                                        <input type="text" id="agent_code" name="agent_code" class="form-control" 
                                               placeholder="Enter agent code (e.g., SCT931)" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="date_from">Date From</label>
                                        <input type="date" id="date_from" name="date_from" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="date_to">Date To</label>
                                        <input type="date" id="date_to" name="date_to" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="member_account">Member Account</label>
                                        <input type="text" id="member_account" name="member_account" class="form-control" 
                                               placeholder="Enter member account">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="group_by">Group By</label>
                                        <select id="group_by" name="group_by" class="form-control">
                                            <option value="both">Both (Agent & Member)</option>
                                            <option value="agent_id">Agent Only</option>
                                            <option value="member_account">Member Only</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" id="fetchData" class="btn btn-primary btn-block">Fetch</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Loading indicator -->
                <div id="loading" class="text-center" style="display:none;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Loading data from external API...</p>
                </div>

                <!-- Error message -->
                <div id="error-message" class="alert alert-danger" style="display:none;"></div>

                <!-- API Response Status -->
                <div id="api-status" class="alert" style="display:none;"></div>

                <!-- Agent Info Card -->
                <div id="agent-info-card" class="card" style="display:none;">
                    <div class="card-header">
                        <h3 class="card-title">Agent Information</h3>
                    </div>
                    <div class="card-body">
                        <div id="agent-info-content"></div>
                    </div>
                </div>

                <!-- Summary Card -->
                <div id="summary-card" class="card" style="display:none;">
                    <div class="card-header">
                        <h3 class="card-title">Summary</h3>
                    </div>
                    <div class="card-body">
                        <div id="summary-content"></div>
                    </div>
                </div>

                <!-- Report Data Card -->
                <div id="report-card" class="card" style="display:none;">
                    <div class="card-header">
                        <h3 class="card-title">Report Data</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="report-table" class="table table-bordered table-hover">
                                <thead id="report-table-head">
                                    <!-- Dynamic headers will be inserted here -->
                                </thead>
                                <tbody id="report-table-body">
                                    <!-- Dynamic data will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Member Transactions Section -->
                <div id="member-transactions-section" class="card" style="display:none;">
                    <div class="card-header">
                        <h3 class="card-title">Member Transactions</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Member Transactions Filter -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="member_transactions_agent_code">Agent Code *</label>
                                    <input type="text" id="member_transactions_agent_code" class="form-control" 
                                           placeholder="Enter agent code (e.g., SCT931)" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="member_transactions_member_account">Member Account *</label>
                                    <input type="text" id="member_transactions_member_account" class="form-control" 
                                           placeholder="Enter member account" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="member_transactions_date_from">Date From</label>
                                    <input type="date" id="member_transactions_date_from" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="member_transactions_date_to">Date To</label>
                                    <input type="date" id="member_transactions_date_to" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label for="member_transactions_limit">Limit</label>
                                    <select id="member_transactions_limit" class="form-control">
                                        <option value="10">10</option>
                                        <option value="20" selected>20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="button" id="fetchMemberTransactions" class="btn btn-success btn-block">Fetch</button>
                                </div>
                            </div>
                        </div>

                        <!-- Member Transactions Loading -->
                        <div id="member-loading" class="text-center" style="display:none;">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p>Loading member transactions...</p>
                        </div>

                        <!-- Member Transactions Error -->
                        <div id="member-error-message" class="alert alert-danger" style="display:none;"></div>

                        <!-- Member Transactions API Status -->
                        <div id="member-api-status" class="alert" style="display:none;"></div>

                        <!-- Member Transactions Data -->
                        <div id="member-transactions-data" style="display:none;">
                            <!-- Member Info -->
                            <div id="member-info-card" class="card mb-3">
                                <div class="card-header">
                                    <h4 class="card-title">Member Information</h4>
                                </div>
                                <div class="card-body">
                                    <div id="member-info-content"></div>
                                </div>
                            </div>

                            <!-- Member Transactions Table -->
                            <div class="table-responsive">
                                <table id="member-transactions-table" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User ID</th>
                                            <th>Transaction Amount</th>
                                            <th>Bet Amount</th>
                                            <th>Valid Amount</th>
                                            <th>Status</th>
                                            <th>Banker</th>
                                            <th>Before Balance</th>
                                            <th>After Balance</th>
                                            <th>Settled Status</th>
                                            <th>Wager Code</th>
                                            <th>Agent Code</th>
                                            <th>Created At</th>
                                            <th>Updated At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="member-transactions-table-body">
                                        <!-- Dynamic data will be inserted here -->
                                    </tbody>
                                </table>
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
$(document).ready(function() {
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    $('#date_to').val(today.toISOString().split('T')[0]);
    $('#date_from').val(thirtyDaysAgo.toISOString().split('T')[0]);

    // Handle form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        fetchReportData();
    });

    // Handle member transactions fetch
    $('#fetchMemberTransactions').on('click', function() {
        fetchMemberTransactions();
    });

    // Set default dates for member transactions
    $('#member_transactions_date_to').val(today.toISOString().split('T')[0]);
    $('#member_transactions_date_from').val(thirtyDaysAgo.toISOString().split('T')[0]);

    function fetchReportData() {
        const formData = {
            agent_code: $('#agent_code').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val(),
            member_account: $('#member_account').val(),
            group_by: $('#group_by').val()
        };

        // Validate required fields
        if (!formData.agent_code) {
            showError('Please enter an agent code.');
            return;
        }

        // Show loading
        showLoading(true);
        hideAllCards();

        $.ajax({
            url: '{{ route("admin.shan.report.transactions.fetch") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    displayApiResponse(response.data);
                } else {
                    showError(response.message || 'Failed to fetch data');
                }
            },
            error: function(xhr) {
                let message = 'An error occurred while fetching data.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showError(message);
            },
            complete: function() {
                showLoading(false);
            }
        });
    }

    function displayApiResponse(apiData) {
        // Display API status
        displayApiStatus(apiData.status, apiData.message);
        
        // Display agent info
        if (apiData.data && apiData.data.agent_info) {
            displayAgentInfo(apiData.data.agent_info);
        }
        
        // Display summary
        if (apiData.data && apiData.data.summary) {
            displaySummary(apiData.data.summary);
        }
        
        // Display report data
        if (apiData.data && apiData.data.report_data) {
            displayReportTable(apiData.data.report_data, apiData.data.filters.group_by);
        }
    }

    function displayApiStatus(status, message) {
        const statusClass = status === 'Request was successful.' ? 'alert-success' : 'alert-warning';
        const content = `
            <strong>API Status:</strong> ${status}<br>
            <strong>Message:</strong> ${message}
        `;
        $('#api-status').removeClass('alert-success alert-warning alert-danger')
                       .addClass(statusClass)
                       .html(content)
                       .show();
    }

    function displayAgentInfo(agentInfo) {
        const content = `
            <div class="row">
                <div class="col-md-4">
                    <strong>Agent ID:</strong> ${agentInfo.agent_id}
                </div>
                <div class="col-md-4">
                    <strong>Agent Code:</strong> ${agentInfo.agent_code}
                </div>
                <div class="col-md-4">
                    <strong>Agent Name:</strong> ${agentInfo.agent_name}
                </div>
            </div>
        `;
        $('#agent-info-content').html(content);
        $('#agent-info-card').show();
    }

    function displaySummary(summary) {
        const content = `
            <div class="row">
                <div class="col-md-2">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-layer-group"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Groups</span>
                            <span class="info-box-number">${summary.total_groups}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-exchange-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Transactions</span>
                            <span class="info-box-number">${summary.total_transactions}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-dollar-sign"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Transaction Amount</span>
                            <span class="info-box-number">${summary.total_transaction_amount}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-coins"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Bet Amount</span>
                            <span class="info-box-number">${summary.total_bet_amount}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Valid Amount</span>
                            <span class="info-box-number">${summary.total_valid_amount}</span>
                        </div>
                    </div>
                </div>
                ${summary.unique_agents ? `
                <div class="col-md-2">
                    <div class="info-box">
                        <span class="info-box-icon bg-dark"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Unique Agents</span>
                            <span class="info-box-number">${summary.unique_agents}</span>
                        </div>
                    </div>
                </div>
                ` : ''}
                ${summary.unique_members ? `
                <div class="col-md-2">
                    <div class="info-box">
                        <span class="info-box-icon bg-light"><i class="fas fa-user"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Unique Members</span>
                            <span class="info-box-number">${summary.unique_members}</span>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
        $('#summary-content').html(content);
        $('#summary-card').show();
    }

    function displayReportTable(data, groupBy) {
        // Clear previous data
        $('#report-table-head').empty();
        $('#report-table-body').empty();

        // Set table headers based on group by
        let headers = '';
        if (groupBy === 'agent_id') {
            headers = `
                <tr>
                    <th>Agent ID</th>
                    <th>Agent Name</th>
                    <th>Total Transactions</th>
                    <th>Total Transaction Amount</th>
                    <th>Total Bet Amount</th>
                    <th>Total Valid Amount</th>
                    <th>Avg Before Balance</th>
                    <th>Avg After Balance</th>
                    <th>First Transaction</th>
                    <th>Last Transaction</th>
                </tr>
            `;
        } else if (groupBy === 'member_account') {
            headers = `
                <tr>
                    <th>Member Account</th>
                    <th>Total Transactions</th>
                    <th>Total Transaction Amount</th>
                    <th>Total Bet Amount</th>
                    <th>Total Valid Amount</th>
                    <th>Avg Before Balance</th>
                    <th>Avg After Balance</th>
                    <th>First Transaction</th>
                    <th>Last Transaction</th>
                </tr>
            `;
        } else {
            headers = `
                <tr>
                    <th>Agent ID</th>
                    <th>Agent Name</th>
                    <th>Member Account</th>
                    <th>Total Transactions</th>
                    <th>Total Transaction Amount</th>
                    <th>Total Bet Amount</th>
                    <th>Total Valid Amount</th>
                    <th>Avg Before Balance</th>
                    <th>Avg After Balance</th>
                    <th>First Transaction</th>
                    <th>Last Transaction</th>
                </tr>
            `;
        }
        $('#report-table-head').html(headers);

        // Add data rows
        let rows = '';
        data.forEach(function(item) {
            let row = '';
            if (groupBy === 'agent_id') {
                row = `
                    <tr>
                        <td>${item.agent_id}</td>
                        <td>${item.agent ? item.agent.name : 'N/A'}</td>
                        <td>${item.total_transactions}</td>
                        <td>${parseFloat(item.total_transaction_amount).toFixed(2)}</td>
                        <td>${parseFloat(item.total_bet_amount).toFixed(2)}</td>
                        <td>${parseFloat(item.total_valid_amount).toFixed(2)}</td>
                        <td>${parseFloat(item.avg_before_balance).toFixed(2)}</td>
                        <td>${parseFloat(item.avg_after_balance).toFixed(2)}</td>
                        <td>${formatDateTime(item.first_transaction)}</td>
                        <td>${formatDateTime(item.last_transaction)}</td>
                    </tr>
                `;
            } else if (groupBy === 'member_account') {
                row = `
                    <tr>
                        <td>${item.member_account}</td>
                        <td>${item.total_transactions}</td>
                        <td>${parseFloat(item.total_transaction_amount).toFixed(2)}</td>
                        <td>${parseFloat(item.total_bet_amount).toFixed(2)}</td>
                        <td>${parseFloat(item.total_valid_amount).toFixed(2)}</td>
                        <td>${parseFloat(item.avg_before_balance).toFixed(2)}</td>
                        <td>${parseFloat(item.avg_after_balance).toFixed(2)}</td>
                        <td>${formatDateTime(item.first_transaction)}</td>
                        <td>${formatDateTime(item.last_transaction)}</td>
                    </tr>
                `;
            } else {
                row = `
                    <tr>
                        <td>${item.agent_id}</td>
                        <td>${item.agent ? item.agent.name : 'N/A'}</td>
                        <td>${item.member_account}</td>
                        <td>${item.total_transactions}</td>
                        <td>${parseFloat(item.total_transaction_amount).toFixed(2)}</td>
                        <td>${parseFloat(item.total_bet_amount).toFixed(2)}</td>
                        <td>${parseFloat(item.total_valid_amount).toFixed(2)}</td>
                        <td>${parseFloat(item.avg_before_balance).toFixed(2)}</td>
                        <td>${parseFloat(item.avg_after_balance).toFixed(2)}</td>
                        <td>${formatDateTime(item.first_transaction)}</td>
                        <td>${formatDateTime(item.last_transaction)}</td>
                    </tr>
                `;
            }
            rows += row;
        });
        $('#report-table-body').html(rows);
        $('#report-card').show();
    }

    function formatDateTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';
        const date = new Date(dateTimeString);
        return date.toLocaleString();
    }

    function showLoading(show) {
        if (show) {
            $('#loading').show();
        } else {
            $('#loading').hide();
        }
    }

    function showError(message) {
        $('#error-message').text(message).show();
        setTimeout(function() {
            $('#error-message').hide();
        }, 5000);
    }

    function fetchMemberTransactions() {
        const formData = {
            agent_code: $('#member_transactions_agent_code').val(),
            member_account: $('#member_transactions_member_account').val(),
            date_from: $('#member_transactions_date_from').val(),
            date_to: $('#member_transactions_date_to').val(),
            limit: $('#member_transactions_limit').val()
        };

        // Validate required fields
        if (!formData.agent_code) {
            showMemberError('Please enter an agent code.');
            return;
        }
        if (!formData.member_account) {
            showMemberError('Please enter a member account.');
            return;
        }

        // Show loading
        showMemberLoading(true);
        hideMemberCards();

        $.ajax({
            url: '{{ route("admin.shan.report.transactions.member") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Pass the response data along with status and message
                    displayMemberTransactions({
                        ...response.data,
                        status: response.status,
                        message: response.message
                    });
                } else {
                    showMemberError(response.message || 'Failed to fetch member transactions');
                }
            },
            error: function(xhr) {
                let message = 'An error occurred while fetching member transactions.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showMemberError(message);
            },
            complete: function() {
                showMemberLoading(false);
            }
        });
    }

    function displayMemberTransactions(apiData) {
        
        // Display API status
        displayMemberApiStatus(apiData.status, apiData.message);
        
        // Display member info
        if (apiData.agent_info && apiData.member_account) {
            displayMemberInfo(apiData.agent_info, apiData.member_account, apiData.filters);
        }
        
        // Display transactions
        if (apiData.transactions && Array.isArray(apiData.transactions)) {
            displayMemberTransactionsTable(apiData.transactions);
        } else {
            // Show empty table with message
            $('#member-transactions-table-body').html('<tr><td colspan="14" class="text-center">No transactions found</td></tr>');
        }
        
        // Always show the member transactions data section
        $('#member-transactions-data').show();
    }

    function displayMemberApiStatus(status, message) {
        const statusClass = status === 'Request was successful.' ? 'alert-success' : 'alert-warning';
        const content = `
            <strong>API Status:</strong> ${status}<br>
            <strong>Message:</strong> ${message}
        `;
        $('#member-api-status').removeClass('alert-success alert-warning alert-danger')
                              .addClass(statusClass)
                              .html(content)
                              .show();
    }

    function displayMemberInfo(agentInfo, memberAccount, filters) {
        const content = `
            <div class="row">
                <div class="col-md-3">
                    <strong>Agent ID:</strong> ${agentInfo.agent_id}
                </div>
                <div class="col-md-3">
                    <strong>Agent Code:</strong> ${agentInfo.agent_code}
                </div>
                <div class="col-md-3">
                    <strong>Agent Name:</strong> ${agentInfo.agent_name}
                </div>
                <div class="col-md-3">
                    <strong>Member Account:</strong> ${memberAccount}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <strong>Date From:</strong> ${filters.date_from || 'All'}
                </div>
                <div class="col-md-3">
                    <strong>Date To:</strong> ${filters.date_to || 'All'}
                </div>
                <div class="col-md-3">
                    <strong>Limit:</strong> ${filters.limit}
                </div>
                <div class="col-md-3">
                    <strong>Total Found:</strong> ${filters.total_found || 'N/A'}
                </div>
            </div>
        `;
        $('#member-info-content').html(content);
        $('#member-transactions-data').show();
    }

    function displayMemberTransactionsTable(transactions) {
        // Clear previous data
        $('#member-transactions-table-body').empty();

        // Add data rows
        let rows = '';
        transactions.forEach(function(transaction) {
            const statusBadge = transaction.status === '1' ? 
                '<span class="badge badge-success">Win</span>' : 
                '<span class="badge badge-danger">Loss</span>';
            
            const settledStatusBadge = getSettledStatusBadge(transaction.settled_status);
            
            const row = `
                <tr>
                    <td>${transaction.id}</td>
                    <td>${transaction.user_id}</td>
                    <td>${parseFloat(transaction.transaction_amount).toFixed(2)}</td>
                    <td>${parseFloat(transaction.bet_amount).toFixed(2)}</td>
                    <td>${parseFloat(transaction.valid_amount).toFixed(2)}</td>
                    <td>${statusBadge}</td>
                    <td>${transaction.banker}</td>
                    <td>${parseFloat(transaction.before_balance).toFixed(2)}</td>
                    <td>${parseFloat(transaction.after_balance).toFixed(2)}</td>
                    <td>${settledStatusBadge}</td>
                    <td>${transaction.wager_code}</td>
                    <td>${transaction.agent_code}</td>
                    <td>${formatDateTime(transaction.created_at)}</td>
                    <td>${formatDateTime(transaction.updated_at)}</td>
                </tr>
            `;
            rows += row;
        });
        $('#member-transactions-table-body').html(rows);
    }

    function getSettledStatusBadge(settledStatus) {
        switch(settledStatus) {
            case 'settled_win':
                return '<span class="badge badge-success">Settled Win</span>';
            case 'settled_loss':
                return '<span class="badge badge-danger">Settled Loss</span>';
            case 'pending':
                return '<span class="badge badge-warning">Pending</span>';
            default:
                return `<span class="badge badge-secondary">${settledStatus}</span>`;
        }
    }

    function showMemberLoading(show) {
        if (show) {
            $('#member-loading').show();
        } else {
            $('#member-loading').hide();
        }
    }

    function showMemberError(message) {
        $('#member-error-message').text(message).show();
        setTimeout(function() {
            $('#member-error-message').hide();
        }, 5000);
    }

    function hideMemberCards() {
        $('#member-api-status').hide();
        $('#member-transactions-data').hide();
        $('#member-error-message').hide();
    }

    function hideAllCards() {
        $('#api-status').hide();
        $('#agent-info-card').hide();
        $('#summary-card').hide();
        $('#report-card').hide();
        $('#error-message').hide();
        $('#member-transactions-section').show(); // Show member transactions section when main data is fetched
    }
});
</script>
@endsection
