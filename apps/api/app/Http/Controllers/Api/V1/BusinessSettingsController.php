<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Settings\BusinessSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBusinessSettingsRequest;
use App\Http\Resources\CompanyProfileResource;
use App\Http\Resources\CurrencyResource;
use App\Http\Resources\LocaleResource;
use Illuminate\Http\JsonResponse;

class BusinessSettingsController extends Controller
{
    public function show(BusinessSettingsService $service): JsonResponse
    {
        return response()->json([
            'data' => $this->transform($service->show()),
        ]);
    }

    public function update(UpdateBusinessSettingsRequest $request, BusinessSettingsService $service): JsonResponse
    {
        return response()->json([
            'data' => $this->transform($service->update($request->validated())),
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function transform(array $settings): array
    {
        return [
            'company_profile' => (new CompanyProfileResource($settings['company_profile']))->resolve(),
            'currency' => $settings['currency'] ? (new CurrencyResource($settings['currency']))->resolve() : null,
            'locale' => $settings['locale'] ? (new LocaleResource($settings['locale']))->resolve() : null,
            'currencies' => CurrencyResource::collection($settings['currencies'])->resolve(),
            'locales' => LocaleResource::collection($settings['locales'])->resolve(),
            'system_settings' => $settings['system_settings'],
        ];
    }
}
