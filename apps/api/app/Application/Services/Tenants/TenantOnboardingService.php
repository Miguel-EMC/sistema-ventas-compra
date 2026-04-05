<?php

namespace App\Application\Services\Tenants;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantOnboardingService
{
    /**
     * @param array{name:string,domain:string,status?:string|null,plan_id?:string|null} $companyData
     * @param array{name:string,username:string,display_name?:string|null,email?:string|null,password:string} $adminData
     * @return array{company: Company, admin: User}
     */
    public function registerNewCompany(array $companyData, array $adminData): array
    {
        return DB::transaction(function () use ($companyData, $adminData): array {
            $adminRole = Role::query()->where('slug', 'admin')->first();

            if (! $adminRole instanceof Role) {
                throw ValidationException::withMessages([
                    'role' => 'No existe un rol administrador disponible para el onboarding.',
                ]);
            }

            $company = Company::query()->create([
                'name' => trim((string) $companyData['name']),
                'domain' => trim((string) $companyData['domain']),
                'status' => trim((string) ($companyData['status'] ?? 'active')),
                'plan_id' => $this->nullableText($companyData['plan_id'] ?? null),
            ]);

            $admin = User::query()->create([
                'public_id' => (string) Str::uuid(),
                'company_id' => $company->id,
                'name' => trim((string) $adminData['name']),
                'username' => trim((string) $adminData['username']),
                'display_name' => $this->nullableText($adminData['display_name'] ?? null) ?? trim((string) $adminData['name']),
                'email' => $this->nullableText($adminData['email'] ?? null),
                'password' => $adminData['password'],
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]);

            return [
                'company' => $company,
                'admin' => $admin->load('role', 'company'),
            ];
        });
    }

    private function nullableText(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
