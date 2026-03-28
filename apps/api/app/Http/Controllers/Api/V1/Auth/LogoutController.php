<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Services\Auth\TokenAuthService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request, TokenAuthService $service): JsonResponse
    {
        $service->logout($request->user());

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }
}
