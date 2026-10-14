<?php

namespace Tests\Feature\Ventas;

// Caso 05: venta de ejemplo = S/ 57.50, stock final 7 y 4.
class VentaCorrectaTest extends VentaTestCase
{
    public function test_botella_x3_y_kit_x1_suman_57_50_y_descuentan_stock(): void
    {
        $vendedor = $this->vendedor();
        $paciente = $this->paciente();

        $venta = $this->servicio()->create($vendedor, $paciente->id, [
            $this->linea('PRD-001', 3),
            $this->linea('PRD-002', 1),
        ], 'EFECTIVO');

        $this->assertSame('57.50', $venta->total);
        $this->assertSame($vendedor->id, $venta->user_id);
        $this->assertSame($paciente->id, $venta->patient_id);
        $this->assertSame('EFECTIVO', $venta->payment_method);
        $this->assertSame('V-'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT), $venta->code);
        $this->assertNotNull($venta->sold_at);

        $botella = $venta->items->firstWhere('product_id', $this->producto('PRD-001')->id);
        $kit = $venta->items->firstWhere('product_id', $this->producto('PRD-002')->id);

        $this->assertSame(3, $botella->quantity);
        $this->assertSame('12.50', $botella->unit_price);
        $this->assertSame('37.50', $botella->subtotal);
        $this->assertSame(1, $kit->quantity);
        $this->assertSame('20.00', $kit->unit_price);
        $this->assertSame('20.00', $kit->subtotal);

        $this->assertSame(7, $this->producto('PRD-001')->stock);
        $this->assertSame(4, $this->producto('PRD-002')->stock);
    }

    // RN-09: líneas repetidas del mismo producto se suman en una sola.
    public function test_lineas_repetidas_se_agrupan_en_un_solo_detalle(): void
    {
        $venta = $this->servicio()->create($this->vendedor(), $this->paciente()->id, [
            $this->linea('PRD-001', 2),
            $this->linea('PRD-001', 1),
        ], 'TARJETA');

        $this->assertCount(1, $venta->items);
        $this->assertSame(3, $venta->items->first()->quantity);
        $this->assertSame('37.50', $venta->total);
        $this->assertSame(7, $this->producto('PRD-001')->stock);
    }

    // La pantalla "Revisar" calcula lo mismo sin guardar ni descontar stock.
    public function test_quote_calcula_el_total_sin_guardar(): void
    {
        $resumen = $this->servicio()->quote($this->paciente()->id, [
            $this->linea('PRD-001', 3),
            $this->linea('PRD-002', 1),
        ]);

        $this->assertSame('57.50', $resumen['total']);
        $this->assertSame('12.50', $resumen['items'][0]['unit_price']);
        $this->assertSame('37.50', $resumen['items'][0]['subtotal']);
        $this->assertNoSeGuardoNada();
        $this->assertSame(10, $this->producto('PRD-001')->stock);
        $this->assertSame(5, $this->producto('PRD-002')->stock);
    }
}
