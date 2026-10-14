<?php

namespace Tests\Feature\Ventas;

use App\Models\Sale;

// Caso 05 por HTTP: Nueva venta → Revisar → Confirmar.
class FlujoVentaHttpTest extends VentaTestCase
{
    public function test_vendedor_abre_el_formulario_de_nueva_venta(): void
    {
        $this->actingAs($this->vendedor())
            ->get('/ventas/nueva')
            ->assertOk()
            ->assertSee('Revisar venta')
            ->assertSee('Botella reutilizable')
            ->assertDontSee('Alcohol en gel'); // producto INACTIVO
    }

    public function test_revisar_muestra_el_total_y_no_guarda_nada(): void
    {
        $this->actingAs($this->vendedor())
            ->post('/ventas/revisar', $this->datosVentaEjemplo())
            ->assertOk()
            ->assertSee('S/ 12.50')
            ->assertSee('S/ 37.50')
            ->assertSee('S/ 57.50')
            ->assertSee('Confirmar venta');

        $this->assertNotNull(session('sale_token'));
        $this->assertNoSeGuardoNada();
        $this->assertSame(10, $this->producto('PRD-001')->stock);
    }

    public function test_confirmar_crea_la_venta_y_redirige_al_detalle(): void
    {
        $vendedor = $this->vendedor();
        $token = $this->revisar($vendedor, $this->datosVentaEjemplo());

        $respuesta = $this->actingAs($vendedor)
            ->post('/ventas', $this->datosVentaEjemplo() + ['token' => $token]);

        $venta = Sale::firstOrFail();
        $respuesta->assertRedirect(route('sales.show', $venta))
            ->assertSessionHas('status', "Venta {$venta->code} registrada.");

        $this->assertSame('57.50', $venta->total);
        $this->assertSame($vendedor->id, $venta->user_id);
        $this->assertSame(7, $this->producto('PRD-001')->stock);
        $this->assertSame(4, $this->producto('PRD-002')->stock);

        // El detalle muestra la venta y no ofrece editar ni eliminar (RN-11).
        $this->actingAs($vendedor)->get(route('sales.show', $venta))
            ->assertOk()
            ->assertSee($venta->code)
            ->assertSee('S/ 57.50')
            ->assertDontSee('Editar')
            ->assertDontSee('Eliminar');
    }

    public function test_revisar_con_stock_insuficiente_vuelve_al_formulario_con_errores(): void
    {
        $datos = $this->datosVentaEjemplo();
        $datos['lines'][0]['quantity'] = 11;

        $this->actingAs($this->vendedor())
            ->post('/ventas/revisar', $datos)
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors(['items' => 'Stock insuficiente para Botella reutilizable: disponible 10, solicitado 11.'])
            ->assertSessionHasInput('payment_method', 'EFECTIVO');

        $this->assertNoSeGuardoNada();
    }

    public function test_revisar_sin_lineas_es_rechazado_por_el_form_request(): void
    {
        $this->actingAs($this->vendedor())
            ->from('/ventas/nueva')
            ->post('/ventas/revisar', ['patient_id' => $this->paciente()->id, 'payment_method' => 'EFECTIVO', 'lines' => []])
            ->assertRedirect('/ventas/nueva')
            ->assertSessionHasErrors(['lines' => 'La venta debe tener al menos un producto.']);
    }

    public function test_listado_muestra_la_venta_y_el_total_filtrado(): void
    {
        $vendedor = $this->vendedor();
        $venta = $this->servicio()->create($vendedor, $this->paciente()->id, [$this->linea('PRD-001', 3), $this->linea('PRD-002', 1)], 'TARJETA');

        $this->actingAs($vendedor)->get('/ventas')
            ->assertOk()
            ->assertSee($venta->code)
            ->assertSee('Total del resultado (1 venta)')
            ->assertSee('S/ 57.50');

        // Filtro por paciente que no coincide: no hay ventas y el total es 0.
        $this->actingAs($vendedor)->get('/ventas?paciente=NoExiste')
            ->assertOk()
            ->assertDontSee($venta->code)
            ->assertSee('S/ 0.00');

        // Filtro por fecha de hoy y por documento del paciente: sí aparece.
        $hoy = now()->toDateString();
        $this->actingAs($vendedor)->get("/ventas?desde={$hoy}&hasta={$hoy}&paciente=70000001")
            ->assertOk()
            ->assertSee($venta->code);
    }

    public function test_la_ficha_del_paciente_muestra_su_historial_de_ventas(): void
    {
        $paciente = $this->paciente();
        $venta = $this->servicio()->create($this->vendedor(), $paciente->id, [$this->linea('PRD-001', 1)], 'EFECTIVO');

        // El vendedor tiene pacientes.ver y ventas.ver: ve su venta en la ficha.
        $this->actingAs($this->vendedor())->get(route('patients.show', $paciente))
            ->assertOk()
            ->assertSee('Historial de ventas')
            ->assertSee($venta->code);

        // Admisión no tiene permisos de ventas: no ve la sección.
        $this->actingAs($this->usuario('admision@example.com'))->get(route('patients.show', $paciente))
            ->assertOk()
            ->assertDontSee('Historial de ventas')
            ->assertDontSee($venta->code);
    }
}
