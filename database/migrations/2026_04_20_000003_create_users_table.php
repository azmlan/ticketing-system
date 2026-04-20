<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('employee_number', 50)->nullable();
            $table->ulid('department_id')->nullable();
            $table->ulid('location_id')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('locale', 5)->default('ar');
            $table->boolean('is_tech')->default(false);
            $table->boolean('is_super_user')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Explicit indexes on every FK column (CLAUDE.md convention)
            $table->index('department_id');
            $table->index('location_id');

            // FK constraints with explicit ON DELETE
            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->onDelete('set null');

            $table->foreign('location_id')
                ->references('id')->on('locations')
                ->onDelete('set null');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions use char(26) user_id to match ULID primary keys
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->char('user_id', 26)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
