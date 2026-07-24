<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Administrador por defecto
        User::factory()->create([
            'name' => 'Admin Sistema',
            'email' => 'admin@facturacion.com',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
