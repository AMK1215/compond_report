<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buffalo_provider_settings', function (Blueprint $table) {
            $table->decimal('total_bet_amount', 15, 2)->default(0)->after('maintenance_request_time');
            $table->decimal('total_prize_amount', 15, 2)->default(0)->after('total_bet_amount');
            $table->decimal('total_profit', 15, 2)->default(0)->after('total_prize_amount');
            $table->unsignedBigInteger('spin_count')->default(0)->after('total_profit');
        });
    }

    public function down(): void
    {
        Schema::table('buffalo_provider_settings', function (Blueprint $table) {
            $table->dropColumn([
                'total_bet_amount',
                'total_prize_amount',
                'total_profit',
                'spin_count',
            ]);
        });
    }
};

