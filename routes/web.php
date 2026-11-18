<?php

use App\Http\Controllers\Web\PageController;
use Illuminate\Support\Facades\Route;

// Punto de entrada (equivalente a index.html): redirige según la sesión
Route::get('/', [PageController::class, 'home'])->name('home');

// ---------- Invitados ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [PageController::class, 'login'])->name('login');
    Route::get('/registro', [PageController::class, 'register'])->name('register');
    Route::get('/olvide-contrasena', [PageController::class, 'forgotPassword'])->name('password.request');
    Route::get('/restablecer-contrasena/{token}', [PageController::class, 'resetPassword'])->name('password.reset');
});

// ---------- Usuario autenticado ----------
Route::middleware(['auth', 'active', 'role:user'])->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/movimientos', [PageController::class, 'movements'])->name('movements');
    Route::get('/estadisticas', [PageController::class, 'statistics'])->name('statistics');
    Route::get('/metas', [PageController::class, 'goals'])->name('goals');
    Route::get('/configuracion', [PageController::class, 'settings'])->name('settings');
});

// ---------- Administrador ----------
Route::middleware(['auth', 'active', 'role:admin'])->group(function () {
    Route::get('/admin', [PageController::class, 'admin'])->name('admin');
});

// Compatibilidad con los enlaces antiguos *.html del frontend estático
foreach ([
    'index' => '/', 'login' => '/login', 'registro' => '/registro', 'dashboard' => '/dashboard',
    'movimientos' => '/movimientos', 'estadisticas' => '/estadisticas', 'metas' => '/metas',
    'configuracion' => '/configuracion', 'admin' => '/admin',
] as $legacy => $target) {
    Route::redirect("/{$legacy}.html", $target, 301);
}
