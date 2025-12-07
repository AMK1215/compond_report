<?php

use App\Http\Controllers\Admin\AdsVedioController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\BannerAdsController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BannerTextController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\GameListController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\PlayerReportController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\Shan\ShanPlayerReportController;
use App\Http\Controllers\Admin\Shan\ShanReportTransactionController;
use App\Http\Controllers\Admin\TopTenWithdrawController;
use App\Http\Controllers\Admin\TransferLogController;
use App\Http\Controllers\Admin\WinnerTextController;
use App\Http\Controllers\Admin\PoneWine\PoneWineReportController;
use App\Http\Controllers\Admin\BuffaloGame\BuffaloReportController;
use App\Http\Controllers\Admin\BuffaloGame\BuffaloRtpController;
use App\Http\Controllers\Admin\GameReport\GameReportController;
use App\Http\Controllers\Agent\SubAgentController;
use App\Http\Controllers\Agent\SubPlayerController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'middleware' => ['auth', 'checkBanned'],
], function () {

    Route::post('balance-up', [HomeController::class, 'balanceUp'])->name('balanceUp');

    Route::get('logs/{id}', [HomeController::class, 'logs'])->name('logs');

    // to do
    Route::get('/changePassword/{user}', [HomeController::class, 'changePassword'])->name('changePassword');
    Route::post('/updatePassword/{user}', [HomeController::class, 'updatePassword'])->name('updatePassword');

    Route::get('/changeplayersite/{user}', [HomeController::class, 'changePlayerSite'])->name('changeSiteName');

    Route::post('/updatePlayersite/{user}', [HomeController::class, 'updatePlayerSiteLink'])->name('updateSiteLink');

    Route::get('/player-list', [HomeController::class, 'playerList'])->name('playerList');

    // banner etc start

    Route::resource('video-upload', AdsVedioController::class);
    Route::resource('winner_text', WinnerTextController::class);
    Route::resource('top-10-withdraws', TopTenWithdrawController::class);
    Route::resource('banners', BannerController::class);
    Route::resource('adsbanners', BannerAdsController::class);
    Route::resource('text', BannerTextController::class);
    Route::resource('/promotions', PromotionController::class);
    Route::resource('contact', ContactController::class);
    // agent start
    Route::resource('agent', AgentController::class);
    Route::get('agent-player-report/{id}', [AgentController::class, 'getPlayerReports'])->name('agent.getPlayerReports');
    Route::get('agent-cash-in/{id}', [AgentController::class, 'getCashIn'])->name('agent.getCashIn');
    Route::post('agent-cash-in/{id}', [AgentController::class, 'makeCashIn'])->name('agent.makeCashIn');
    Route::get('agent/cash-out/{id}', [AgentController::class, 'getCashOut'])->name('agent.getCashOut');
    Route::post('agent/cash-out/update/{id}', [AgentController::class, 'makeCashOut'])
        ->name('agent.makeCashOut');
    Route::put('agent/{id}/ban', [AgentController::class, 'banAgent'])->name('agent.ban');
    Route::get('agent-changepassword/{id}', [AgentController::class, 'getChangePassword'])->name('agent.getChangePassword');
    Route::post('agent-changepassword/{id}', [AgentController::class, 'makeChangePassword'])->name('agent.makeChangePassword');
    Route::get('agent-profile/{id}', [AgentController::class, 'agentProfile'])->name('agent.profile');
    // agent end
    // Player ban route
    Route::middleware(['permission:ban_player'])->group(function () {
        Route::put('player/{id}/ban', [PlayerController::class, 'banUser'])->name('player.ban');
    });

    // Player change password routes
    Route::middleware(['permission:change_player_password'])->group(function () {
        Route::get('player-changepassword/{id}', [PlayerController::class, 'getChangePassword'])->name('player.getChangePassword');
        Route::post('player-changepassword/{id}', [PlayerController::class, 'makeChangePassword'])->name('player.makeChangePassword');
    });


    Route::get('/players-list', [PlayerController::class, 'player_with_agent'])->name('playerListForAdmin');
    // agent create player end
    // report log

    Route::get('/agent-report/{id}', [AgentController::class, 'agentReportIndex'])->name('agent.report');
    Route::get('/player-report/{id}', [PlayerController::class, 'playerReportIndex'])->name('player.report');

    // Shan Report
    Route::get('/shan-report', [ReportController::class, 'shanReportIndex'])->name('shan_report');

    // master, agent sub-agent end
    Route::get('/transfer-logs', [TransferLogController::class, 'index'])->name('transfer-logs.index');


    Route::get('playertransferlog/{id}', [TransferLogController::class, 'PlayertransferLog'])->name('PlayertransferLogDetail');



    Route::get('report', [ReportController::class, 'index'])->name('report.index');
    Route::get('report/{member_account}', [ReportController::class, 'show'])->name('report.detail');
    Route::get('player-report', [PlayerReportController::class, 'summary'])->name('player_report.summary');
    Route::get('reports/daily-win-loss', [ReportController::class, 'dailyWinLossReport'])->name('reports.daily_win_loss');
    Route::get('reports/game-log-report', [ReportController::class, 'gameLogReport'])->name('reports.game_log_report');
    Route::get('reports/combined-game-report', [\App\Http\Controllers\Admin\CombinedGameReportController::class, 'index'])->name('reports.combined_game_report');


    Route::get('game-reports',[GameReportController::class,'index'])->name('game_report.index');
    Route::get('game-reports/player/{id}',[GameReportController::class,'playerIndex'])->name('player.game_report.index');
    Route::get('game-reports/agent/{id}',[GameReportController::class,'agentIndex'])->name('agent.game_report.index');


    Route::get('reports/player-report/{playerId}',[ReportController::class,'getReport'])->name('report.player.index');

    Route::middleware(['role:Owner'])->group(function () {
        Route::get('buffalo-game/rtp', [BuffaloRtpController::class, 'index'])->name('buffalo.rtp.index');
        Route::post('buffalo-game/rtp', [BuffaloRtpController::class, 'update'])->name('buffalo.rtp.update');
    });

    // provider start
    Route::get('gametypes', [ProductController::class, 'index'])->name('gametypes.index');
    Route::post('/game-types/{productId}/toggle-status', [ProductController::class, 'toggleStatus'])->name('gametypes.toggle-status');
    Route::get('gametypes/{game_type_id}/product/{product_id}', [ProductController::class, 'edit'])->name('gametypes.edit');
    Route::post('gametypes/{game_type_id}/product/{product_id}', [ProductController::class, 'update'])->name('gametypes.update');
    Route::post('admin/gametypes/{gameTypeId}/{productId}/update', [ProductController::class, 'update'])
        ->name('gametypesproduct.update');

    // game list start
    Route::get('all-game-lists', [GameListController::class, 'GetGameList'])->name('gameLists.index');
    Route::get('all-game-lists/{id}', [GameListController::class, 'edit'])->name('gameLists.edit');
    Route::post('all-game-lists/{id}', [GameListController::class, 'update'])->name('gameLists.update');

    Route::patch('gameLists/{id}/toggleStatus', [GameListController::class, 'toggleStatus'])->name('gameLists.toggleStatus');

    Route::patch('hotgameLists/{id}/toggleStatus', [GameListController::class, 'HotGameStatus'])->name('HotGame.toggleStatus');

    // pp hot

    Route::patch('pphotgameLists/{id}/toggleStatus', [GameListController::class, 'PPHotGameStatus'])->name('PPHotGame.toggleStatus');
    Route::get('game-list/{gameList}/edit', [GameListController::class, 'edit'])->name('game_list.edit');
    Route::post('/game-list/{id}/update-image-url', [GameListController::class, 'updateImageUrl'])->name('game_list.update_image_url');
    Route::get('game-list-order/{gameList}/edit', [GameListController::class, 'GameListOrderedit'])->name('game_list_order.edit');
    Route::post('/game-lists/{id}/update-order', [GameListController::class, 'updateOrder'])->name('GameListOrderUpdate');


    // shan player report
    Route::get('/shan-player-report', [ShanPlayerReportController::class, 'index'])->name('shan.player.report');

    // shan report transactions
    Route::get('/shan-report-transactions', [ShanReportTransactionController::class, 'index'])->name('shan.report.transactions');
    Route::post('/shan-report-transactions/fetch', [ShanReportTransactionController::class, 'fetchReportTransactions'])->name('shan.report.transactions.fetch');
    Route::post('/shan-report-transactions/member', [ShanReportTransactionController::class, 'fetchMemberTransactions'])->name('shan.report.transactions.member');

    // PoneWine reports
    Route::group(['prefix' => 'ponewine'], function () {
        Route::get('/report', [PoneWineReportController::class, 'index'])->name('ponewine.report.index');
        Route::get('/report/agent/{agentId}', [PoneWineReportController::class, 'agentDetail'])->name('ponewine.report.agent.detail');
        Route::get('/report/player/{playerId}', [PoneWineReportController::class, 'playerDetail'])->name('ponewine.report.player.detail');
        Route::get('/report/export', [PoneWineReportController::class, 'exportCsv'])->name('ponewine.report.export');
    });

    // Buffalo Game reports
    Route::group(['prefix' => 'buffalo-game'], function () {
        Route::get('/report', [BuffaloReportController::class, 'index'])->name('buffalo-report.index');
        Route::get('/report/{id}', [BuffaloReportController::class, 'show'])->name('buffalo-report.show');

        Route::get('/new-report', [App\Http\Controllers\Admin\BuffaloGame\BuffaloGameReportController::class, 'index'])
        ->name('new_buffalo_report.index');

        Route::get('/new-report/{id}', [App\Http\Controllers\Admin\BuffaloGame\BuffaloGameReportController::class, 'show'])
            ->name('new_buffalo_report.show');
    });
});

