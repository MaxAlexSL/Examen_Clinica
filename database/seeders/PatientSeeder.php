<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        // Datos ficticios.
        $patients = [
            ['document_type' => 'DNI', 'document_number' => '70000001', 'first_names' => 'Lucía', 'last_names' => 'Quispe Ramos', 'birth_date' => '1990-04-12', 'phone' => '987000001', 'email' => 'lucia.quispe@example.com', 'status' => 'ACTIVO'],
            ['document_type' => 'DNI', 'document_number' => '70000002', 'first_names' => 'Carlos Alberto', 'last_names' => 'Mendoza Flores', 'birth_date' => '1985-09-30', 'phone' => '987000002', 'email' => null, 'status' => 'ACTIVO'],
            ['document_type' => 'CE', 'document_number' => '001234567', 'first_names' => 'Valentina', 'last_names' => 'Rojas Pérez', 'birth_date' => '2001-01-05', 'phone' => null, 'email' => 'valentina.rojas@example.com', 'status' => 'ACTIVO'],
            ['document_type' => 'PASAPORTE', 'document_number' => 'AB123456', 'first_names' => 'Miguel', 'last_names' => 'Torres Huamán', 'birth_date' => '1978-07-22', 'phone' => '987000004', 'email' => null, 'status' => 'ACTIVO'],
            ['document_type' => 'DNI', 'document_number' => '70000005', 'first_names' => 'Rosa María', 'last_names' => 'Castillo Vega', 'birth_date' => '1965-11-15', 'phone' => '987000005', 'email' => null, 'status' => 'INACTIVO'],
        ];

        foreach ($patients as $data) {
            $patient = Patient::create($data + ['affiliation_date' => now()->toDateString()]);

            // Código generado a partir del id: AF-000001, AF-000002, ...
            $patient->affiliation_code = sprintf('AF-%06d', $patient->id);
            $patient->save();
        }
    }
}
