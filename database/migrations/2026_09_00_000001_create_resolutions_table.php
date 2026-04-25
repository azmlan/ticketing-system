<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('ticket_id', 26)->unique();
            $table->string('summary', 500);
            $table->string('root_cause', 500)->nullable();
            $table->text('steps_taken')->nullable();
            $table->text('parts_resources')->nullable();
            $table->unsignedInteger('time_spent_minutes')->nullable();
            $table->enum('resolution_type', ['known_fix', 'workaround', 'escalated_externally', 'other']);
            $table->char('linked_resolution_id', 26)->nullable();
            $table->text('link_notes')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->char('created_by', 26);
            $table->timestamps();

            $table->foreign('ticket_id')
                ->references('id')->on('tickets')
                ->onDelete('cascade');

            $table->foreign('linked_resolution_id')
                ->references('id')->on('resolutions')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('restrict');

            $table->index('linked_resolution_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};
