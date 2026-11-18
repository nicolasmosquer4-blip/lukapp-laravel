<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /** GET /api/admin/users?q=texto&status=activo|bloqueado&page=1&per_page=15 */
    public function index(Request $request): AnonymousResourceCollection
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:activo,bloqueado'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->where('role', User::ROLE_USER)
            ->when($f['q'] ?? null, function (Builder $q, string $term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                $q->where(fn (Builder $w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($f['status'] ?? null, fn (Builder $q, $s) => $q->where('is_blocked', $s === 'bloqueado'))
            ->latest()->orderByDesc('id')
            ->paginate($f['per_page'] ?? 15)
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function show(User $user, AdminService $service): JsonResponse
    {
        return response()->json([
            'data' => (new UserResource($user))->resolve() + $service->userDetails($user),
        ]);
    }

    /** PUT /api/admin/users/{user}/block  { "blocked": true|false } */
    public function updateBlock(Request $request, User $user): JsonResponse
    {
        Gate::authorize('manage', $user);
        $data = $request->validate(['blocked' => ['required', 'boolean']]);

        $user->forceFill(['is_blocked' => $data['blocked']])->save();
        if ($user->is_blocked) {
            $user->tokens()->delete(); // revoca accesos API activos
        }

        return response()->json([
            'message' => $user->is_blocked ? 'Cuenta suspendida.' : 'Acceso restaurado.',
            'data' => (new UserResource($user))->resolve(),
        ]);
    }

    /** DELETE /api/admin/users/{user}/data — elimina movimientos, metas y notificaciones (conserva la cuenta). */
    public function resetData(User $user): JsonResponse
    {
        Gate::authorize('manage', $user);
        $user->resetFinancialData();

        return response()->json(['message' => 'Datos del usuario eliminados. La cuenta se conserva.']);
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('manage', $user);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
        $user->tokens()->delete();
        $user->notifications()->delete();
        $user->delete(); // cascada: movimientos, metas, abonos, preferencias

        return response()->json(['message' => 'Usuario eliminado permanentemente.']);
    }
}
