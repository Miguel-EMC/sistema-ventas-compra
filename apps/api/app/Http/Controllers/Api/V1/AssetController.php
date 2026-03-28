<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Assets\AssetManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\StoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', ''));

        $assets = Asset::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return AssetResource::collection($assets);
    }

    public function store(StoreAssetRequest $request, AssetManagementService $service)
    {
        return AssetResource::make(
            $service->create($request->validated())->load('category'),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Asset $asset): AssetResource
    {
        return AssetResource::make($asset->load('category'));
    }

    public function update(
        UpdateAssetRequest $request,
        Asset $asset,
        AssetManagementService $service,
    ): AssetResource {
        return AssetResource::make(
            $service->update($asset, $request->validated())->load('category'),
        );
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return response()->json([
            'message' => 'Activo eliminado correctamente.',
        ]);
    }
}
