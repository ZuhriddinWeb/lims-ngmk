<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Администратор по умолчанию.
        // ВАЖНО: смените пароль после первого входа / в продакшене.
        User::updateOrCreate(
            ['email' => 'admin@ngmk.uz'],
            [
                'name' => 'Администратор ЛИМС',
                'password' => Hash::make('password'),
            ],
        );

        $this->call(LabSeeder::class);
    }
}
