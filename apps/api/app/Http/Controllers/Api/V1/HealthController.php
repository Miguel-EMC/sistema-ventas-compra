<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'app' => config('app.name'),
            'version' => 'v1',
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'frontend_url' => config('app.frontend_url'),
            'database_connection' => config('database.default'),
        ]);
    }
}
