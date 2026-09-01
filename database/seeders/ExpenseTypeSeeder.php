<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        ExpenseType::insert([
            [
                'name' => 'Flete',
                'description' => 'Gastos relacionados con el transporte de mercancías.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Seguro',
                'description' => 'Gastos correspondientes al seguro de las mercancías.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Arancel Aduanal',
                'description' => 'Gastos relacionados con aranceles y trámites aduanales.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Control de Calidad',
                'description' => 'Gastos relacionados con inspecciones y control de calidad.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}