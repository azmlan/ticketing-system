<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_pause_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('ticket_sla_id');
            $table->foreign('ticket_sla_id')->references('id')->on('ticket_sla')->onDelete('cascade');

            $table->timestamp('paused_at');
            $table->timestamp('resumed_at')->nullable();
            $table->string('pause_status', 50);
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->timestamps();

            $table->index('ticket_sla_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_pause_logs');
    }
};
