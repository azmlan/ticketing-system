<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $groups = DB::table('groups')->orderBy('created_at')->get();

        $categories = [
            [
                'id' => (string) Str::ulid(),
                'name_ar' => 'أجهزة الحاسوب',
                'name_en' => 'Computer Hardware',
                'group_id' => $groups[0]->id,
                'is_active' => true,
                'sort_order' => 1,
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'name_ar' => 'الشبكات',
                'name_en' => 'Networking',
                'group_id' => $groups[1]->id,
                'is_active' => true,
                'sort_order' => 1,
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('categories')->insert($categories);
    }
}
