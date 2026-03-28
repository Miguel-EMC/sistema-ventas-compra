<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Partners\CustomerManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partners\StoreCustomerRequest;
use App\Http\Requests\Partners\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', ''));

        $customers = Customer::query()
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

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request, CustomerManagementService $service)
    {
        return CustomerResource::make(
            $service->create($request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Customer $customer): CustomerResource
    {
        return CustomerResource::make($customer);
    }

    public function update(
        UpdateCustomerRequest $request,
        Customer $customer,
        CustomerManagementService $service,
    ): CustomerResource {
        return CustomerResource::make(
            $service->update($customer, $request->validated()),
        );
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json([
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }
}
