<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Partners\SupplierManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partners\StoreSupplierRequest;
use App\Http\Requests\Partners\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', ''));

        $suppliers = Supplier::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request, SupplierManagementService $service)
    {
        return SupplierResource::make(
            $service->create($request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        return SupplierResource::make($supplier);
    }

    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier,
        SupplierManagementService $service,
    ): SupplierResource {
        return SupplierResource::make(
            $service->update($supplier, $request->validated()),
        );
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return response()->json([
            'message' => 'Proveedor eliminado correctamente.',
        ]);
    }
}
