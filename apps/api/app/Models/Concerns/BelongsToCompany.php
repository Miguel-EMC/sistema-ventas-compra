<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope());

        static::creating(function ($model): void {
            if (! property_exists($model, 'fillable') || ! in_array('company_id', $model->getFillable(), true)) {
                return;
            }

            if ($model->getAttribute('company_id') !== null) {
                return;
            }

            $user = Auth::user();

            if ($user?->company_id !== null) {
                $model->setAttribute('company_id', $user->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
