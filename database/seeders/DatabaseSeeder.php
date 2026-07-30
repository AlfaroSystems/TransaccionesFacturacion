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
        // Empresas por defecto para sucursales
        if (!Company::exists()) {
            Company::create([
                'name' => 'Empresa Principal S.A. de C.V.',
                'nit' => '0614-220726-101-5',
                'address' => 'San Salvador, El Salvador',
                'phone' => '2222-3333'
            ]);
            
            Company::create([
                'name' => 'Facturación Global S.A.',
                'nit' => '0101-121290-202-4',
                'address' => 'Santa Ana, El Salvador',
                'phone' => '2440-1234'
            ]);
        }

        // Administrador por defecto
        if (!User::where('email', 'admin@facturacion.com')->exists()) {
            User::factory()->create([
                'name' => 'Admin Sistema',
                'email' => 'admin@facturacion.com',
                'status' => 'active',
            ]);
        }

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
