<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    /** Envía el enlace de recuperación por correo. */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink(['email' => mb_strtolower(trim($request->input('email')))]);

        if ($status === Password::RESET_THROTTLED) {
            return response()->json(['message' => __($status)], 429);
        }

        // Respuesta idéntica exista o no el correo (evita enumeración de usuarios)
        return response()->json([
            'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
        ]);
    }

    /** Restablece la contraseña con el token recibido por correo. */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(6)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $user->tokens()->delete(); // invalida tokens API previos
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 400);
        }

        return response()->json([
            'message' => 'Tu contraseña fue restablecida. Ya puedes iniciar sesión.',
            'redirect' => route('login'),
        ]);
    }
}
