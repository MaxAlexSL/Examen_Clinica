<?php

namespace App\Services;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

/**
 * Alta y edición de productos.
 * Si dos peticiones pasan la validación al mismo tiempo, la restricción UNIQUE(code)
 * de la BD responde con el error 1062: se convierte en el mismo error de validación,
 * nunca en un error 500.
 */
class ProductService
{
    /**
     * @param  array<string, mixed>  $data  Datos ya validados (validated()).
     */
    public function create(array $data): Product
    {
        return $this->handleDuplicate(fn () => Product::create($data + ['status' => 'ACTIVO']));
    }

    /**
     * @param  array<string, mixed>  $data  Datos ya validados (sin stock ni status).
     */
    public function update(Product $product, array $data): Product
    {
        return $this->handleDuplicate(function () use ($product, $data) {
            $product->update($data);

            return $product;
        });
    }

    private function handleDuplicate(callable $callback): Product
    {
        try {
            return $callback();
        } catch (QueryException $e) {
            // 1062 = entrada duplicada en MySQL/MariaDB. Cualquier otro error se relanza.
            if (($e->errorInfo[1] ?? null) !== 1062) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'code' => StoreProductRequest::duplicateMessage(),
            ]);
        }
    }
}
