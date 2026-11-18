<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de acceso por rol. Uso: ->middleware('role:admin') o 'role:user'.
 * En la web redirige al panel correcto (igual que los "route guards" de app.js);
 * en la API responde 403.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user && in_array($user->role, $roles, true)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        return redirect()->route($user?->isAdmin() ? 'admin' : 'dashboard');
    }
}
