<?php

namespace Tests\Feature\Ventas;

use App\Models\Sale;

// Caso 08: un cambio de precio no altera las ventas pasadas.
class PrecioHistoricoTest extends VentaTestCase
{
    public function test_cambiar_el_precio_no_altera_la_venta_registrada(): void
    {
        $venta = $this->servicio()->create($this->vendedor(), $this->paciente()->id, [
            $this->linea('PRD-001', 2),
        ], 'EFECTIVO');

        $this->producto('PRD-001')->update(['price' => '99.90']);

        $venta = Sale::with('items')->findOrFail($venta->id);

        $this->assertSame('12.50', $venta->items->first()->unit_price);
        $this->assertSame('25.00', $venta->items->first()->subtotal);
        $this->assertSame('25.00', $venta->total);
    }
}
