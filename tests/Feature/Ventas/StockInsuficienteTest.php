<?php

namespace Tests\Feature\Ventas;

// Caso 06: vender 11 con stock 10 (aun en líneas repetidas) rechaza todo.
class StockInsuficienteTest extends VentaTestCase
{
    public function test_vender_11_con_stock_10_en_una_linea_se_rechaza(): void
    {
        $errores = $this->ventaRechazada([$this->linea('PRD-001', 11)]);

        $this->assertSame(
            ['Stock insuficiente para Botella reutilizable: disponible 10, solicitado 11.'],
            $errores['items'],
        );
        $this->assertNoSeGuardoNada();
        $this->assertSame(10, $this->producto('PRD-001')->stock);
    }

    public function test_vender_6_mas_5_del_mismo_producto_en_dos_lineas_se_rechaza(): void
    {
        $errores = $this->ventaRechazada([
            $this->linea('PRD-001', 6),
            $this->linea('PRD-002', 1), // línea válida: tampoco debe guardarse
            $this->linea('PRD-001', 5),
        ]);

        $this->assertSame(
            ['Stock insuficiente para Botella reutilizable: disponible 10, solicitado 11.'],
            $errores['items'],
        );
        $this->assertNoSeGuardoNada();
        $this->assertSame(10, $this->producto('PRD-001')->stock);
        $this->assertSame(5, $this->producto('PRD-002')->stock);
    }
}
