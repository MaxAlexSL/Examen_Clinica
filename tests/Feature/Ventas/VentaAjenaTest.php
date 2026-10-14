<?php

namespace Tests\Feature\Ventas;

use App\Models\Sale;

// Caso 09: el vendedor no ve ventas ajenas, ni en el detalle ni en el listado (RN-12).
class VentaAjenaTest extends VentaTestCase
{
    /**
     * @return array{0: Sale, 1: Sale} [venta de vendedor1, venta de vendedor2]
     */
    private function ventas(): array
    {
        return [
            // vendedor1 → Lucía Quispe Ramos
            $this->servicio()->create($this->vendedor(), $this->paciente('70000001')->id, [$this->linea('PRD-001', 1)], 'EFECTIVO'),
            // vendedor2 → Valentina Rojas Pérez
            $this->servicio()->create($this->usuario('vendedor2@example.com'), $this->paciente('001234567')->id, [$this->linea('PRD-002', 1)], 'TARJETA'),
        ];
    }

    public function test_vendedor_no_puede_ver_el_detalle_de_una_venta_ajena(): void
    {
        [, $ajena] = $this->ventas();

        $this->actingAs($this->vendedor())
            ->get("/ventas/{$ajena->id}")
            ->assertForbidden()
            ->assertSee('No tienes permiso para esta acción')
            ->assertDontSee($ajena->code)
            ->assertDontSee('Valentina');
    }

    public function test_el_listado_del_vendedor_no_incluye_ventas_ajenas(): void
    {
        [$propia, $ajena] = $this->ventas();

        $this->actingAs($this->vendedor())
            ->get('/ventas')
            ->assertOk()
            ->assertSee($propia->code)
            ->assertDontSee($ajena->code)
            ->assertDontSee('Valentina')
            ->assertDontSee('<th class="px-6 py-3">Vendedor</th>', false); // sin ventas.ver_todas no hay columna vendedor
    }

    public function test_el_administrador_ve_ambas_ventas(): void
    {
        [$propia, $ajena] = $this->ventas();
        $admin = $this->usuario('clinica@example.com');

        $this->actingAs($admin)
            ->get('/ventas')
            ->assertOk()
            ->assertSee($propia->code)
            ->assertSee($ajena->code)
            ->assertSee('<th class="px-6 py-3">Vendedor</th>', false);

        $this->actingAs($admin)->get("/ventas/{$propia->id}")->assertOk();
        $this->actingAs($admin)->get("/ventas/{$ajena->id}")->assertOk()->assertSee('Valentina');
    }

    public function test_el_administrador_filtra_las_ventas_por_vendedor(): void
    {
        [$propia, $ajena] = $this->ventas();
        $vendedor2 = $this->usuario('vendedor2@example.com');

        $this->actingAs($this->usuario('clinica@example.com'))
            ->get("/ventas?vendedor={$vendedor2->id}")
            ->assertOk()
            ->assertSee($ajena->code)
            ->assertDontSee($propia->code)
            ->assertSee('Total del resultado (1 venta)');
    }

    // Sin ventas.ver_todas el filtro se ignora: no sirve para ver ventas ajenas.
    public function test_el_vendedor_no_puede_usar_el_filtro_para_ver_ventas_ajenas(): void
    {
        [$propia, $ajena] = $this->ventas();
        $vendedor2 = $this->usuario('vendedor2@example.com');

        $this->actingAs($this->vendedor())
            ->get("/ventas?vendedor={$vendedor2->id}")
            ->assertOk()
            ->assertSee($propia->code)
            ->assertDontSee($ajena->code)
            ->assertDontSee('id="vendedor"', false);
    }

    public function test_admision_no_accede_a_ventas(): void
    {
        [$propia] = $this->ventas();
        $admision = $this->usuario('admision@example.com');

        $this->actingAs($admision)->get('/ventas')->assertForbidden();
        $this->actingAs($admision)->get('/ventas/nueva')->assertForbidden();
        $this->actingAs($admision)->get("/ventas/{$propia->id}")->assertForbidden();
    }
}
