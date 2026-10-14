<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    // El permiso usuarios.editar se valida en la ruta (middleware permission).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            // Único, pero ignorando el correo del propio usuario que se edita.
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            // Opcional al editar: si se deja vacío se mantiene la contraseña actual.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }

    /**
     * Reglas adicionales: un usuario no puede desactivarse ni cambiar su propio rol.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->route('user');

                if (! $this->user()->is($user)) {
                    return;
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'No puedes desactivar tu propio usuario.');
                }

                if ($this->input('role') !== $user->getRoleNames()->first()) {
                    $validator->errors()->add('role', 'No puedes cambiar tu propio rol.');
                }
            },
        ];
    }
}
