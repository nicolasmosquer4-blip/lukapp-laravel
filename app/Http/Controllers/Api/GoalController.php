<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoalContributionRequest;
use App\Http\Requests\GoalRequest;
use App\Http\Resources\GoalContributionResource;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GoalController extends Controller
{
    /** GET /api/goals?status=activas|completadas — incluye el resumen global en "summary". */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', 'in:activas,completadas']]);
        $base = $request->user()->goals();

        $goals = (clone $base)
            ->when($request->input('status') === 'activas', fn ($q) => $q->whereColumn('saved', '<', 'target'))
            ->when($request->input('status') === 'completadas', fn ($q) => $q->whereColumn('saved', '>=', 'target'))
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $totals = (clone $base)->selectRaw('COALESCE(SUM(saved),0) AS saved, COALESCE(SUM(target),0) AS target, COUNT(*) AS total')->first();
        $saved = (float) $totals->saved;
        $target = (float) $totals->target;

        return GoalResource::collection($goals)->additional([
            'summary' => [
                'saved' => $saved,
                'target' => $target,
                'progress' => $target > 0 ? min(100, round($saved / $target * 100, 1)) : 0,
                'count' => (int) $totals->total,
            ],
        ]);
    }

    public function store(GoalRequest $request): JsonResponse
    {
        $goal = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $goal = $request->user()->goals()->create([
                'name' => $data['name'],
                'icon' => $data['icon'] ?? '🎯',
                'target' => $data['target'],
                'saved' => $data['saved'] ?? 0,
                'deadline' => $data['deadline'] ?? null,
            ]);

            if ((float) $goal->saved > 0) {
                $goal->contributions()->create(['type' => 'abonar', 'amount' => $goal->saved, 'comment' => 'Ahorro inicial']);
            }

            return $goal;
        });

        return (new GoalResource($goal))->additional(['message' => 'Meta creada.'])
            ->response()->setStatusCode(201);
    }

    public function show(Goal $goal): GoalResource
    {
        Gate::authorize('view', $goal);

        return new GoalResource($goal->load('contributions'));
    }

    public function update(GoalRequest $request, Goal $goal, GoalService $service): GoalResource
    {
        Gate::authorize('update', $goal);
        $goal->update($request->safe()->only(['name', 'icon', 'target', 'deadline']));
        $service->syncCompletionFlag($goal);

        return (new GoalResource($goal))->additional(['message' => 'Meta actualizada.']);
    }

    public function destroy(Goal $goal): JsonResponse
    {
        Gate::authorize('delete', $goal);
        $goal->delete();

        return response()->json(['message' => 'Meta eliminada.']);
    }

    /** GET /api/goals/{goal}/contributions — historial de abonos y retiros. */
    public function contributions(Goal $goal): AnonymousResourceCollection
    {
        Gate::authorize('view', $goal);

        return GoalContributionResource::collection($goal->contributions()->paginate(20));
    }

    /** POST /api/goals/{goal}/contributions  { type: abonar|retirar, amount, comment? } */
    public function contribute(GoalContributionRequest $request, Goal $goal, GoalService $service): JsonResponse
    {
        Gate::authorize('update', $goal);

        $goal = $service->contribute($goal, $request->input('type'), (float) $request->input('amount'), $request->input('comment'));

        return (new GoalResource($goal))
            ->additional(['message' => $request->input('type') === 'abonar' ? 'Abono registrado.' : 'Retiro registrado.'])
            ->response()->setStatusCode(201);
    }
}
