<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Services\Auth\LegacyBridgeAuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LegacyBridgeLoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class LegacyBridgeLoginController extends Controller
{
    public function __invoke(
        LegacyBridgeLoginRequest $request,
        LegacyBridgeAuthService $service
    ): JsonResponse {
        $result = $service->exchange($request->validated());

        return response()->json([
            'data' => [
                'user' => UserResource::make($result['user'])->toArray($request),
            ],
            'meta' => [
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
        ]);
    }
}
