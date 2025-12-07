<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop constraint if it exists (PostgreSQL specific) - try multiple possible names
        if (Schema::hasTable('main_reports')) {
            DB::statement('ALTER TABLE main_reports DROP CONSTRAINT IF EXISTS unique_transaction');
            DB::statement('ALTER TABLE main_reports DROP CONSTRAINT IF EXISTS main_reports_match_id_user_id_bet_number_unique');
        }
        
        // Drop table if it exists to avoid constraint conflicts
        Schema::dropIfExists('main_reports');
        
        Schema::create('main_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Batch-level data
            $table->string('member_account')->nullable();
            $table->unsignedBigInteger('player_id')->nullable();
            $table->unsignedBigInteger('player_agent_id')->nullable();
            $table->unsignedBigInteger('product_code')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('game_type')->nullable();
            $table->string('operator_code')->nullable();
            $table->timestamp('request_time')->nullable();
            $table->string('sign')->nullable();
            $table->string('currency')->nullable();

            // Transaction-level data
            $table->string('transaction_id')->unique();
            $table->string('action');
            $table->decimal('amount', 20, 4);
            $table->decimal('valid_bet_amount', 20, 4)->nullable();
            $table->decimal('bet_amount', 20, 4)->nullable();
            $table->decimal('prize_amount', 20, 4)->nullable();
            $table->decimal('tip_amount', 20, 4)->nullable();
            $table->string('wager_code')->nullable();
            $table->string('wager_status')->nullable();
            $table->string('round_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('settle_at')->nullable();
            $table->string('game_code')->nullable();
            $table->string('game_name')->nullable();
            $table->string('channel_code')->nullable();
            $table->string('status')->default('pending'); // New: To store the status of the transaction from our side
            // Add before_balance and after_balance if you want to explicitly store them here
            $table->decimal('before_balance', 20, 4)->nullable();
            $table->decimal('balance', 20, 4)->nullable();
            $table->unsignedBigInteger('game_type_id')->nullable();// always 15 here, but keep column
            $table->json('players')->nullable();                   // stores $callbackPlayers array
            $table->decimal('banker_balance', 20, 4)->nullable();  // banker->wallet->balanceFloat
            $table->timestampTz('timestamp')->nullable();          // ISO8601 UTC timestamp
            $table->decimal('total_player_net', 20, 4)->nullable();// $trueTotalPlayerNet
            $table->decimal('banker_amount_change', 20, 4)->nullable(); // $bankerAmountChange
            $table->string('agent_name')->nullable();

            // Game Information
            $table->integer('room_id')->nullable();
            $table->string('match_id')->nullable(); // Remove unique constraint since multiple bets per match
            $table->integer('win_number')->nullable();
            
            // Player Information
            $table->unsignedBigInteger('user_id')->nullable(); // user table ID
            // Bet Information
            $table->integer('bet_number')->nullable(); // The number player bet on
            
            // Result Information
            $table->decimal('win_lose_amount', 15, 2)->nullable(); // Win/Loss amount
            $table->enum('result', ['Win', 'Lose', 'Draw'])->nullable();

            // slot, buffalo, ponewine, shan
            $table->enum('report_game_type', ['gscs_slot', 'buffalo', 'ponewine', 'shan'])->nullable();
            
            // Provider Data (for reference)
            $table->integer('provider_bet_id')->nullable(); // Provider's pone_wine_bet.id
            $table->integer('provider_player_bet_id')->nullable(); // Provider's pone_wine_player_bet.id
            $table->integer('provider_bet_info_id')->nullable(); // Provider's pone_wine_bet_info.id
            
            // Transaction Status
            $table->boolean('is_processed')->default(true);
            $table->timestamp('processed_at')->nullable();
            
            // Metadata
            $table->json('meta')->nullable(); // Store complete provider payload for reference
            $table->text('notes')->nullable();
            // $table->unsignedBigInteger('player_agent_id')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            // Composite indexes for common query patterns
            $table->index(['room_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['match_id']);
            $table->index(['player_id', 'created_at']);
            $table->index(['result', 'created_at']);
            $table->index(['bet_number', 'room_id']);
            $table->index(['status', 'created_at']);
            $table->index(['is_processed', 'created_at']);
            $table->index(['game_type_id', 'created_at']);
            $table->index(['player_agent_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['game_type', 'created_at']);
            $table->index(['provider_name', 'created_at']);
            $table->index(['wager_code', 'created_at']);
            $table->index(['wager_status', 'created_at']);
            $table->index(['product_code', 'created_at']);
            $table->index(['operator_code', 'created_at']);
            $table->index(['report_game_type', 'created_at']);
            
            // Single column indexes for frequently filtered columns
            $table->index('status');
            $table->index('is_processed');
            $table->index('game_type_id');
            $table->index('player_agent_id');
            $table->index('action');
            $table->index('game_type');
            $table->index('provider_name');
            $table->index('wager_code');
            $table->index('wager_status');
            $table->index('product_code');
            $table->index('operator_code');
            $table->index('member_account');
            $table->index('currency');
            $table->index('round_id');
            $table->index('game_code');
            $table->index('channel_code');
            $table->index('win_number');
            $table->index('report_game_type');
            
            // Date/time indexes for range queries
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('settle_at');
            $table->index('timestamp');
            $table->index('request_time');
            $table->index('processed_at');
            
            // Composite indexes for date range queries with filters
            $table->index(['status', 'settle_at']);
            $table->index(['is_processed', 'processed_at']);
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['player_agent_id', 'status', 'created_at']);
            $table->index(['game_type_id', 'status', 'created_at']);
            
            // Unique constraint to prevent duplicate transactions
            // A player can have multiple bets in the same match (different bet numbers)
            // Using a more specific name to avoid conflicts
            $table->unique(['match_id', 'user_id', 'bet_number'], 'main_reports_match_user_bet_unique');
            
            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('player_agent_id')->references('id')->on('users')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_reports');
    }
};
