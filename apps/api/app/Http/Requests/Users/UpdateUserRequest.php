<?php

namespace App\Http\Requests\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');
        $isSuperadmin = $this->user()?->role?->slug === 'superadmin';

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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

            /** @var User $targetUser */
            $targetUser = $this->route('user');
            $isSuperadmin = $this->user()?->role?->slug === 'superadmin';
            $targetCompanyId = $this->has('company_id')
                ? $this->input('company_id')
                : $targetUser->company_id;

            if (! $isSuperadmin && $role->slug === 'superadmin') {
                $validator->errors()->add('role_id', 'Solo el superadmin puede asignar ese rol.');
            }

            if ($role->slug === 'superadmin' && $targetCompanyId !== null) {
                $validator->errors()->add('company_id', 'El superadmin no debe estar vinculado a una empresa.');
            }

            if ($isSuperadmin && $role->slug !== 'superadmin' && $targetCompanyId === null) {
                $validator->errors()->add('company_id', 'Debes asignar una empresa para usuarios tenant.');
            }
        });
    }
}
