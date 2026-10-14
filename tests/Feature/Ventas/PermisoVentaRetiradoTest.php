<?php

namespace Tests\Feature\Ventas;

use Spatie\Permission\Models\Role;

// Caso 03: quitar ventas.crear bloquea en el servidor (RN-02).
class PermisoVentaRetiradoTest extends VentaTestCase
{
    public function test_sin_ventas_crear_el_vendedor_recibe_403_y_no_se_crea_venta(): void
    {
        // Antes: el vendedor puede abrir el formulario.
        $this->actingAs($this->vendedor())->get('/ventas/nueva')->assertOk();

        Role::findByName('Vendedor')->revokePermissionTo('ventas.crear');
        $vendedor = $this->vendedor(); // siguiente petición: usuario recargado

        $this->actingAs($vendedor)->get('/ventas/nueva')->assertForbidden();
        $this->actingAs($vendedor)->post('/ventas/revisar', $this->datosVentaEjemplo())->assertForbidden();
        $this->actingAs($vendedor)
            ->post('/ventas', $this->datosVentaEjemplo() + ['token' => 'cualquiera'])
            ->assertForbidden();

        $this->assertNoSeGuardoNada();
        $this->assertSame(10, $this->producto('PRD-001')->stock);

        // Conserva ventas.ver: aún puede ver el listado de sus ventas.
        $this->actingAs($vendedor)->get('/ventas')->assertOk();
    }
}
