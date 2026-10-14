<?php

namespace App\Http\Requests;

/**
 * Mismas reglas que el alta, sin "stock" (RN-07: tras el alta solo cambia por ventas).
 * Como "stock" no tiene regla, validated() no lo devuelve aunque llegue en el request.
 */
class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['stock']);

        return $rules;
    }
}
