<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'occupation' => $this->occupation,
            'avatar' => $this->avatar_url,
            'role' => $this->role,
            'plan' => $this->plan,
            'blocked' => (bool) $this->is_blocked,
            'last_access_at' => $this->last_access_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
