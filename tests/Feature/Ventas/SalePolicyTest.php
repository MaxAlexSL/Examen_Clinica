<?php

namespace Tests\Feature\Ventas;

use App\Models\Sale;

// Base del caso 09: el vendedor no ve ventas ajenas (RN-12).
class SalePolicyTest extends VentaTestCase
{
    /**
     * @return array{0: Sale, 1: Sale} [venta de vendedor1, venta de vendedor2]
     */
    private function ventasDeAmbosVendedores(): array
    {
        $paciente = $this->paciente()->id;

        return [
            $this->servicio()->create($this->usuario('vendedor1@example.com'), $paciente, [$this->linea('PRD-001', 1)], 'EFECTIVO'),
            $this->servicio()->create($this->usuario('vendedor2@example.com'), $paciente, [$this->linea('PRD-002', 1)], 'TARJETA'),
        ];
    }

    public function test_vendedor_ve_su_venta_pero_no_la_de_otro_vendedor(): void
    {
        [$propia, $ajena] = $this->ventasDeAmbosVendedores();
        $vendedor1 = $this->usuario('vendedor1@example.com');

        $this->assertTrue($vendedor1->can('viewAny', Sale::class));
        $this->assertTrue($vendedor1->can('view', $propia));
        $this->assertFalse($vendedor1->can('view', $ajena));
    }

    public function test_administrador_ve_ambas_ventas(): void
    {
        [$venta1, $venta2] = $this->ventasDeAmbosVendedores();
        $admin = $this->usuario('clinica@example.com');

        $this->assertTrue($admin->can('view', $venta1));
        $this->assertTrue($admin->can('view', $venta2));
    }

    public function test_crear_venta_depende_del_permiso_ventas_crear(): void
    {
        $this->assertTrue($this->usuario('vendedor1@example.com')->can('create', Sale::class));
        $this->assertFalse($this->usuario('admision@example.com')->can('create', Sale::class));
        $this->assertFalse($this->usuario('admision@example.com')->can('viewAny', Sale::class));
    }

    public function test_visible_to_filtra_los_listados_con_la_misma_regla(): void
    {
        [$venta1, $venta2] = $this->ventasDeAmbosVendedores();

        $idsVisibles = fn (string $email) => Sale::visibleTo($this->usuario($email))->orderBy('id')->pluck('id')->all();

        $this->assertSame([$venta1->id], $idsVisibles('vendedor1@example.com'));
        $this->assertSame([$venta2->id], $idsVisibles('vendedor2@example.com'));
        $this->assertSame([$venta1->id, $venta2->id], $idsVisibles('clinica@example.com'));
        $this->assertSame([], $idsVisibles('admision@example.com')); // sin ventas.ver ni ventas.ver_todas
    }
}
