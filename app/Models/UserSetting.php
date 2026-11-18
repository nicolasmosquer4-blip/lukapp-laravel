<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    public const NOTIFICATION_KEYS = [
        'notif_email', 'notif_push', 'notif_weekly', 'notif_monthly', 'notif_alerts', 'notif_goals',
    ];

    public const APPEARANCE_KEYS = ['theme', 'currency', 'language'];

    protected $fillable = [
        'theme', 'currency', 'language',
        'notif_email', 'notif_push', 'notif_weekly', 'notif_monthly', 'notif_alerts', 'notif_goals',
    ];

    /** Valores por defecto (iguales a DEFAULT_SETTINGS del frontend original). */
    protected $attributes = [
        'theme' => 'light',
        'currency' => 'COP',
        'language' => 'es',
        'notif_email' => true,
        'notif_push' => true,
        'notif_weekly' => false,
        'notif_monthly' => true,
        'notif_alerts' => true,
        'notif_goals' => true,
    ];

    protected function casts(): array
    {
        return [
            'notif_email' => 'boolean',
            'notif_push' => 'boolean',
            'notif_weekly' => 'boolean',
            'notif_monthly' => 'boolean',
            'notif_alerts' => 'boolean',
            'notif_goals' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Estructura agrupada que espera el frontend: { appearance: {...}, notifications: {...} } */
    public function toGroupedArray(): array
    {
        return [
            'appearance' => $this->only(self::APPEARANCE_KEYS),
            'notifications' => collect(self::NOTIFICATION_KEYS)
                ->mapWithKeys(fn ($key) => [$key => (bool) $this->{$key}])
                ->all(),
        ];
    }
}
