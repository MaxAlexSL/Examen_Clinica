<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['code' => 'PRD-001', 'name' => 'Botella reutilizable', 'description' => 'Botella de 750 ml libre de BPA.', 'price' => 12.50, 'stock' => 10, 'status' => 'ACTIVO'],
            ['code' => 'PRD-002', 'name' => 'Kit de aseo', 'description' => 'Cepillo, pasta dental y jabón.', 'price' => 20.00, 'stock' => 5, 'status' => 'ACTIVO'],
            ['code' => 'PRD-003', 'name' => 'Termómetro digital', 'description' => 'Termómetro de lectura rápida.', 'price' => 35.00, 'stock' => 8, 'status' => 'ACTIVO'],
            ['code' => 'PRD-004', 'name' => 'Mascarillas (caja x50)', 'description' => 'Mascarillas descartables de tres capas.', 'price' => 15.00, 'stock' => 0, 'status' => 'ACTIVO'],
            ['code' => 'PRD-005', 'name' => 'Alcohol en gel 500 ml', 'description' => 'Producto descontinuado.', 'price' => 9.90, 'stock' => 12, 'status' => 'INACTIVO'],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
