<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Movement;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Genera los datos de ejemplo que antes vivían en data/demoData.js.
 * Las fechas se calculan relativas a hoy para que el dashboard y las
 * estadísticas siempre muestren información de los últimos meses.
 */
class DemoDataService
{
    /** [descripción, tipo, categoría, día del mes, monto] por cada mes */
    private const MONTHLY_TEMPLATE = [
        ['Salario mensual', 'ingreso', 'Salario', 25, 4200000],
        ['Proyecto freelance', 'ingreso', 'Freelance', 22, 850000],
        ['Supermercado', 'gasto', 'Alimentación', 20, 248000],
        ['Arriendo', 'gasto', 'Vivienda', 5, 1200000],
        ['Transporte', 'gasto', 'Transporte', 17, 186000],
        ['Internet', 'gasto', 'Servicios', 15, 95000],
        ['Netflix', 'gasto', 'Entretenimiento', 12, 31900],
        ['Restaurante', 'gasto', 'Alimentación', 11, 72000],
        ['Venta de producto', 'ingreso', 'Ventas', 9, 350000],
        ['Curso online', 'gasto', 'Educación', 7, 120000],
        ['Farmacia', 'gasto', 'Salud', 4, 64000],
        ['Taxi', 'gasto', 'Transporte', 3, 28000],
    ];

    private const GOALS = [
        ['Viaje a Europa', '✈️', 6000000, 2400000, '+8 months'],
        ['Computador', '💻', 4500000, 3330000, '+3 months'],
        ['Fondo de emergencia', '💰', 3000000, 1050000, '+13 months'],
    ];

    public function seedFor(User $user, int $months = 6): void
    {
        $categories = Category::all()->keyBy(fn (Category $c) => $c->type.'|'.$c->name);

        DB::transaction(function () use ($user, $months, $categories) {
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = Carbon::today()->startOfMonth()->subMonths($i);
                // Pequeña variación por mes para que las gráficas no sean planas
                $factor = 1 + (($i % 3) - 1) * 0.08;

                foreach (self::MONTHLY_TEMPLATE as [$description, $type, $categoryName, $day, $amount]) {
                    $date = $month->copy()->day(min($day, $month->daysInMonth));
                    // En el mes actual solo se registran los movimientos hasta hoy
                    if ($i === 0 && $date->isFuture()) {
                        continue;
                    }

                    $user->movements()->create([
                        'category_id' => $categories[$type.'|'.$categoryName]->id,
                        'type' => $type,
                        'description' => $description,
                        'amount' => round($amount * ($type === 'gasto' ? $factor : 1)),
                        'date' => $date->toDateString(),
                        'status' => Movement::statusForDate($date),
                    ]);
                }
            }

            foreach (self::GOALS as [$name, $icon, $target, $saved, $deadline]) {
                $goal = $user->goals()->create([
                    'name' => $name,
                    'icon' => $icon,
                    'target' => $target,
                    'saved' => $saved,
                    'deadline' => Carbon::today()->modify($deadline)->toDateString(),
                ]);
                $goal->contributions()->create(['type' => 'abonar', 'amount' => $saved, 'comment' => 'Ahorro inicial']);
            }

            $user->notify(new AppNotification(
                'Bienvenido a LukApp',
                'Tu panel financiero ya está listo para usar.',
                'info'
            ));
        });
    }
}
