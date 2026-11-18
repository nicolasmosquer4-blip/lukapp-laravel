<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalContribution extends Model
{
    public const TYPE_DEPOSIT = 'abonar';
    public const TYPE_WITHDRAW = 'retirar';

    protected $fillable = ['type', 'amount', 'comment'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
