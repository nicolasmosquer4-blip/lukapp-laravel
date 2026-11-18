<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'data' => (new UserResource($user))->resolve(),
        ]);
    }

    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        $disk = Storage::disk('public');

        // Nombre aleatorio generado por Laravel (nunca el nombre original del cliente)
        $path = $request->file('avatar')->store('avatars', 'public');

        if ($user->avatar_path && $disk->exists($user->avatar_path)) {
            $disk->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => $path])->save();

        return response()->json([
            'message' => 'Foto de perfil actualizada.',
            'data' => (new UserResource($user))->resolve(),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->input('password')])->save();

        // Cierra las demás sesiones/tokens del usuario
        $current = $user->currentAccessToken();
        $tokens = $user->tokens();
        if ($current instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $tokens->whereKeyNot($current->getKey());
        }
        $tokens->delete();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return response()->json(['message' => 'La cuenta de administrador está protegida y no puede eliminarse.'], 403);
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->tokens()->delete();
        $user->notifications()->delete();

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // movements, goals, goal_contributions y user_settings se eliminan en cascada (FK)
        $user->delete();

        return response()->json(['message' => 'Tu cuenta fue eliminada.', 'redirect' => route('login')]);
    }
}
