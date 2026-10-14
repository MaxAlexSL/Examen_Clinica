<?php

namespace App\Http\Requests;

/**
 * Mismas reglas que el alta. La regla UNIQUE de StorePatientRequest ignora
 * al paciente de la ruta ({patient}), así que puede conservar su documento.
 * affiliation_code y status no forman parte del formulario de edición.
 */
class UpdatePatientRequest extends StorePatientRequest
{
}
