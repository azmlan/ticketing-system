<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_sla', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('ticket_id')->unique();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');

            $table->unsignedInteger('response_target_minutes')->nullable();
            $table->unsignedInteger('resolution_target_minutes')->nullable();
            $table->unsignedInteger('response_elapsed_minutes')->default(0);
            $table->unsignedInteger('resolution_elapsed_minutes')->default(0);
            $table->timestamp('response_met_at')->nullable();

            $table->enum('response_status', ['on_track', 'warning', 'breached'])->default('on_track');
            $table->enum('resolution_status', ['on_track', 'warning', 'breached'])->default('on_track');

            $table->timestamp('last_clock_start')->nullable();
            $table->boolean('is_clock_running')->default(true);

            $table->timestamps();

            $table->index('response_status');
            $table->index('resolution_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_sla');
    }
};
