<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('display_number', 20)->unique();
            $table->string('subject');
            $table->text('description');
            $table->enum('status', [
                'awaiting_assignment',
                'in_progress',
                'on_hold',
                'awaiting_approval',
                'action_required',
                'awaiting_final_approval',
                'resolved',
                'closed',
                'cancelled',
            ]);
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->char('category_id', 26);
            $table->char('subcategory_id', 26)->nullable();
            $table->char('group_id', 26);
            $table->char('assigned_to', 26)->nullable();
            $table->char('requester_id', 26);
            $table->char('location_id', 26)->nullable();
            $table->char('department_id', 26)->nullable();
            $table->string('close_reason', 100)->nullable();
            $table->text('close_reason_text')->nullable();
            $table->string('incident_origin', 20)->default('web');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // FK indexes
            $table->index('category_id');
            $table->index('subcategory_id');
            $table->index('group_id');
            $table->index('assigned_to');
            $table->index('requester_id');
            $table->index('location_id');
            $table->index('department_id');

            // Query performance indexes (§5.3)
            $table->index('status');
            $table->index('priority');
            $table->index(['status', 'group_id']);
            $table->index(['status', 'assigned_to']);
            $table->index(['requester_id', 'created_at']);
            $table->index('created_at');

            // FK constraints
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onDelete('restrict');

            $table->foreign('subcategory_id')
                ->references('id')->on('subcategories')
                ->onDelete('set null');

            $table->foreign('group_id')
                ->references('id')->on('groups')
                ->onDelete('restrict');

            $table->foreign('assigned_to')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->foreign('requester_id')
                ->references('id')->on('users')
                ->onDelete('restrict');

            $table->foreign('location_id')
                ->references('id')->on('locations')
                ->onDelete('set null');

            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->onDelete('set null');
        });

        // FULLTEXT indexes — MySQL only (SQLite test environment skips these)
        if (DB::getDriverName() === 'mysql') {
            Schema::table('tickets', function (Blueprint $table) {
                $table->fullText('subject');
                $table->fullText('description');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
