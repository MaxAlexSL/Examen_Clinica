<?php

namespace Tests\Feature\Ventas;

use App\Models\SaleItem;
use RuntimeException;

// Caso 10: un fallo a mitad de la venta revierte todo (RN-13).
class TransaccionRollbackTest extends VentaTestCase
{
    public function test_fallo_en_la_segunda_linea_revierte_venta_detalles_y_stock(): void
    {
        // Se simula un fallo al guardar el 2.º detalle (después de crear la venta y descontar el 1.er producto).
        $detallesCreados = 0;
        SaleItem::creating(function () use (&$detallesCreados) {
            if (++$detallesCreados === 2) {
                throw new RuntimeException('Fallo simulado en la segunda línea.');
            }
        });

        try {
            $this->servicio()->create($this->vendedor(), $this->paciente()->id, [
                $this->linea('PRD-001', 3),
                $this->linea('PRD-002', 1),
            ], 'EFECTIVO');
            $this->fail('Se esperaba el fallo simulado.');
        } catch (RuntimeException $e) {
            $this->assertSame('Fallo simulado en la segunda línea.', $e->getMessage());
        } finally {
            SaleItem::flushEventListeners();
        }

        $this->assertSame(2, $detallesCreados);
        $this->assertNoSeGuardoNada();
        $this->assertSame(10, $this->producto('PRD-001')->stock);
        $this->assertSame(5, $this->producto('PRD-002')->stock);
    }
}
