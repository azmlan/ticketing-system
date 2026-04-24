<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->unique();
            $table->unsignedInteger('response_target_minutes');
            $table->unsignedInteger('resolution_target_minutes');
            $table->boolean('use_24x7')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
