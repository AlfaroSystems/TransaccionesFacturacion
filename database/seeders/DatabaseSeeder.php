<?php

namespace Database\Seeders;
use App\Models\User;
use App\Models\Company;
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
        // Cargar Catálogo Geográfico Primero
        $this->call(GeographicSeeder::class);

        // Registrar también el usuario del cliente
        if (!User::where('email', 'jon.virgi@gmail.com')->exists()) {
            User::factory()->create([
                'name' => 'Admin Sistema (Jon)',
                'email' => 'jon.virgi@gmail.com',
                'status' => 'active',
            ]);
        }

        // Cargar Roles y Permisos
        $this->call(RoleAndPermissionSeeder::class);
    }
}