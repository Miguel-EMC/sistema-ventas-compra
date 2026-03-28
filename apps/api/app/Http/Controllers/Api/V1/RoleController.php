<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        return RoleResource::collection(
            Role::query()->orderBy('name')->get(),
        );
    }
}
