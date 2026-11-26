<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Services\PriceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProductPriceController extends Controller
{
    public function __construct(private readonly PriceService $service) {}

    /**
     * Productos con sus presentaciones, el factor, el precio vigente y el historial.
     */
    public function index(Request $request)
    {
        Gate::authorize('prices.ver');

        $search = $request->input('search');

        $products = Product::with([
            'category',
            'presentations.currentPrice',
            'presentations.prices.user',
        ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', '%'.$search.'%')
                        ->orWhere('sku', 'ilike', '%'.$search.'%')
                        ->orWhere('internal_code', 'ilike', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('product_prices.index', compact('products'));
    }

    /**
     * Agrega una presentación de venta a un producto.
     */
    public function storePresentation(Request $request, Product $product)
    {
        Gate::authorize('prices.cambiar');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('product_presentations', 'name')->where('id_product', $product->id_product)],
            'factor' => ['required', 'numeric', 'gt:0'],
        ], [
            'name.unique' => 'El producto ya tiene una presentación con ese nombre.',
        ]);

        $product->presentations()->create([
            'name' => $validated['name'],
            'factor' => (float) $validated['factor'],
            'is_active' => true,
        ]);

        return redirect()->route('prices.index')->with('success', 'Presentación agregada.');
    }

    /**
     * Asigna o cambia el precio de venta de una presentación.
     */
    public function change(Request $request, ProductPresentation $presentation)
    {
        Gate::authorize('prices.cambiar');

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $this->service->cambiarPrecio($presentation, (float) $validated['price'], $validated['notes'] ?? null);

        return redirect()->route('prices.index')->with('success', 'Precio actualizado.');
    }
}
