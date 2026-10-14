<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Ventas: Nueva venta → Revisar → Confirmar, listado y detalle.
 * Precios, totales y stock los calcula SaleService con datos de la BD (RN-10).
 * Una venta confirmada no se edita ni se elimina (RN-11): no hay rutas para eso.
 */
class SaleController extends Controller
{
    use AuthorizesRequests;

    // Clave en sesión del token de un solo uso que evita registrar la venta dos veces.
    private const TOKEN_KEY = 'sale_token';

    public function __construct(private SaleService $sales)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'paciente' => ['nullable', 'string', 'max:100'],
            'vendedor' => ['nullable', 'integer'],
        ]);
        $patientSearch = trim((string) ($filters['paciente'] ?? ''));

        // El filtro por vendedor solo tiene sentido para quien ve todas las ventas.
        $showSeller = $request->user()->can('ventas.ver_todas');
        $sellerId = $showSeller ? ($filters['vendedor'] ?? null) : null;

        // RN-12: ventas.ver_todas ve todas; ventas.ver solo las suyas.
        $query = Sale::visibleTo($request->user())
            ->when($sellerId, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['desde'] ?? null, fn ($q, $from) => $q->where('sold_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['hasta'] ?? null, fn ($q, $to) => $q->where('sold_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($patientSearch !== '', function ($q) use ($patientSearch) {
                $q->whereHas('patient', function ($q) use ($patientSearch) {
                    $q->where('document_number', 'like', "%{$patientSearch}%")
                        ->orWhere('first_names', 'like', "%{$patientSearch}%")
                        ->orWhere('last_names', 'like', "%{$patientSearch}%");
                });
            });

        // Total de todo el resultado filtrado (no solo de la página actual).
        $filteredTotal = (clone $query)->sum('total');

        $sales = $query->with(['patient', 'user'])
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'filteredTotal' => $filteredTotal,
            'filters' => [
                'desde' => $filters['desde'] ?? '',
                'hasta' => $filters['hasta'] ?? '',
                'paciente' => $patientSearch,
                'vendedor' => (string) ($sellerId ?? ''),
            ],
            'showSeller' => $showSeller,
            // Trabajadores que registraron al menos una venta (para el filtro por vendedor).
            'sellers' => $showSeller ? User::whereHas('sales')->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function create(): View
    {
        return view('sales.create', [
            'patients' => Patient::active()->orderBy('last_names')->orderBy('first_names')
                ->get(['id', 'affiliation_code', 'document_type', 'document_number', 'first_names', 'last_names']),
            // Los inactivos no se listan; los de stock 0 se muestran deshabilitados.
            // sold_quantity = unidades vendidas en total (para sugerir los más vendidos).
            'products' => Product::active()->select(['id', 'code', 'name', 'price', 'stock'])
                ->withSum('saleItems as sold_quantity', 'quantity')
                ->orderBy('name')->get(),
        ]);
    }

    // Paso "Revisar": calcula la venta con precios de la BD, sin guardar nada.
    public function review(StoreSaleRequest $request): View|RedirectResponse
    {
        $data = $request->validated();

        try {
            $quote = $this->sales->quote((int) $data['patient_id'], $request->lines());
        } catch (ValidationException $e) {
            return redirect()->route('sales.create')->withErrors($e->errors())->withInput();
        }

        // Token de un solo uso: solo esta revisión puede confirmarse, y una sola vez.
        $token = Str::random(40);
        $request->session()->put(self::TOKEN_KEY, $token);

        // Si el usuario pulsa "Modificar", el formulario vuelve con sus datos (old input).
        $request->flashOnly(['patient_id', 'payment_method', 'lines']);

        return view('sales.review', [
            'quote' => $quote,
            'paymentMethod' => $data['payment_method'],
            'token' => $token,
        ]);
    }

    // Paso "Confirmar": consume el token y registra la venta.
    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // pull() lee y borra el token: un segundo envío ya no lo encuentra.
        $expected = $request->session()->pull(self::TOKEN_KEY);
        if (! is_string($expected) || ! hash_equals($expected, (string) ($data['token'] ?? ''))) {
            return redirect()->route('sales.index')->with('error', 'La venta ya fue procesada o expiró.');
        }

        try {
            // Vendedor = usuario autenticado (nunca un user_id del request).
            $sale = $this->sales->create(
                $request->user(),
                (int) $data['patient_id'],
                $request->lines(),
                $data['payment_method'],
            );
        } catch (ValidationException $e) {
            // Ej.: otro vendedor vendió el último stock entre "Revisar" y "Confirmar".
            return redirect()->route('sales.create')->withErrors($e->errors())->withInput();
        }

        return redirect()->route('sales.show', $sale)->with('status', "Venta {$sale->code} registrada.");
    }

    public function show(Sale $sale): View
    {
        // RN-12: ventas.ver_todas, o ventas.ver y la venta es propia. Si no → 403.
        $this->authorize('view', $sale);

        $sale->load(['patient', 'user', 'items.product']);

        return view('sales.show', compact('sale'));
    }
}
