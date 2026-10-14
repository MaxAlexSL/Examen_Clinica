<?php

namespace App\Http\Requests;

use App\Services\SaleService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de forma de la venta (se usa en "Revisar" y en "Confirmar").
 * Solo se aceptan paciente, productos, cantidades, medio de pago y token:
 * precio, total y vendedor NUNCA se leen del request (RN-10), porque no tienen regla
 * y validated() solo devuelve campos con regla.
 * Las reglas de negocio (paciente ACTIVO, stock, etc.) las valida SaleService.
 */
class StoreSaleRequest extends FormRequest
{
    // El permiso ya se valida en la ruta (permission:ventas.crear).
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer'],
            'payment_method' => ['required', Rule::in(SaleService::PAYMENT_METHODS)],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            // Token de un solo uso generado en "Revisar" (solo lo usa store()).
            'token' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Selecciona un paciente.',
            'payment_method.required' => 'Selecciona el medio de pago.',
            'payment_method.in' => 'El medio de pago debe ser EFECTIVO o TARJETA.',
            'lines.required' => 'La venta debe tener al menos un producto.',
            'lines.min' => 'La venta debe tener al menos un producto.',
            'lines.*.product_id.required' => 'Selecciona el producto en cada línea.',
            'lines.*.product_id.integer' => 'Selecciona el producto en cada línea.',
            'lines.*.quantity.required' => 'Indica la cantidad en cada línea.',
            'lines.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'lines.*.quantity.min' => 'La cantidad debe ser mayor que 0.',
        ];
    }

    /**
     * Líneas limpias para SaleService: solo product_id y quantity.
     *
     * @return array<int, array{product_id: int, quantity: int}>
     */
    public function lines(): array
    {
        return array_map(fn (array $line) => [
            'product_id' => (int) $line['product_id'],
            'quantity' => (int) $line['quantity'],
        ], array_values($this->validated('lines')));
    }
}
