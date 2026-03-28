<?php

namespace App\Application\Services\Partners;

use App\Models\Supplier;
use Illuminate\Support\Str;

class SupplierManagementService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Supplier
    {
        return Supplier::query()->create([
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
    public function update(Supplier $supplier, array $payload): Supplier
    {
        $supplier->update([
            'document_type' => $payload['document_type'] ?? null,
            'document_number' => $payload['document_number'] ?? null,
            'name' => $payload['name'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        return $supplier->fresh();
    }
}
