<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->settingsOrDefault()->toGroupedArray()]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = $request->user()->settingsOrDefault();
        $settings->update($request->validated());

        return response()->json([
            'message' => 'Preferencias guardadas.',
            'data' => $settings->fresh()->toGroupedArray(),
        ]);
    }
}
