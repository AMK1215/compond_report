<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pone_wine_transactions', function (Blueprint $table) {
            $table->id();
            
            // Game Information
            $table->integer('room_id');
            $table->string('match_id'); // Remove unique constraint since multiple bets per match
            $table->integer('win_number');
            
            // Player Information
            $table->string('player_id'); // player username
            $table->unsignedBigInteger('user_id'); // user table ID
            $table->string('user_name'); // player username (duplicate for easy querying)
            
            // Agent Information (from provider payload)
            $table->string('client_agent_name')->nullable();
            $table->string('client_agent_id')->nullable();
            
            // Bet Information
            $table->integer('bet_number'); // The number player bet on
            $table->decimal('bet_amount', 15, 2); // Amount player bet
            
            // Result Information
            $table->decimal('win_lose_amount', 15, 2); // Win/Loss amount
            $table->decimal('player_balance_before', 15, 2); // Player balance before transaction
            $table->decimal('player_balance_after', 15, 2); // Player balance after transaction
            $table->enum('result', ['Win', 'Lose', 'Draw']);
            
            // Provider Data (for reference)
            $table->integer('provider_bet_id')->nullable(); // Provider's pone_wine_bet.id
            $table->integer('provider_player_bet_id')->nullable(); // Provider's pone_wine_player_bet.id
            $table->integer('provider_bet_info_id')->nullable(); // Provider's pone_wine_bet_info.id
            
            // Transaction Status
            $table->boolean('is_processed')->default(true);
            $table->timestamp('processed_at')->nullable();
            
            // Metadata
            $table->json('provider_payload')->nullable(); // Store complete provider payload for reference
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('player_agent_id')->nullable();
            $table->string('player_agent_name')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['room_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['match_id']);
            $table->index(['player_id', 'created_at']);
            $table->index(['result', 'created_at']);
            $table->index(['bet_number', 'room_id']);
            
            // Unique constraint to prevent duplicate transactions
            // A player can have multiple bets in the same match (different bet numbers)
            $table->unique(['match_id', 'user_id', 'bet_number'], 'unique_transaction');
            
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
        Schema::dropIfExists('pone_wine_transactions');
    }
};