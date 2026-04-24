<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubcategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = DB::table('categories')->orderBy('created_at')->get();

        $subcategories = [];

        foreach ($categories as $category) {
            $subcategories[] = [
                'id' => (string) Str::ulid(),
                'category_id' => $category->id,
                'name_ar' => 'عطل في الجهاز',
                'name_en' => 'Device Failure',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 1,
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $subcategories[] = [
                'id' => (string) Str::ulid(),
                'category_id' => $category->id,
                'name_ar' => 'طلب استبدال',
                'name_en' => 'Replacement Request',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 2,
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('subcategories')->insert($subcategories);
    }
}