// Agent Routes - For agents to manage their sub-agents and players
Route::group([
    'prefix' => 'agent',
    'as' => 'agent.',
    'middleware' => ['auth', 'checkBanned', 'role:Agent'],
], function () {

    // Sub-Agent Management (Agent to Agent)
    Route::resource('sub-agent', SubAgentController::class);
    Route::put('sub-agent/{id}/ban', [SubAgentController::class, 'banAgent'])->name('sub-agent.ban');
    Route::get('sub-agent/{id}/cash-in', [SubAgentController::class, 'getCashIn'])->name('sub-agent.getCashIn');
    Route::post('sub-agent/{id}/cash-in', [SubAgentController::class, 'makeCashIn'])->name('sub-agent.makeCashIn');
    Route::get('sub-agent/{id}/cash-out', [SubAgentController::class, 'getCashOut'])->name('sub-agent.getCashOut');
    Route::post('sub-agent/{id}/cash-out', [SubAgentController::class, 'makeCashOut'])->name('sub-agent.makeCashOut');
    Route::get('sub-agent/{id}/change-password', [SubAgentController::class, 'getChangePassword'])->name('sub-agent.getChangePassword');
    Route::post('sub-agent/{id}/change-password', [SubAgentController::class, 'makeChangePassword'])->name('sub-agent.makeChangePassword');
    Route::get('sub-agent/{id}/logs', [SubAgentController::class, 'logs'])->name('sub-agent.logs');

    // Player Management (Agent to Player)
    Route::resource('player', SubPlayerController::class);
    Route::put('player/{id}/ban', [SubPlayerController::class, 'banPlayer'])->name('player.ban');
    Route::get('player/{id}/cash-in', [SubPlayerController::class, 'getCashIn'])->name('player.getCashIn');
    Route::post('player/{id}/cash-in', [SubPlayerController::class, 'makeCashIn'])->name('player.makeCashIn');
    Route::get('player/{id}/cash-out', [SubPlayerController::class, 'getCashOut'])->name('player.getCashOut');
    Route::post('player/{id}/cash-out', [SubPlayerController::class, 'makeCashOut'])->name('player.makeCashOut');
    Route::get('player/{id}/change-password', [SubPlayerController::class, 'getChangePassword'])->name('player.getChangePassword');
    Route::post('player/{id}/change-password', [SubPlayerController::class, 'makeChangePassword'])->name('player.makeChangePassword');
    Route::get('player/{id}/logs', [SubPlayerController::class, 'logs'])->name('player.logs');
});
