<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Services\Auth\TokenAuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, TokenAuthService $service): JsonResponse
    {
        $result = $service->attempt($request->validated());

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
