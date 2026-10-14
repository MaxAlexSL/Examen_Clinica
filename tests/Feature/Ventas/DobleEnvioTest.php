<?php

namespace Tests\Feature\Ventas;

use App\Models\Sale;

// Doble envío (doble clic o reenviar el formulario): el token de un solo uso evita duplicar la venta.
class DobleEnvioTest extends VentaTestCase
{
    public function test_reenviar_el_mismo_token_solo_crea_una_venta(): void
    {
        $vendedor = $this->vendedor();
        $datos = $this->datosVentaEjemplo() + ['token' => $this->revisar($vendedor, $this->datosVentaEjemplo())];

        $this->actingAs($vendedor)->post('/ventas', $datos)->assertRedirect(route('sales.show', Sale::firstOrFail()));

        $this->actingAs($vendedor)->post('/ventas', $datos)
            ->assertRedirect(route('sales.index'))
            ->assertSessionHas('error', 'La venta ya fue procesada o expiró.');

        $this->assertSame(1, Sale::count());
        $this->assertSame(7, $this->producto('PRD-001')->stock);
        $this->assertSame(4, $this->producto('PRD-002')->stock);
    }

    public function test_confirmar_sin_token_o_con_token_falso_no_crea_venta(): void
    {
        $vendedor = $this->vendedor();
        $this->revisar($vendedor, $this->datosVentaEjemplo());

        $this->actingAs($vendedor)->post('/ventas', $this->datosVentaEjemplo() + ['token' => 'inventado'])
            ->assertRedirect(route('sales.index'))
            ->assertSessionHas('error', 'La venta ya fue procesada o expiró.');

        $this->actingAs($vendedor)->post('/ventas', $this->datosVentaEjemplo())
            ->assertRedirect(route('sales.index'));

        $this->assertNoSeGuardoNada();
    }
}
