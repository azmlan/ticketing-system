<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('field_type', ['text', 'number', 'dropdown', 'multi_select', 'date', 'checkbox']);
            $table->boolean('is_required')->default(false);
            $table->enum('scope_type', ['global', 'category'])->default('global');
            $table->char('scope_category_id', 26)->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->softDeletes();
            $table->timestamps();

            $table->index('scope_category_id');

            $table->foreign('scope_category_id')
                ->references('id')->on('categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
