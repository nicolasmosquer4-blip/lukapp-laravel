<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\DemoDataService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Cuentas iniciales (las mismas que el frontend creaba en localStorage):
     *  - administrador@lukapp.com / 123456  (rol admin)
     *  - usuario@lukapp.com       / 123456  (usuario demo con datos)
     * Cambia estas contraseñas fuera del entorno local.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $admin = User::firstOrNew(['email' => 'administrador@lukapp.com']);
        $admin->fill(['name' => 'Administrador del Sistema', 'password' => '123456']);
        $admin->role = User::ROLE_ADMIN;
        $admin->plan = 'Admin';
        $admin->save();
        $admin->settingsOrDefault();

        $demo = User::firstOrNew(['email' => 'usuario@lukapp.com']);
        $isNew = ! $demo->exists;
        $demo->fill([
            'name' => 'Daniel Gutiérrez',
            'phone' => '+57 300 000 0000',
            'occupation' => 'Analista financiero',
            'password' => '123456',
        ]);
        $demo->plan = 'Premium';
        $demo->save();
        $demo->settingsOrDefault();

        if ($isNew) {
            app(DemoDataService::class)->seedFor($demo);
        }

        // Usuarios adicionales de prueba para el panel de administración
        if (User::where('role', User::ROLE_USER)->count() < 6) {
            User::factory(5)->create()->each(fn (User $u) => $u->settingsOrDefault());
        }
    }
}
