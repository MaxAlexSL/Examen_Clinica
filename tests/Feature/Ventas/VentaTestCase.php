<?php

namespace Tests\Feature\Ventas;

use App\Models\Patient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Base de las pruebas de ventas: datos del seeder y ayudas comunes.
 * Productos del seeder: PRD-001 Botella 12.50 (stock 10), PRD-002 Kit 20.00 (stock 5),
 * PRD-004 stock 0, PRD-005 INACTIVO. Paciente DNI 70000005 INACTIVO.
 */
abstract class VentaTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    protected function servicio(): SaleService
    {
        return app(SaleService::class);
    }

    protected function usuario(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    protected function vendedor(): User
    {
        return $this->usuario('vendedor1@example.com');
    }

    // Se busca por documento: los id no se reinician entre pruebas.
    protected function paciente(string $documento = '70000001'): Patient
    {
        return Patient::where('document_number', $documento)->firstOrFail();
    }

    protected function producto(string $codigo): Product
    {
        return Product::where('code', $codigo)->firstOrFail();
    }

    /**
     * @return array{product_id: int, quantity: mixed}
     */
    protected function linea(string $codigo, mixed $cantidad): array
    {
        return ['product_id' => $this->producto($codigo)->id, 'quantity' => $cantidad];
    }

    /**
     * Ejecuta una venta que debe ser rechazada y devuelve los mensajes de error.
     *
     * @param  array<int, mixed>  $lineas
     * @return array<string, array<int, string>>
     */
    protected function ventaRechazada(array $lineas, ?Patient $paciente = null, string $metodo = 'EFECTIVO'): array
    {
        try {
            $this->servicio()->create($this->vendedor(), ($paciente ?? $this->paciente())->id, $lineas, $metodo);
        } catch (ValidationException $e) {
            return $e->errors();
        }

        $this->fail('Se esperaba que la venta fuera rechazada.');
    }

    protected function assertNoSeGuardoNada(): void
    {
        $this->assertSame(0, Sale::count(), 'No debe existir ninguna venta.');
        $this->assertSame(0, SaleItem::count(), 'No debe existir ningún detalle de venta.');
    }
}
