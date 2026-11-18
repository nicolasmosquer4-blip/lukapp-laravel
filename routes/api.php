<?php

use App\Http\Controllers\Api\Admin\SystemController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StatisticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST de LukApp  (prefijo /api)
|--------------------------------------------------------------------------
| Autenticación: Laravel Sanctum (cookie de sesión para el frontend web,
| token Bearer para clientes externos como Postman).
*/

// ---------- Públicas (con limitación de intentos) ----------
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('api.auth.register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('api.auth.login');
    Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:password')->name('api.auth.forgot');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:password')->name('api.auth.reset');
});

// ---------- Protegidas ----------
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('auth/me', [AuthController::class, 'me'])->name('api.auth.me');

    // Perfil y preferencias (cualquier rol)
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'updateAvatar']);
    Route::put('profile/password', [ProfileController::class, 'updatePassword']);
    Route::delete('profile', [ProfileController::class, 'destroy']);

    Route::get('settings', [SettingsController::class, 'show']);
    Route::put('settings', [SettingsController::class, 'update']);

    // Notificaciones
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::put('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->whereUuid('id');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->whereUuid('id');
    Route::delete('notifications', [NotificationController::class, 'destroyAll']);

    // Módulos financieros del usuario
    Route::middleware('role:user')->group(function () {
        Route::get('dashboard', DashboardController::class);
        Route::get('statistics', StatisticsController::class);
        Route::get('categories', [CategoryController::class, 'index']);

        Route::get('movements/export', [MovementController::class, 'export']);
        Route::delete('movements', [MovementController::class, 'bulkDestroy']);
        Route::apiResource('movements', MovementController::class)->whereNumber('movement');

        Route::get('goals/{goal}/contributions', [GoalController::class, 'contributions'])->whereNumber('goal');
        Route::post('goals/{goal}/contributions', [GoalController::class, 'contribute'])->whereNumber('goal');
        Route::apiResource('goals', GoalController::class)->whereNumber('goal');
    });

    // Panel de administración
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('kpis', [SystemController::class, 'kpis']);
        Route::get('stats', [SystemController::class, 'stats']);
        Route::get('system', [SystemController::class, 'system']);
        Route::put('system/demo-mode', [SystemController::class, 'updateDemoMode']);

        Route::get('users', [AdminUserController::class, 'index']);
        Route::get('users/{user}', [AdminUserController::class, 'show'])->whereNumber('user');
        Route::put('users/{user}/block', [AdminUserController::class, 'updateBlock'])->whereNumber('user');
        Route::delete('users/{user}/data', [AdminUserController::class, 'resetData'])->whereNumber('user');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->whereNumber('user');
    });
});

// Cualquier otra ruta /api/* → 404 en JSON
Route::fallback(fn () => response()->json(['message' => 'Endpoint no encontrado.'], 404));
