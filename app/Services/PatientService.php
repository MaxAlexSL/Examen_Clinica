<?php

namespace App\Services;

use App\Http\Requests\StorePatientRequest;
use App\Models\Patient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alta y edición de pacientes.
 * Si dos peticiones pasan la validación al mismo tiempo, la restricción
 * UNIQUE(document_type, document_number) de la BD responde con el error 1062:
 * se convierte en el mismo error de validación, nunca en un error 500.
 */
class PatientService
{
    /**
     * @param  array<string, mixed>  $data  Datos ya validados (validated()).
     */
    public function create(array $data): Patient
    {
        return $this->handleDuplicate(fn () => DB::transaction(function () use ($data) {
            $patient = Patient::create($data + [
                'affiliation_date' => now()->toDateString(),
                'status' => 'ACTIVO',
            ]);

            // Código generado a partir del id, dentro de la misma transacción: AF-000001.
            $patient->affiliation_code = sprintf('AF-%06d', $patient->id);
            $patient->save();

            return $patient;
        }));
    }

    /**
     * @param  array<string, mixed>  $data  Datos ya validados (sin affiliation_code ni status).
     */
    public function update(Patient $patient, array $data): Patient
    {
        return $this->handleDuplicate(function () use ($patient, $data) {
            $patient->update($data);

            return $patient;
        });
    }

    private function handleDuplicate(callable $callback): Patient
    {
        try {
            return $callback();
        } catch (QueryException $e) {
            // 1062 = entrada duplicada en MySQL/MariaDB. Cualquier otro error se relanza.
            if (($e->errorInfo[1] ?? null) !== 1062) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'document_number' => StorePatientRequest::duplicateMessage(),
            ]);
        }
    }
}
