<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name_ar');
            $table->string('name_en');
            $table->char('group_id', 26);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('group_id');

            $table->foreign('group_id')
                ->references('id')->on('groups')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
