<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';

    /**
     * Atributos asignables masivamente. "role" e "is_blocked" NO se incluyen
     * a propósito: solo se modifican desde código controlado (admin / seeders).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'occupation',
        'password',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var list<string> */
    protected $appends = ['avatar_url'];

    /** Valores por defecto en memoria (iguales a los DEFAULT de la migración). */
    protected $attributes = [
        'role' => self::ROLE_USER,
        'plan' => 'Usuario',
        'is_blocked' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_access_at' => 'datetime',
            'is_blocked' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /* ---------------- Relaciones ---------------- */

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function goalContributions(): HasManyThrough
    {
        return $this->hasManyThrough(GoalContribution::class, Goal::class);
    }

    /* ---------------- Helpers ---------------- */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Devuelve las preferencias del usuario, creándolas con valores por defecto si no existen. */
    public function settingsOrDefault(): UserSetting
    {
        if (! $this->settings) {
            // firstOrCreate evita duplicados y setRelation actualiza la relación ya cargada
            $this->setRelation('settings', $this->settings()->firstOrCreate([]));
        }

        return $this->settings;
    }

    /** Elimina movimientos, metas y notificaciones (conserva la cuenta). */
    public function resetFinancialData(): void
    {
        $this->movements()->delete();
        $this->goals()->delete(); // goal_contributions se borran en cascada
        $this->notifications()->delete();
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar_path
            ? Storage::disk('public')->url($this->avatar_path)
            : null);
    }
}
