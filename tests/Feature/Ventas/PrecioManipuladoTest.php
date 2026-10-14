<?php

namespace Tests\Feature\Ventas;

use App\Models\Sale;

// Caso 07: precio, total o vendedor manipulados en el request se ignoran (RN-10).
class PrecioManipuladoTest extends VentaTestCase
{
    public function test_precio_total_y_vendedor_del_request_se_ignoran(): void
    {
        $vendedor1 = $this->vendedor();
        $vendedor2 = $this->usuario('vendedor2@example.com');
        $token = $this->revisar($vendedor1, $this->datosVentaEjemplo());

        $datos = $this->datosVentaEjemplo();
        // Campos inyectados a mano (por ejemplo, desde las herramientas del navegador).
        $datos['lines'][0] += ['price' => '0.01', 'unit_price' => '0.01', 'subtotal' => '0.01'];
        $datos['lines'][1] += ['price' => '0.01', 'unit_price' => '0.01', 'subtotal' => '0.01'];
        $datos += [
            'price' => '1',
            'unit_price' => '1',
            'total' => '1',
            'user_id' => $vendedor2->id,
            'sold_at' => '2000-01-01 00:00:00',
            'token' => $token,
        ];

        $this->actingAs($vendedor1)->post('/ventas', $datos)->assertSessionHasNoErrors();

        $venta = Sale::with('items')->firstOrFail();
        $this->assertSame('57.50', $venta->total);
        $this->assertSame($vendedor1->id, $venta->user_id);
        $this->assertNotSame('2000', $venta->sold_at->format('Y'));
        $this->assertEqualsCanonicalizing(['12.50', '20.00'], $venta->items->pluck('unit_price')->all());
        $this->assertEqualsCanonicalizing(['37.50', '20.00'], $venta->items->pluck('subtotal')->all());
    }
}
