<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function kpis(AdminService $service): JsonResponse
    {
        return response()->json(['data' => $service->kpis()]);
    }

    public function stats(AdminService $service): JsonResponse
    {
        return response()->json(['data' => $service->globalStats()]);
    }

    public function system(AdminService $service): JsonResponse
    {
        return response()->json(['data' => $service->systemMetrics()]);
    }

    /** PUT /api/admin/system/demo-mode  { "enabled": true } — los nuevos registros reciben datos de ejemplo. */
    public function updateDemoMode(Request $request): JsonResponse
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        SystemSetting::setValue('demo_mode', $data['enabled'] ? '1' : '0');

        return response()->json([
            'message' => $data['enabled'] ? 'Modo demo activado.' : 'Modo demo desactivado.',
            'data' => ['demoStatus' => (bool) $data['enabled']],
        ]);
    }
}
