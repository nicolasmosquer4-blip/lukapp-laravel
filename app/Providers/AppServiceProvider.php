<?php

namespace App\Providers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        // Protección contra fuerza bruta en login/registro y recuperación de contraseña
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        // El enlace del correo de recuperación apunta a la vista Blade de LukApp
        ResetPassword::createUrlUsing(fn (User $user, string $token) => route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        // Datos del usuario para el layout (topbar/sidebar renderizados en el servidor)
        View::composer(['layouts.*', 'partials.*', 'pages.*', 'auth.*'], function ($view) {
            $user = auth()->user();
            $view->with('authUser', $user);
            $view->with('themePreference', $user?->settings?->theme ?? 'light');
        });

        // Datos de arranque para el JS (window.LUKAPP): usuario, tema y URLs de las páginas
        View::composer('layouts.base', function ($view) {
            $user = auth()->user();
            $view->with('lukappBoot', [
                'apiBase' => url('/api'),
                'user' => $user ? (new UserResource($user))->resolve() : null,
                'theme' => $user?->settings?->theme ?? 'light',
                'routes' => collect([
                    'home' => 'home', 'login' => 'login', 'register' => 'register', 'forgot' => 'password.request',
                    'dashboard' => 'dashboard', 'movements' => 'movements', 'statistics' => 'statistics',
                    'goals' => 'goals', 'settings' => 'settings', 'admin' => 'admin',
                ])->map(fn (string $name) => route($name))->all(),
            ]);
        });
    }
}
