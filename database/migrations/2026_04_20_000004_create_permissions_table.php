<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key', 100)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('group_key', 100);
            $table->timestamps();

            $table->index('group_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
