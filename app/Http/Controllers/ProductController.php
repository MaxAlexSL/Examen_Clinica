<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catálogo de productos. Los permisos se validan en las rutas.
 * No hay eliminación física (RN-06): un producto se desactiva.
 */
class ProductController extends Controller
{
    public function __construct(private ProductService $products)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        // Un solo campo de búsqueda: código o nombre.
        $products = Product::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            // Los más recientes primero.
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('products.index', compact('products', 'search'));
    }

    public function show(Product $product): View
    {
        return view('products.show', compact('product'));
    }

    public function create(): View
    {
        return view('products.create', ['product' => new Product()]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        return redirect()->route('products.index')->with('status', "Producto {$product->code} registrado correctamente.");
    }

    public function edit(Product $product): View
    {
        return view('products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        // validated() no incluye stock: el stock solo cambia por ventas.
        $this->products->update($product, $request->validated());

        return redirect()->route('products.index')->with('status', 'Producto actualizado correctamente.');
    }

    // Solo cambia el estado; el registro nunca se elimina.
    public function deactivate(Product $product): RedirectResponse
    {
        $product->update(['status' => 'INACTIVO']);

        return back()->with('status', 'Producto desactivado.');
    }

    public function activate(Product $product): RedirectResponse
    {
        $product->update(['status' => 'ACTIVO']);

        return back()->with('status', 'Producto activado.');
    }
}
