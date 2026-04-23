<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'id' => (string) Str::ulid(),
                'name_ar' => 'دعم تقني',
                'name_en' => 'Technical Support',
                'manager_id' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'name_ar' => 'البنية التحتية',
                'name_en' => 'Infrastructure',
                'manager_id' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('groups')->insert($groups);
    }
}
