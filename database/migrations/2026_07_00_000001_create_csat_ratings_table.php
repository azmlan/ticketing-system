<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('csat_ratings', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('ticket_id');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->unique('ticket_id');

            $table->ulid('requester_id');
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('restrict');

            $table->ulid('tech_id');
            $table->foreign('tech_id')->references('id')->on('users')->onDelete('restrict');

            $table->tinyInteger('rating')->unsigned()->nullable();
            $table->text('comment')->nullable();
            $table->enum('status', ['pending', 'submitted', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('dismissed_count')->default(0);

            $table->timestamps();

            $table->index(['tech_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csat_ratings');
    }
};
