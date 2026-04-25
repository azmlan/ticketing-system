<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('ticket_id', 26);
            $table->char('custom_field_id', 26);
            $table->text('value')->nullable();
            $table->timestamps();

            // FK indexes (§5.3)
            $table->index('ticket_id');
            $table->index('custom_field_id');
            $table->index(['ticket_id', 'custom_field_id']);

            $table->foreign('ticket_id')
                ->references('id')->on('tickets')
                ->onDelete('cascade');

            $table->foreign('custom_field_id')
                ->references('id')->on('custom_fields')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
