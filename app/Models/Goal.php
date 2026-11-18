<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'target', 'saved', 'deadline'];

    protected function casts(): array
    {
        return [
            'target' => 'decimal:2',
            'saved' => 'decimal:2',
            'deadline' => 'date:Y-m-d',
            'completed_notified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class)->latest();
    }

    public function isCompleted(): bool
    {
        return (float) $this->saved >= (float) $this->target;
    }

    public function progress(): float
    {
        return (float) $this->target > 0 ? round(((float) $this->saved / (float) $this->target) * 100, 2) : 0.0;
    }
}
