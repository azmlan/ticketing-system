<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_user', function (Blueprint $table) {
            $table->ulid('user_id');
            $table->ulid('permission_id');
            $table->ulid('granted_by')->nullable();
            $table->timestamp('granted_at')->useCurrent();

            // Composite PK enforces uniqueness; serves as the required UNIQUE(user_id, permission_id)
            $table->primary(['user_id', 'permission_id']);

            // Explicit indexes on FK columns (CLAUDE.md convention)
            $table->index('permission_id');
            $table->index('granted_by');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
    }
};
