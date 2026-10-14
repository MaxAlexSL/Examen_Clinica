<?php

namespace Tests\Feature\Ventas;

// RN-08: reglas de la venta; ninguna venta rechazada guarda datos.
class ReglasVentaTest extends VentaTestCase
{
    public function test_paciente_inactivo_se_rechaza(): void
    {
        $inactivo = $this->paciente('70000005');

        $errores = $this->ventaRechazada([$this->linea('PRD-001', 1)], $inactivo);

        $this->assertArrayHasKey('patient_id', $errores);
        $this->assertStringContainsString('INACTIVO', $errores['patient_id'][0]);
        $this->assertNoSeGuardoNada();
        $this->assertSame(10, $this->producto('PRD-001')->stock);
    }

    public function test_producto_inactivo_se_rechaza(): void
    {
        $errores = $this->ventaRechazada([$this->linea('PRD-005', 1)]);

        $this->assertSame(['El producto Alcohol en gel 500 ml está INACTIVO y no se puede vender.'], $errores['items']);
        $this->assertNoSeGuardoNada();
        $this->assertSame(12, $this->producto('PRD-005')->stock);
    }

    public function test_producto_sin_stock_se_rechaza(): void
    {
        $errores = $this->ventaRechazada([$this->linea('PRD-004', 1)]);

        $this->assertSame(['Stock insuficiente para Mascarillas (caja x50): disponible 0, solicitado 1.'], $errores['items']);
        $this->assertNoSeGuardoNada();
    }

    public function test_cantidad_cero_negativa_o_decimal_se_rechaza(): void
    {
        foreach ([0, -2, '1.5'] as $cantidad) {
            $errores = $this->ventaRechazada([$this->linea('PRD-001', $cantidad)]);

            $this->assertSame(['La cantidad de cada producto debe ser un número entero mayor que 0.'], $errores['items']);
        }

        $this->assertNoSeGuardoNada();
        $this->assertSame(10, $this->producto('PRD-001')->stock);
    }

    public function test_venta_sin_lineas_se_rechaza(): void
    {
        $errores = $this->ventaRechazada([]);

        $this->assertSame(['La venta debe tener al menos un producto.'], $errores['items']);
        $this->assertNoSeGuardoNada();
    }

    public function test_producto_inexistente_se_rechaza(): void
    {
        $errores = $this->ventaRechazada([['product_id' => 999999, 'quantity' => 1]]);

        $this->assertSame(['Uno o más productos seleccionados no existen.'], $errores['items']);
        $this->assertNoSeGuardoNada();
    }

    public function test_metodo_de_pago_invalido_se_rechaza(): void
    {
        $errores = $this->ventaRechazada([$this->linea('PRD-001', 1)], metodo: 'CHEQUE');

        $this->assertSame(['El método de pago debe ser EFECTIVO o TARJETA.'], $errores['payment_method']);
        $this->assertNoSeGuardoNada();
        $this->assertSame(10, $this->producto('PRD-001')->stock);
    }
}
