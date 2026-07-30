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

        // Empresas por defecto para sucursales
        if (!Company::exists()) {
            // Buscar datos para San Salvador
            $deptSS = \App\Models\Department::where('code', '06')->first();
            $muniSS = \App\Models\Municipality::where('code', '0603')->first();
            $distSS = \App\Models\District::where('code', '060303')->first();

            Company::create([
                'name' => 'Empresa Principal S.A. de C.V.',
                'commercial_name' => 'Empresa Principal',
                'nit' => '0614-220726-101-5',
                'nrc' => '123456-7',
                'commercial_line_1' => 'Servicios Informáticos',
                'address' => 'Alameda Manuel Enrique Araujo, San Salvador',
                'department_id' => $deptSS?->id,
                'municipality_id' => $muniSS?->id,
                'district_id' => $distSS?->id,
                'phone' => '2222-3333',
                'email' => 'contacto@empresaprincipal.com',
                'web_site' => 'https://empresaprincipal.com',
                'is_active' => true
            ]);
            
            // Buscar datos para Santa Ana
            $deptSA = \App\Models\Department::where('code', '02')->first();
            $muniSA = \App\Models\Municipality::where('code', '0202')->first();
            $distSA = \App\Models\District::where('code', '020201')->first();

            Company::create([
                'name' => 'Facturación Global S.A.',
                'commercial_name' => 'Facturación Global',
                'nit' => '0101-121290-202-4',
                'nrc' => '987654-3',
                'commercial_line_1' => 'Comercio General',
                'address' => 'Avenida Independencia Sur, Santa Ana',
                'department_id' => $deptSA?->id,
                'municipality_id' => $muniSA?->id,
                'district_id' => $distSA?->id,
                'phone' => '2440-1234',
                'email' => 'info@facturacionglobal.com',
                'web_site' => 'https://facturacionglobal.com',
                'is_active' => true
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
