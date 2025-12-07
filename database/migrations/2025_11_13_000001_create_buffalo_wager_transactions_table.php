<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buffalo_wager_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('wager_code')->unique();
            $table->decimal('bet_amount', 15, 2);
            $table->decimal('prize_amount', 15, 2);
            $table->decimal('net_amount', 15, 2);
            $table->decimal('before_balance', 15, 2)->nullable();
            $table->decimal('after_balance', 15, 2)->nullable();
            $table->unsignedBigInteger('player_agent_id')->nullable()->index();
            $table->string('player_agent_name')->nullable();
            $table->unsignedBigInteger('request_time')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buffalo_wager_transactions');
    }
};

