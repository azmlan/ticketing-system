<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('ticket_id', 26);
            $table->char('from_user_id', 26);
            $table->char('to_user_id', 26);
            $table->enum('status', ['pending', 'accepted', 'rejected', 'revoked']);
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // FK indexes
            $table->index('ticket_id');
            $table->index('from_user_id');
            $table->index('to_user_id');

            // Composite index for active transfer lookup (§7.1)
            $table->index(['ticket_id', 'status']);

            $table->foreign('ticket_id')
                ->references('id')->on('tickets')
                ->onDelete('cascade');

            $table->foreign('from_user_id')
                ->references('id')->on('users')
                ->onDelete('restrict');

            $table->foreign('to_user_id')
                ->references('id')->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_requests');
    }
};
