<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['slug' => 'news', 'name' => 'News'],
            ['slug' => 'technology', 'name' => 'Technology'],
            ['slug' => 'sports', 'name' => 'Sports'],
            ['slug' => 'business', 'name' => 'Business'],
            ['slug' => 'entertainment', 'name' => 'Entertainment'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
