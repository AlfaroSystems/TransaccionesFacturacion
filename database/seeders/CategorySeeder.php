<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Crear categorías iniciales de farmacia.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'Analgésicos',
            'description' => 'Medicamentos utilizados para aliviar dolores leves, moderados y fiebre.',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Antibióticos',
            'description' => 'Medicamentos utilizados para tratar infecciones bacterianas.',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Pediatría',
            'description' => 'Productos y medicamentos destinados para niños.',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Higiene Personal',
            'description' => 'Productos de cuidado e higiene personal.',
            'is_active' => true,
        ]);
    }
}