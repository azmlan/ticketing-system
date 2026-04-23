<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condition_reports', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('ticket_id', 26);
            $table->string('report_type');
            $table->char('location_id', 26)->nullable();
            $table->date('report_date');
            $table->text('current_condition');
            $table->text('condition_analysis');
            $table->text('required_action');
            $table->char('tech_id', 26);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->char('reviewed_by', 26)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            // FK indexes
            $table->index('ticket_id');
            $table->index('location_id');
            $table->index('tech_id');
            $table->index('reviewed_by');

            $table->foreign('ticket_id')
                ->references('id')->on('tickets')
                ->onDelete('cascade');

            $table->foreign('location_id')
                ->references('id')->on('locations')
                ->onDelete('set null');

            $table->foreign('tech_id')
                ->references('id')->on('users')
                ->onDelete('restrict');

            $table->foreign('reviewed_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condition_reports');
    }
};
