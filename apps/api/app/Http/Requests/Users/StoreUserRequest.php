<?php

namespace App\Http\Requests\Users;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isSuperadmin = $this->user()?->role?->slug === 'superadmin';

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'is_active' => ['sometimes', 'boolean'],
            'company_id' => array_values(array_filter([
                $isSuperadmin ? 'nullable' : 'prohibited',
                'uuid',
                Rule::exists('companies', 'id'),
            ])),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $role = Role::query()->find($this->integer('role_id'));

            if ($role === null) {
                return;
            }

            $isSuperadmin = $this->user()?->role?->slug === 'superadmin';
            $companyId = $this->input('company_id');

            if (! $isSuperadmin && $role->slug === 'superadmin') {
                $validator->errors()->add('role_id', 'Solo el superadmin puede asignar ese rol.');
            }

            if ($role->slug === 'superadmin' && $companyId !== null) {
                $validator->errors()->add('company_id', 'El superadmin no debe estar vinculado a una empresa.');
            }

            if ($isSuperadmin && $role->slug !== 'superadmin' && $companyId === null) {
                $validator->errors()->add('company_id', 'Debes asignar una empresa para usuarios tenant.');
            }
        });
    }
}
