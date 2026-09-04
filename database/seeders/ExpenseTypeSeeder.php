<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Flete',
                'description' => 'Gastos relacionados con el transporte de mercancías.',
                'is_active' => true,
            ],
            [
                'name' => 'Seguro',
                'description' => 'Gastos correspondientes al seguro de las mercancías.',
                'is_active' => true,
            ],
            [
                'name' => 'Arancel Aduanal',
                'description' => 'Gastos relacionados con aranceles y trámites aduanales.',
                'is_active' => true,
            ],
            [
                'name' => 'Control de Calidad',
                'description' => 'Gastos relacionados con inspecciones y control de calidad.',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            ExpenseType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}