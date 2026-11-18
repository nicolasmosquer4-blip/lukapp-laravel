<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteMovementsRequest;
use App\Http\Requests\MovementRequest;
use App\Http\Resources\MovementResource;
use App\Models\Movement;
use App\Services\MovementAlertService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovementController extends Controller
{
    /**
     * GET /api/movements?type=ingreso|gasto&q=texto&status=&from=&to=&page=1&per_page=8
     * Lista paginada + totales del conjunto filtrado (solo movimientos completados).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->validateFilters($request);
        $user = $request->user();
        Movement::syncStatusesForUser($user->id);

        $query = $this->filteredQuery($request, $filters);

        $totals = (clone $query)->reorder()->completed()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'ingreso' THEN amount ELSE 0 END), 0) AS income")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'gasto' THEN amount ELSE 0 END), 0) AS expense")
            ->first();

        $page = $query->with('category')->paginate($filters['per_page'] ?? 8)->withQueryString();

        return MovementResource::collection($page)->additional([
            'totals' => [
                'income' => (float) $totals->income,
                'expense' => (float) $totals->expense,
                'balance' => (float) $totals->income - (float) $totals->expense,
            ],
        ]);
    }

    public function store(MovementRequest $request, MovementAlertService $alerts): JsonResponse
    {
        $movement = $request->user()->movements()->create($request->movementData());
        $movement->load('category');
        $alerts->afterCreate($request->user(), $movement);

        return (new MovementResource($movement))
            ->additional(['message' => $movement->type === Movement::TYPE_INCOME ? 'Ingreso registrado.' : 'Gasto registrado.'])
            ->response()->setStatusCode(201);
    }

    public function show(Movement $movement): MovementResource
    {
        Gate::authorize('view', $movement);

        return new MovementResource($movement->load('category'));
    }

    public function update(MovementRequest $request, Movement $movement): MovementResource
    {
        Gate::authorize('update', $movement);
        $movement->update($request->movementData());

        return (new MovementResource($movement->load('category')))->additional(['message' => 'Movimiento actualizado.']);
    }

    public function destroy(Movement $movement): JsonResponse
    {
        Gate::authorize('delete', $movement);
        $movement->delete();

        return response()->json(['message' => 'Movimiento eliminado.']);
    }

    /** DELETE /api/movements  { "ids": [1,2,3] } — solo borra los que pertenecen al usuario. */
    public function bulkDestroy(BulkDeleteMovementsRequest $request): JsonResponse
    {
        $deleted = $request->user()->movements()->whereIn('id', $request->input('ids'))->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'No se encontraron movimientos para eliminar.'], 404);
        }

        return response()->json([
            'message' => $deleted === 1 ? '1 movimiento eliminado.' : "{$deleted} movimientos eliminados.",
            'deleted' => $deleted,
        ]);
    }

    /** GET /api/movements/export?type=&q= — descarga CSV con los mismos filtros de la tabla. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validateFilters($request);
        $query = $this->filteredQuery($request, $filters)->with('category');
        $filename = 'lukapp-movimientos-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel reconozca UTF-8
            fputcsv($out, ['Descripcion', 'Tipo', 'Categoria', 'Fecha', 'Monto', 'Estado']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $m) {
                    fputcsv($out, [
                        $this->csvSafe($m->description), $m->type, $m->category?->name,
                        $m->date->format('Y-m-d'), (float) $m->amount, $m->status,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'type' => ['nullable', 'in:todos,ingreso,gasto'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:completado,pendiente,cancelado'],
            'category_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function filteredQuery(Request $request, array $f): Builder
    {
        return Movement::query()
            ->where('user_id', $request->user()->id)
            ->ofType($f['type'] ?? null)
            ->search($f['q'] ?? null)
            ->when($f['status'] ?? null, fn (Builder $q, $s) => $q->where('status', $s))
            ->when($f['category_id'] ?? null, fn (Builder $q, $c) => $q->where('category_id', $c))
            ->when($f['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('date', '>=', $d))
            ->when($f['to'] ?? null, fn (Builder $q, $d) => $q->whereDate('date', '<=', $d))
            ->orderByDesc('date')->orderByDesc('id');
    }

    /** Evita inyección de fórmulas al abrir el CSV en Excel. */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
