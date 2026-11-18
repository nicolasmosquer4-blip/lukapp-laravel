<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StatisticsController extends Controller
{
    /** GET /api/statistics?period=6M|1A|TODO */
    public function __invoke(Request $request, StatisticsService $service): JsonResponse
    {
        $data = $request->validate(['period' => ['nullable', Rule::in(StatisticsService::PERIODS)]]);

        return response()->json(['data' => $service->build($request->user(), $data['period'] ?? '6M')]);
    }
}
