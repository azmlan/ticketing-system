<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('recipient_id', 26);
            $table->char('ticket_id', 26)->nullable();
            $table->string('type', 100);
            $table->string('channel', 20)->default('email');
            $table->string('subject', 500);
            $table->string('body_preview', 500)->nullable();
            $table->enum('status', ['queued', 'sent', 'failed']);
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();

            $table->index('recipient_id');
            $table->index(['recipient_id', 'created_at']);
            $table->index(['status', 'created_at']);

            $table->foreign('recipient_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('ticket_id')
                ->references('id')->on('tickets')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
