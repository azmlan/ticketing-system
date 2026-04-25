<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_options', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('custom_field_id', 26);
            $table->string('value_ar');
            $table->string('value_en');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('custom_field_id');

            $table->foreign('custom_field_id')
                ->references('id')->on('custom_fields')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_options');
    }
};
