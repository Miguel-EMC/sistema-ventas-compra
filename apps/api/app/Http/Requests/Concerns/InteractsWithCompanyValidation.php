<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

trait InteractsWithCompanyValidation
{
    protected function uniqueForCurrentCompany(string $table, string $column, mixed $ignoreId = null): Unique
    {
        $rule = Rule::unique($table, $column);

        if ($ignoreId !== null) {
            $rule = $rule->ignore($ignoreId);
        }

        return $rule->where(function (Builder $query): void {
            $companyId = $this->user()?->company_id;

            if ($companyId === null) {
                $query->whereNull('company_id');
                return;
            }

            $query->where('company_id', $companyId);
        });
    }

    protected function existsForCurrentCompany(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where(function (Builder $query): void {
            $companyId = $this->user()?->company_id;

            if ($companyId === null) {
                $query->whereNull('company_id');
                return;
            }

            $query->where('company_id', $companyId);
        });
    }
}
