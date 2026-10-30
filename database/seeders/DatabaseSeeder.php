<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([ExpenseTypeSeeder::class]);

        // Cargar Catálogo Geográfico Primero
        $this->call(GeographicSeeder::class);

        // Cargar Roles y Permisos
        $this->call(RoleAndPermissionSeeder::class);

        $this->crearAdministradorInicial();

        // Cargar Catálogo de Países
        $this->call(CountrySeeder::class);
    }

    /**
     * Administrador del cliente, solo si todavía no hay ninguno (una instalación nueva). Su
     * contraseña es aleatoria y se muestra una sola vez: con una fija, cualquiera que leyera
     * el seeder podría entrar como administrador.
     */
    private function crearAdministradorInicial(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        if ($adminRole->users()->exists()) {
            return;
        }

        $email = 'jon.virgi@gmail.com';
        $user = User::where('email', $email)->first();
        $password = null;

        if (! $user) {
            $password = Str::password(16);
            $user = User::create([
                'username' => 'Admin Sistema (Jon)',
                'email' => $email,
                'password_hash' => Hash::make($password),
                'is_active' => true,
            ]);
        }

        $user->roles()->syncWithoutDetaching([$adminRole->id_role => ['assigned_at' => now()]]);

        $this->command?->warn($password
            ? "Administrador inicial: {$email} con la contraseña {$password} (cámbiala al entrar)."
            : "Se asignó el rol de administrador a {$email}; conserva su contraseña.");
    }
}
