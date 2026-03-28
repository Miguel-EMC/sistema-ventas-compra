<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticatedUserController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => UserResource::make(
                $request->user()->load('role'),
            )->toArray($request),
        ]);
    }
}
