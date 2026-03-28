<?php

namespace App\Application\Services\Partners;

use App\Models\Customer;
use Illuminate\Support\Str;

class CustomerManagementService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Customer
    {
        return Customer::query()->create([
            'public_id' => (string) Str::uuid(),
            'document_type' => $payload['document_type'] ?? null,
            'document_number' => $payload['document_number'] ?? null,
            'name' => $payload['name'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Customer $customer, array $payload): Customer
    {
        $customer->update([
            'document_type' => $payload['document_type'] ?? null,
            'document_number' => $payload['document_number'] ?? null,
            'name' => $payload['name'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        return $customer->fresh();
    }
}
