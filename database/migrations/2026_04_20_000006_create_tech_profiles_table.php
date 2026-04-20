<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tech_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->unique();
            $table->string('specialization', 255)->nullable();
            $table->string('job_title_ar', 255)->nullable();
            $table->string('job_title_en', 255)->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('promoted_at');
            $table->ulid('promoted_by');
            $table->timestamps();

            // Explicit index on promoted_by FK (user_id already indexed via unique)
            $table->index('promoted_by');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('promoted_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tech_profiles');
    }
};
