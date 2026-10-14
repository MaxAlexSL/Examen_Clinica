<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Permisos, roles, usuarios y pacientes de ejemplo (dentro de la transacción de cada prueba).
        $this->seed();
    }

    private function userByEmail(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function admission(): User
    {
        return $this->userByEmail('admision@example.com');
    }

    // Se busca por documento: los id (y por tanto los códigos AF-) no se reinician entre pruebas.
    private function seededPatient(): Patient
    {
        return Patient::where('document_type', 'DNI')->where('document_number', '70000001')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'document_type' => 'DNI',
            'document_number' => '71234567',
            'first_names' => 'Ana Sofía',
            'last_names' => 'Gutiérrez Salas',
            'birth_date' => '1995-03-20',
            'phone' => '987654321',
            'email' => 'ana.gutierrez@example.com',
        ], $overrides);
    }

    // Caso 04: documento duplicado (mismo tipo y número) rechazado.
    public function test_duplicate_document_is_rejected(): void
    {
        $count = Patient::count();

        $this->actingAs($this->admission())
            ->post('/pacientes', $this->validData(['document_number' => '70000001'])) // ya existe (seeder)
            ->assertSessionHasErrors([
                'document_number' => 'El documento ya existe.',
            ]);

        $this->assertSame($count, Patient::count());
    }

    public function test_same_number_with_different_type_is_allowed(): void
    {
        $this->actingAs($this->admission())
            ->post('/pacientes', $this->validData(['document_type' => 'CE', 'document_number' => '70000001']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Patient::where('document_number', '70000001')->count());
    }

    public function test_future_birth_date_is_rejected(): void
    {
        $count = Patient::count();

        $this->actingAs($this->admission())
            ->post('/pacientes', $this->validData(['birth_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['birth_date' => 'La fecha de nacimiento no puede ser una fecha futura.']);

        $this->assertSame($count, Patient::count());
    }

    public function test_dni_must_have_eight_digits(): void
    {
        $this->actingAs($this->admission())
            ->post('/pacientes', $this->validData(['document_number' => '1234567']))
            ->assertSessionHasErrors(['document_number' => 'El DNI debe tener exactamente 8 dígitos.']);
    }

    public function test_admission_creates_patient_with_generated_code(): void
    {
        $response = $this->actingAs($this->admission())->post('/pacientes', $this->validData());

        $patient = Patient::where('document_number', '71234567')->firstOrFail();

        $response->assertSessionHasNoErrors()->assertRedirect(route('patients.show', $patient));
        $this->assertMatchesRegularExpression('/^AF-\d{6}$/', $patient->affiliation_code);
        $this->assertSame(sprintf('AF-%06d', $patient->id), $patient->affiliation_code);
        $this->assertSame('ACTIVO', $patient->status);
        $this->assertSame(now()->toDateString(), $patient->affiliation_date->toDateString());
    }

    public function test_seller_can_list_but_not_create_or_deactivate(): void
    {
        $seller = $this->userByEmail('vendedor1@example.com');
        $patient = $this->seededPatient();
        $count = Patient::count();

        $this->actingAs($seller)->get('/pacientes')->assertOk()->assertSee($patient->affiliation_code);

        $this->actingAs($seller)->post('/pacientes', $this->validData())->assertForbidden();
        $this->actingAs($seller)->patch("/pacientes/{$patient->id}/desactivar")->assertForbidden();

        $this->assertSame($count, Patient::count());
        $this->assertSame('ACTIVO', $patient->fresh()->status);
    }

    // El último afiliado aparece primero, también al buscar.
    public function test_list_shows_latest_affiliated_first(): void
    {
        $this->actingAs($this->admission())->post('/pacientes', $this->validData(['last_names' => 'Quispe Nuevo']));
        $latest = Patient::orderByDesc('id')->firstOrFail();
        $older = Patient::where('document_number', '70000001')->firstOrFail(); // Lucía Quispe Ramos (seeder)

        $this->actingAs($this->admission())->get('/pacientes')
            ->assertSeeInOrder([$latest->affiliation_code, $older->affiliation_code]);

        $this->actingAs($this->admission())->get('/pacientes?q=Quispe')
            ->assertSeeInOrder([$latest->affiliation_code, $older->affiliation_code]);
    }

    public function test_admission_can_open_show_create_and_edit_pages(): void
    {
        $patient = $this->seededPatient();

        $this->actingAs($this->admission())->get("/pacientes/{$patient->id}")
            ->assertOk()
            ->assertSee($patient->affiliation_code)
            ->assertDontSee('Historial de ventas'); // Admisión no tiene permisos de ventas
        $this->actingAs($this->admission())->get('/pacientes/crear')->assertOk();
        $this->actingAs($this->admission())->get("/pacientes/{$patient->id}/editar")->assertOk();
    }

    public function test_deactivate_only_changes_status(): void
    {
        $patient = $this->seededPatient();

        $this->actingAs($this->admission())
            ->patch("/pacientes/{$patient->id}/desactivar")
            ->assertRedirect();

        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'status' => 'INACTIVO']);
        $this->assertSame($patient->affiliation_code, $patient->fresh()->affiliation_code);
    }

    public function test_update_keeps_own_document_and_affiliation_code(): void
    {
        $patient = $this->seededPatient();
        $originalCode = $patient->affiliation_code;

        $this->actingAs($this->admission())
            ->put("/pacientes/{$patient->id}", $this->validData([
                'document_number' => '70000001', // su propio documento no cuenta como duplicado
                'first_names' => 'Lucía Elena',
                'affiliation_code' => 'AF-999999', // se ignora: no está en validated()
            ]))
            ->assertSessionHasNoErrors();

        $patient->refresh();
        $this->assertSame('Lucía Elena', $patient->first_names);
        $this->assertSame($originalCode, $patient->affiliation_code);
    }

    // Si la BD rechaza el duplicado (error 1062), se responde con el mismo mensaje de validación.
    public function test_database_duplicate_error_becomes_validation_error(): void
    {
        $count = Patient::count();

        try {
            // Se llama al servicio sin pasar por el Form Request, como en una carrera entre dos peticiones.
            app(PatientService::class)->create($this->validData(['document_number' => '70000001']));
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['El documento ya existe.'],
                $e->errors()['document_number'],
            );
        }

        $this->assertSame($count, Patient::count());
    }
}
