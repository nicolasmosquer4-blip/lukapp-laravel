<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /** Mismas categorías y colores que usaba el frontend (movimientoView.js / estadisticaModel.js). */
    public const CATEGORIES = [
        'ingreso' => [
            ['Salario', '#1FA36A'],
            ['Freelance', '#3B82F6'],
            ['Ventas', '#F59E0B'],
            ['Inversiones', '#A855F7'],
            ['Otros ingresos', '#6B7280'],
        ],
        'gasto' => [
            ['Alimentación', '#EF4444'],
            ['Vivienda', '#FDE047'],
            ['Transporte', '#10B981'],
            ['Servicios', '#A855F7'],
            ['Educación', '#3B82F6'],
            ['Entretenimiento', '#EC4899'],
            ['Salud', '#F59E0B'],
            ['Otros gastos', '#6B7280'],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $type => $items) {
            foreach ($items as $index => [$name, $color]) {
                Category::updateOrCreate(
                    ['name' => $name, 'type' => $type],
                    ['color' => $color, 'sort_order' => $index]
                );
            }
        }
    }
}
