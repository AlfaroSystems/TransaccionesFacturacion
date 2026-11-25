<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\PriceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductPriceController extends Controller
{
    public function __construct(private readonly PriceService $service) {}

    /**
     * Listado de productos con su precio de venta vigente y su historial.
     */
    public function index(Request $request)
    {
        Gate::authorize('prices.ver');

        $search = $request->input('search');

        $products = Product::with(['category', 'currentPrice', 'prices.user'])
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
     * Asigna o cambia el precio de venta del producto.
     */
    public function change(Request $request, Product $product)
    {
        Gate::authorize('prices.cambiar');

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $this->service->cambiarPrecio($product, (float) $validated['price'], $validated['notes'] ?? null);

        return redirect()
            ->route('prices.index')
            ->with('success', 'Precio actualizado correctamente.');
    }
}
