<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de productos (alta). UpdateProductRequest hereda estas reglas
 * pero quita "stock": tras el alta, el stock solo cambia por ventas (RN-07).
 */
class StoreProductRequest extends FormRequest
{
    // El permiso ya se valida en la ruta (permission:productos.crear / productos.editar).
    public function authorize(): bool
    {
        return true;
    }

    // El código se guarda sin espacios y en mayúsculas.
    // El stock "010" se normaliza a "10": la regla integer rechaza ceros a la izquierda.
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
        ]);

        $stock = trim((string) $this->input('stock'));
        if (preg_match('/^[0-9]+$/', $stock)) {
            $this->merge(['stock' => ltrim($stock, '0') ?: '0']);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                // Letras, números y guiones (ej. PRD-001); sin guiones al inicio, al final ni dobles.
                'regex:/^[A-Z0-9]+(-[A-Z0-9]+)*$/',
                // Al editar se ignora el propio producto.
                Rule::unique('products', 'code')->ignore($this->route('product')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            // RN-07: precio > 0 con máximo 2 decimales (DECIMAL(10,2)).
            'price' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
            // RN-07: stock entero >= 0 (UNSIGNED INT).
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo puede contener letras, números y guiones (ej. PRD-001).',
            'code.unique' => self::duplicateMessage(),
            'price.gt' => 'El precio debe ser mayor que 0.',
            'price.decimal' => 'El precio debe tener como máximo 2 decimales.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
        ];
    }

    // Mismo mensaje para la validación y para el error 1062 de la BD (ver ProductService).
    public static function duplicateMessage(): string
    {
        return 'El código ya existe.';
    }
}
