<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Создаем тестового пользователя без использования фабрики
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => \Illuminate\Support\Str::random(10),
        ]);

        // Запускаем сидер для досок настроения
        $this->call([
            MoodBoardSeeder::class,
        ]);
    }
}
