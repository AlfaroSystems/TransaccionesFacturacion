<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Crear categorías generales de catálogo.
     */
    public function run(): void
    {
        Category::updateOrCreate(['name' => 'Bebidas y Alimentos'], [
            'description' => 'Productos alimenticios, refrescos y suministros generales.',
            'is_active' => true,
        ]);

        Category::updateOrCreate(['name' => 'Electrónica y Tecnología'], [
            'description' => 'Dispositivos electrónicos, cables y accesorios.',
            'is_active' => true,
        ]);

        Category::updateOrCreate(['name' => 'Ferretería y Materiales'], [
            'description' => 'Herramientas, repuestos y materiales generales.',
            'is_active' => true,
        ]);

        Category::updateOrCreate(['name' => 'Limpieza y Hogar'], [
            'description' => 'Productos de limpieza, desinfección e insumos generales del hogar.',
            'is_active' => true,
        ]);
    }
}