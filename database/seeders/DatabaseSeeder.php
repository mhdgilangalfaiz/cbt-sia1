<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Gilang Alfaiz',
            'email' => 'gilangalfaiz@gmail.com',
            'username' => 'geleng',
            'is_staff' => true,
            // use Illuminate\Support\Facades\Hash;  <-import di atas
            'password' => Hash::make('rahasia'),
        ]);
    }
}
