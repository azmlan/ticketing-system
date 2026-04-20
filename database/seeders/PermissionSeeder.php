<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $permissions = config('permissions');

        $rows = array_map(
            fn (string $key, array $data) => [
                'id'         => \Illuminate\Support\Str::ulid()->toBase32(),
                'key'        => $key,
                'name_ar'    => $data['name_ar'],
                'name_en'    => $data['name_en'],
                'group_key'  => $data['group_key'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_keys($permissions),
            $permissions,
        );

        // Idempotent: update names on re-run, never duplicate
        DB::table('permissions')->upsert(
            $rows,
            uniqueBy: ['key'],
            update: ['name_ar', 'name_en', 'group_key', 'updated_at'],
        );
    }
}
