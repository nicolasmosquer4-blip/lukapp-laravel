<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\DemoDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Autenticación híbrida con Laravel Sanctum:
 *  - Navegador (mismo dominio): sesión con cookie + protección CSRF.
 *  - Clientes externos (Postman/móvil): token Bearer en la respuesta.
 */
class AuthController extends Controller
{
    public function register(RegisterRequest $request, DemoDataService $demo): JsonResponse
    {
        $user = DB::transaction(function () use ($request, $demo) {
            $user = User::create($request->safe()->only(['name', 'email', 'phone', 'occupation', 'password']));
            $user->settingsOrDefault();

            if (SystemSetting::demoModeEnabled()) {
                $demo->seedFor($user);
            } else {
                $user->notify(new AppNotification('Bienvenido a LukApp', 'Tu panel financiero ya está listo para usar.', 'info'));
            }

            return $user;
        });

        return $this->authenticated($request, $user, true, 'Registro exitoso. Preparando tu panel...', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        // Mensaje genérico: no revela si el correo existe
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Correo o contraseña incorrectos.'], 401);
        }

        if ($user->is_blocked) {
            return response()->json(['message' => 'Tu cuenta ha sido suspendida por un administrador.'], 403);
        }

        $user->forceFill(['last_access_at' => now()])->save();

        return $this->authenticated($request, $user, $request->boolean('remember', true), 'Sesión iniciada. Redirigiendo...');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => (new UserResource($user))->resolve(),
            'settings' => $user->settingsOrDefault()->toGroupedArray(),
        ]);
    }

    private function authenticated(Request $request, User $user, bool $remember, string $message, int $status = 200): JsonResponse
    {
        $payload = [
            'message' => $message,
            'data' => (new UserResource($user))->resolve(),
            'redirect' => $user->isAdmin() ? route('admin') : route('dashboard'),
        ];

        if ($request->hasSession()) {
            Auth::guard('web')->login($user, $remember);
            $request->session()->regenerate();
        } else {
            $payload['token_type'] = 'Bearer';
            $payload['token'] = $user->createToken($request->input('device_name', 'api-client'))->plainTextToken;
        }

        return response()->json($payload, $status);
    }
}
