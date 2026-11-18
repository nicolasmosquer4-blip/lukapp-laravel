<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si un administrador bloquea una cuenta, la sesión/token del usuario deja de ser válida
 * inmediatamente (no solo en el siguiente inicio de sesión).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_blocked) {
            return $next($request);
        }

        if ($token = $user->currentAccessToken()) {
            if (method_exists($token, 'delete')) {
                $token->delete();
            }
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $message = 'Tu cuenta ha sido suspendida por un administrador.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('login')->with('status_error', $message);
    }
}
