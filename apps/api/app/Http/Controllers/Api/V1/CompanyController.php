<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Tenants\TenantOnboardingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Companies\StoreCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $companies = Company::query()
            ->with(['users.role'])
            ->latest('created_at')
            ->get();

        return CompanyResource::collection($companies);
    }

    public function store(
        StoreCompanyRequest $request,
        TenantOnboardingService $service,
    ): JsonResponse {
        $validated = $request->validated();
        $temporaryPassword = $this->generateTemporaryPassword();
        $adminUsername = $this->generateAdminUsername((string) $validated['domain']);

        $result = $service->registerNewCompany(
            [
                'name' => $validated['name'],
                'domain' => $validated['domain'],
                'status' => 'pending',
                'plan_id' => $validated['plan'],
            ],
            [
                'name' => sprintf('Administrador %s', trim((string) $validated['name'])),
                'username' => $adminUsername,
                'display_name' => 'Administrador',
                'email' => $validated['admin_email'],
                'password' => $temporaryPassword,
            ],
        );

        $company = $result['company']->load(['users.role']);

        return response()->json([
            'data' => [
                'company' => CompanyResource::make($company)->resolve(),
                'provisioning' => [
                    'admin_email' => $validated['admin_email'],
                    'admin_username' => $adminUsername,
                    'temporary_password' => $temporaryPassword,
                ],
            ],
        ], Response::HTTP_CREATED);
    }

    private function generateAdminUsername(string $domain): string
    {
        $base = trim(Str::slug($domain, '-'));
        $candidate = $base.'-admin';
        $suffix = 1;

        while (User::withoutGlobalScopes()->where('username', $candidate)->exists()) {
            $suffix++;
            $candidate = sprintf('%s-admin-%d', $base, $suffix);
        }

        return $candidate;
    }

    private function generateTemporaryPassword(): string
    {
        return Str::password(14, true, true, false, false);
    }
}
