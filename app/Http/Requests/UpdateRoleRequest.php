<?php

namespace App\Http\Requests;

use App\Services\AdminGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRoleRequest extends FormRequest
{
    // El permiso roles.editar se valida en la ruta (middleware permission).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($this->route('role'))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del rol',
            'permissions.*' => 'permiso',
        ];
    }

    /**
     * Evita que el usuario se quite a sí mismo el permiso de editar roles
     * (se quedaría sin forma de revertir el cambio) y que el sistema
     * se quede sin ningún administrador activo (AdminGuard).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $role = $this->route('role');
                $permissions = (array) $this->input('permissions', []);

                if ($this->user()->hasRole($role) && ! in_array('roles.editar', $permissions, true)) {
                    $validator->errors()->add('permissions', 'No puedes quitar el permiso "roles.editar" de tu propio rol.');

                    return;
                }

                if (! app(AdminGuard::class)->allowsRolePermissions($role, $permissions)) {
                    $validator->errors()->add('permissions', AdminGuard::MESSAGE);
                }
            },
        ];
    }
}
