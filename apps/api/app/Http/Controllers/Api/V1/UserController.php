<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Users\UserManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $users = User::query()
            ->with('role')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request, UserManagementService $service): JsonResponse
    {
        $user = $service->create($request->validated(), $request->user());

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): UserResource
    {
        return UserResource::make($user->load('role'));
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        UserManagementService $service,
    ): UserResource {
        return UserResource::make(
            $service->update($user, $request->validated()),
        );
    }

    public function destroy(Request $request, User $user, UserManagementService $service): JsonResponse
    {
        if ($request->user()?->is($user)) {
            return response()->json([
                'message' => 'No puedes eliminar tu propio usuario.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $service->delete($user);

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
