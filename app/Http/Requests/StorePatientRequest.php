<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de pacientes (alta). UpdatePatientRequest hereda estas reglas:
 * la regla UNIQUE ignora al paciente de la ruta, que solo existe al editar.
 */
class StorePatientRequest extends FormRequest
{
    public const DOCUMENT_TYPES = ['DNI', 'CE', 'PASAPORTE'];

    // El permiso ya se valida en la ruta (permission:pacientes.crear / pacientes.editar).
    public function authorize(): bool
    {
        return true;
    }

    // Normaliza antes de validar: sin espacios y en mayúsculas (pasaportes con letras).
    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_type' => strtoupper(trim((string) $this->input('document_type'))),
            'document_number' => strtoupper(trim((string) $this->input('document_number'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(self::DOCUMENT_TYPES)],
            'document_number' => [
                'required',
                'string',
                $this->documentFormat(),
                // RN-04: no repetir tipo + número (al editar se ignora el propio paciente).
                Rule::unique('patients', 'document_number')
                    ->where('document_type', $this->input('document_type'))
                    ->ignore($this->route('patient')),
            ],
            'first_names' => ['required', 'string', 'max:100'],
            'last_names' => ['required', 'string', 'max:100'],
            // RN-05: la fecha de nacimiento no puede ser futura.
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'regex:/^\+?[0-9]{6,15}$/'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_type.in' => 'El tipo de documento debe ser DNI, CE o PASAPORTE.',
            'document_number.regex' => $this->isDni()
                ? 'El DNI debe tener exactamente 8 dígitos.'
                : 'El número de documento debe tener entre 6 y 12 caracteres alfanuméricos.',
            'document_number.unique' => self::duplicateMessage(),
            'birth_date.before_or_equal' => 'La fecha de nacimiento no puede ser una fecha futura.',
            'phone.regex' => 'El teléfono solo puede contener de 6 a 15 dígitos, opcionalmente con + al inicio.',
        ];
    }

    // Mismo mensaje para la validación y para el error 1062 de la BD (ver PatientService).
    public static function duplicateMessage(): string
    {
        return 'Ya existe un paciente registrado con ese tipo y número de documento.';
    }

    // DNI: 8 dígitos. CE y PASAPORTE: 6 a 12 caracteres alfanuméricos.
    private function documentFormat(): string
    {
        return $this->isDni() ? 'regex:/^[0-9]{8}$/' : 'regex:/^[A-Z0-9]{6,12}$/';
    }

    private function isDni(): bool
    {
        return $this->input('document_type') === 'DNI';
    }
}
