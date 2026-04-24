<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('ticket_id', 26);
            $table->char('user_id', 26);
            $table->text('body');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['ticket_id', 'created_at']);

            $table->foreign('ticket_id')
                ->references('id')->on('tickets')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('restrict');
        });

        // FULLTEXT index — MySQL only (SQLite used in test env does not support it)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE comments ADD FULLTEXT INDEX comments_body_fulltext (body)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
