<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buffalo_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('rtp', 5, 2)->default(0.00);
            $table->boolean('is_under_maintenance')->default(false);
            $table->string('maintenance_reason')->nullable();
            $table->unsignedBigInteger('rtp_request_time')->nullable();
            $table->unsignedBigInteger('maintenance_request_time')->nullable();
            $table->timestamps();

            $table->index('is_under_maintenance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buffalo_provider_settings');
    }
};

