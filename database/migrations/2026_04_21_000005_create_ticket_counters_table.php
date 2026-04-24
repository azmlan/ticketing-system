<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_counters', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });

        // Seed the single counter row — this is a structural invariant, not fixture data
        DB::table('ticket_counters')->insert(['id' => 1, 'last_number' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_counters');
    }
};
