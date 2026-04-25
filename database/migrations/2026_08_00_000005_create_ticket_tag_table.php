<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_tag', function (Blueprint $table) {
            $table->char('ticket_id', 26);
            $table->char('tag_id', 26);

            $table->unique(['ticket_id', 'tag_id']);
            $table->index('ticket_id');
            $table->index('tag_id');

            $table->foreign('ticket_id')
                ->references('id')->on('tickets')
                ->onDelete('cascade');

            $table->foreign('tag_id')
                ->references('id')->on('tags')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_tag');
    }
};
