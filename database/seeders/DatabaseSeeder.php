<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'API Service User',
            'email' => 'serviceuser@example.com',
            'password' => Hash::make('test_secret_key'),
            'api_public_key' => 'test_public_key',
            'api_secret_key' => Hash::make('test_secret_key'),
        ]);

        // Остальные сидеры
        $this->call([
            CategoriesTableSeeder::class,
        ]);
    }
}
