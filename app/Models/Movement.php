<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Movement extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'ingreso';
    public const TYPE_EXPENSE = 'gasto';
    public const TYPES = [self::TYPE_INCOME, self::TYPE_EXPENSE];

    public const STATUS_COMPLETED = 'completado';
    public const STATUS_PENDING = 'pendiente';
    public const STATUS_CANCELLED = 'cancelado';
    public const STATUSES = [self::STATUS_COMPLETED, self::STATUS_PENDING, self::STATUS_CANCELLED];

    protected $fillable = ['category_id', 'type', 'description', 'amount', 'date', 'status'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date:Y-m-d',
        ];
    }

    /* ---------------- Relaciones ---------------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /* ---------------- Reglas de negocio ---------------- */

    /**
     * Regla heredada del frontend (movimientoModel.js → determineStatus):
     * una fecha futura queda "pendiente"; hoy o pasada queda "completado".
     */
    public static function statusForDate(CarbonInterface|string $date): string
    {
        $date = $date instanceof CarbonInterface ? $date->copy() : Carbon::parse($date);

        return $date->startOfDay()->greaterThan(Carbon::today())
            ? self::STATUS_PENDING
            : self::STATUS_COMPLETED;
    }

    /**
     * Sincroniza en una sola consulta los estados que cambiaron con el paso de los días
     * (equivalente a la "auditoría automática" del frontend, pero en SQL).
     */
    public static function syncStatusesForUser(int $userId): void
    {
        $today = Carbon::today()->toDateString();

        static::query()->where('user_id', $userId)
            ->where('status', self::STATUS_PENDING)
            ->whereDate('date', '<=', $today)
            ->update(['status' => self::STATUS_COMPLETED]);

        static::query()->where('user_id', $userId)
            ->where('status', self::STATUS_COMPLETED)
            ->whereDate('date', '>', $today)
            ->update(['status' => self::STATUS_PENDING]);
    }

    /* ---------------- Scopes ---------------- */

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return in_array($type, self::TYPES, true) ? $query->where('type', $type) : $query;
    }

    /** Búsqueda por descripción, categoría o fecha (como el filtro original de la tabla). */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('description', 'like', $like)
                ->orWhere('date', 'like', $like)
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like));
        });
    }

    /** Excluye cancelados (usado por dashboard y estadísticas). */
    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /** Solo completados (usado por los totales de la tabla de movimientos). */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
