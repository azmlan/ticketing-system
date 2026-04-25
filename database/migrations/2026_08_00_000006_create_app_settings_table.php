<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['key' => 'company_name',          'value' => null],
            ['key' => 'logo_path',             'value' => null],
            ['key' => 'primary_color',         'value' => '#2563EB'],
            ['key' => 'secondary_color',       'value' => '#64748B'],
            ['key' => 'business_hours_start',  'value' => '08:00'],
            ['key' => 'business_hours_end',    'value' => '16:00'],
            ['key' => 'working_days',          'value' => json_encode(['sun', 'mon', 'tue', 'wed', 'thu'])],
            ['key' => 'sla_warning_threshold', 'value' => '75'],
            ['key' => 'session_timeout_hours', 'value' => '8'],
        ];

        foreach ($defaults as $row) {
            DB::table('app_settings')->insert([
                'id'         => (string) Str::ulid(),
                'key'        => $row['key'],
                'value'      => $row['value'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
