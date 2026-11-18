<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Goal */
class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'target' => (float) $this->target,
            'saved' => (float) $this->saved,
            'remaining' => max(0, (float) $this->target - (float) $this->saved),
            'progress' => $this->progress(),
            'completed' => $this->isCompleted(),
            'deadline' => $this->deadline?->format('Y-m-d'),
            'contributions' => GoalContributionResource::collection($this->whenLoaded('contributions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
