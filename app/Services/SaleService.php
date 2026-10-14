<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registro de ventas (RN-08 a RN-13).
 *
 * - Nunca recibe precios, totales ni vendedor desde el formulario: el precio sale de la BD,
 *   los importes se calculan aquí y el vendedor es el usuario autenticado (RN-10).
 * - Los importes se calculan en céntimos enteros para evitar errores de redondeo.
 * - create() hace todo en una sola transacción con lockForUpdate (RN-13);
 *   quote() hace el mismo cálculo sin bloquear ni guardar (pantalla "Revisar").
 */
class SaleService
{
    public const PAYMENT_METHODS = ['EFECTIVO', 'TARJETA'];

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     */
    public function create(User $seller, int $patientId, array $lines, string $paymentMethod): Sale
    {
        return DB::transaction(function () use ($seller, $patientId, $lines, $paymentMethod) {
            if (! in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'El método de pago debe ser EFECTIVO o TARJETA.',
                ]);
            }

            // Mismas validaciones y cálculos que quote(), pero bloqueando las filas.
            $calculation = $this->calculate($patientId, $lines, lock: true);

            $sale = Sale::create([
                'patient_id' => $calculation['patient']->id,
                'user_id' => $seller->id,
                'payment_method' => $paymentMethod,
                'total' => $this->fromCents($calculation['total_cents']),
                'sold_at' => now(),
            ]);

            foreach ($calculation['items'] as $item) {
                // unit_price guarda el precio aplicado: si luego cambia el producto, la venta no cambia (RN-10).
                $sale->items()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $this->fromCents($item['unit_price_cents']),
                    'subtotal' => $this->fromCents($item['subtotal_cents']),
                ]);

                $item['product']->decrement('stock', $item['quantity']);
            }

            // Código generado a partir del id, dentro de la misma transacción: V-000001.
            $sale->code = 'V-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT);
            $sale->save();

            return $sale->load('items');
        });
    }

    /**
     * Calcula la venta sin guardar ni bloquear (para la pantalla "Revisar").
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @return array{patient: Patient, items: array<int, array{product: Product, quantity: int, unit_price: string, subtotal: string}>, total: string}
     */
    public function quote(int $patientId, array $lines): array
    {
        $calculation = $this->calculate($patientId, $lines, lock: false);

        return [
            'patient' => $calculation['patient'],
            'items' => array_map(fn (array $item) => [
                'product' => $item['product'],
                'quantity' => $item['quantity'],
                'unit_price' => $this->fromCents($item['unit_price_cents']),
                'subtotal' => $this->fromCents($item['subtotal_cents']),
            ], $calculation['items']),
            'total' => $this->fromCents($calculation['total_cents']),
        ];
    }

    /**
     * Valida paciente, productos y stock y calcula importes en céntimos.
     * Lanza ValidationException ante cualquier regla de negocio incumplida.
     *
     * @param  array<int, mixed>  $lines
     * @return array{patient: Patient, items: array<int, array{product: Product, quantity: int, unit_price_cents: int, subtotal_cents: int}>, total_cents: int}
     */
    private function calculate(int $patientId, array $lines, bool $lock): array
    {
        $quantities = $this->groupLines($lines);

        // RN-08: el paciente debe existir y estar ACTIVO.
        $patientQuery = Patient::whereKey($patientId);
        if ($lock) {
            $patientQuery->lockForUpdate();
        }
        $patient = $patientQuery->first();

        if ($patient === null) {
            throw ValidationException::withMessages(['patient_id' => 'El paciente seleccionado no existe.']);
        }
        if ($patient->status !== 'ACTIVO') {
            throw ValidationException::withMessages([
                'patient_id' => "El paciente {$patient->affiliation_code} está INACTIVO y no puede registrar compras.",
            ]);
        }

        // Productos ordenados por id: todas las ventas bloquean en el mismo orden (evita interbloqueos).
        $productQuery = Product::whereIn('id', array_keys($quantities))->orderBy('id');
        if ($lock) {
            $productQuery->lockForUpdate();
        }
        $products = $productQuery->get()->keyBy('id');

        if ($products->count() !== count($quantities)) {
            throw ValidationException::withMessages(['items' => 'Uno o más productos seleccionados no existen.']);
        }

        $errors = [];
        $items = [];
        $totalCents = 0;

        foreach ($products as $product) {
            $quantity = $quantities[$product->id];

            // RN-08: producto ACTIVO y stock suficiente para la cantidad SUMADA (RN-09).
            if ($product->status !== 'ACTIVO') {
                $errors[] = "El producto {$product->name} está INACTIVO y no se puede vender.";
                continue;
            }
            if ($quantity > $product->stock) {
                $errors[] = "Stock insuficiente para {$product->name}: disponible {$product->stock}, solicitado {$quantity}.";
                continue;
            }

            // RN-10: precio tomado de la BD; subtotal y total calculados aquí.
            $unitPriceCents = $this->toCents($product->price);
            $subtotalCents = $unitPriceCents * $quantity;
            $totalCents += $subtotalCents;

            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'subtotal_cents' => $subtotalCents,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['items' => $errors]);
        }

        return ['patient' => $patient, 'items' => $items, 'total_cents' => $totalCents];
    }

    /**
     * Valida las líneas y suma las cantidades de un mismo producto (RN-09).
     *
     * @param  array<int, mixed>  $lines
     * @return array<int, int> product_id => cantidad total
     */
    private function groupLines(array $lines): array
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'La venta debe tener al menos un producto.']);
        }

        $quantities = [];

        foreach ($lines as $line) {
            // FILTER_VALIDATE_INT rechaza decimales ("2.5") y texto; acepta enteros y "3".
            $productId = filter_var($line['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT);

            if ($productId === false || $productId < 1) {
                throw ValidationException::withMessages(['items' => 'Hay una línea con un producto no válido.']);
            }
            if ($quantity === false || $quantity < 1) {
                throw ValidationException::withMessages([
                    'items' => 'La cantidad de cada producto debe ser un número entero mayor que 0.',
                ]);
            }

            $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
        }

        return $quantities;
    }

    // "12.50" → 1250. El precio tiene como máximo 2 decimales (DECIMAL(10,2)).
    private function toCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    // 1250 → "12.50"
    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
