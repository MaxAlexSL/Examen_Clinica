<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Permisos, roles, usuarios y productos de ejemplo (dentro de la transacción de cada prueba).
        $this->seed();
    }

    private function userByEmail(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function admin(): User
    {
        return $this->userByEmail('clinica@example.com');
    }

    private function product(string $code): Product
    {
        return Product::where('code', $code)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'code' => 'PRD-100',
            'name' => 'Guantes de látex',
            'description' => 'Caja x 100 unidades',
            'price' => '18.50',
            'stock' => '20',
        ], $overrides);
    }

    public function test_admin_creates_product(): void
    {
        $this->actingAs($this->admin())
            ->post('/productos', $this->validData(['code' => 'prd-100'])) // se normaliza a mayúsculas
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product = $this->product('PRD-100');
        $this->assertSame('18.50', $product->price);
        $this->assertSame(20, $product->stock);
        $this->assertSame('ACTIVO', $product->status);
    }

    public function test_zero_or_negative_price_is_rejected(): void
    {
        $count = Product::count();

        foreach (['0', '-5'] as $price) {
            $this->actingAs($this->admin())
                ->post('/productos', $this->validData(['price' => $price]))
                ->assertSessionHasErrors(['price' => 'El precio debe ser mayor que 0.']);
        }

        $this->assertSame($count, Product::count());
    }

    public function test_price_with_more_than_two_decimals_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/productos', $this->validData(['price' => '10.999']))
            ->assertSessionHasErrors(['price' => 'El precio debe tener como máximo 2 decimales.']);
    }

    public function test_negative_or_decimal_initial_stock_is_rejected(): void
    {
        $count = Product::count();

        $this->actingAs($this->admin())
            ->post('/productos', $this->validData(['stock' => '-1']))
            ->assertSessionHasErrors(['stock' => 'El stock no puede ser negativo.']);

        $this->actingAs($this->admin())
            ->post('/productos', $this->validData(['stock' => '2.5']))
            ->assertSessionHasErrors(['stock' => 'El stock debe ser un número entero.']);

        $this->assertSame($count, Product::count());
    }

    // RN-07: tras el alta, el stock solo cambia por ventas.
    public function test_update_ignores_stock(): void
    {
        $product = $this->product('PRD-001'); // stock 10 (seeder)

        $this->actingAs($this->admin())
            ->put("/productos/{$product->id}", [
                'code' => 'PRD-001', // su propio código no cuenta como duplicado
                'name' => 'Botella reutilizable',
                'price' => '13.00',
                'stock' => '999',
            ])
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('Botella reutilizable', $product->name);
        $this->assertSame('13.00', $product->price);
        $this->assertSame(10, $product->stock);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $count = Product::count();

        $this->actingAs($this->admin())
            ->post('/productos', $this->validData(['code' => 'PRD-001'])) // ya existe (seeder)
            ->assertSessionHasErrors(['code' => 'El código ya existe.']);

        $this->assertSame($count, Product::count());
    }

    // Si la BD rechaza el código duplicado (error 1062), se responde con el mismo mensaje.
    public function test_database_duplicate_error_becomes_validation_error(): void
    {
        $count = Product::count();

        try {
            // Se llama al servicio sin pasar por el Form Request, como en una carrera entre dos peticiones.
            app(ProductService::class)->create($this->validData(['code' => 'PRD-001']));
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['El código ya existe.'], $e->errors()['code']);
        }

        $this->assertSame($count, Product::count());
    }

    public function test_seller_can_list_but_not_create(): void
    {
        $seller = $this->userByEmail('vendedor1@example.com');
        $count = Product::count();

        $this->actingAs($seller)->get('/productos')->assertOk()->assertSee('PRD-001');
        $this->actingAs($seller)->post('/productos', $this->validData())->assertForbidden();

        $this->assertSame($count, Product::count());
    }

    public function test_admission_can_not_list_products(): void
    {
        $this->actingAs($this->userByEmail('admision@example.com'))
            ->get('/productos')
            ->assertForbidden();
    }

    public function test_seller_can_view_product_but_admission_can_not(): void
    {
        $product = $this->product('PRD-004'); // stock 0 (seeder)

        $this->actingAs($this->userByEmail('vendedor1@example.com'))
            ->get("/productos/{$product->id}")
            ->assertOk()
            ->assertSee('PRD-004')
            ->assertSee('S/ 15.00')
            ->assertSee('Sin stock')
            ->assertDontSee('Desactivar'); // el vendedor no tiene productos.desactivar

        $this->actingAs($this->userByEmail('admision@example.com'))
            ->get("/productos/{$product->id}")
            ->assertForbidden();
    }

    public function test_list_shows_price_format_and_out_of_stock_badge(): void
    {
        $this->actingAs($this->admin())
            ->get('/productos')
            ->assertOk()
            ->assertSee('S/ 12.50')   // PRD-001
            ->assertSee('Sin stock'); // PRD-004 tiene stock 0
    }

    public function test_deactivate_only_changes_status(): void
    {
        $product = $this->product('PRD-001');

        $this->actingAs($this->admin())
            ->patch("/productos/{$product->id}/desactivar")
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'code' => 'PRD-001', 'status' => 'INACTIVO']);
    }

    public function test_admin_can_open_create_and_edit_pages(): void
    {
        $product = $this->product('PRD-001');

        $this->actingAs($this->admin())->get('/productos/crear')->assertOk();
        $this->actingAs($this->admin())->get("/productos/{$product->id}/editar")
            ->assertOk()
            ->assertSee('Stock actual')
            ->assertDontSee('name="stock"', false);
    }
}
