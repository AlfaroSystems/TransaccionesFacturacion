<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Unit;
use Illuminate\Http\Request;
use Exception;

class ProductController extends Controller
{
    /**
     * Mostrar listado de productos con paginación y filtros.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'subCategory', 'purchaseUnit', 'saleUnit']);

        // Filtro por búsqueda (nombre, sku, código de barras)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filtro por categoría
        if ($request->filled('id_category')) {
            $query->where('id_category', $request->id_category);
        }

        // Filtro por estado activo
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $products = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $categories = class_exists(Category::class) ? Category::where('is_active', true)->get() : collect();
        $subCategories = class_exists(SubCategory::class) ? SubCategory::where('is_active', true)->get() : collect();
        $units = class_exists(Unit::class) ? Unit::where('is_active', true)->get() : collect();

        return view('products.index', compact('products', 'categories', 'subCategories', 'units'));
    }

    /**
     * Mostrar formulario para registrar un nuevo producto.
     */
    public function create()
    {
        $categories = class_exists(Category::class) ? Category::all() : collect();
        $subCategories = class_exists(SubCategory::class) ? SubCategory::all() : collect();
        $units = class_exists(Unit::class) ? Unit::all() : collect();

        return view('products.create', compact('categories', 'subCategories', 'units'));
    }

    /**
     * Persistir un nuevo producto en la base de datos.
     */
    public function store(ProductRequest $request)
    {
        try {
            $data = $request->validated();
            
            // Si no se envía checkbox de is_active, por defecto es true
            $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

            $product = Product::create($data);

            return redirect()
                ->route('products.index')
                ->with('success', "Producto '{$product->name}' creado correctamente (SKU: {$product->sku}).");
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Ocurrió un error al registrar el producto: ' . $e->getMessage()]);
        }
    }

    /**
     * Mostrar los detalles de un producto específico.
     */
    public function show(Product $product)
    {
        $relations = ['category', 'subCategory', 'purchaseUnit', 'saleUnit'];

        if (\Illuminate\Support\Facades\Schema::hasTable('product_location')) {
            $relations[] = 'locations';
        }

        $product->load($relations);

        return view('products.show', compact('product'));
    }

    /**
     * Mostrar formulario para editar un producto.
     */
    public function edit(Product $product)
    {
        return redirect()->route('products.index', ['edit' => $product->id]);
    }

    /**
     * Actualizar los datos del producto en la base de datos.
     */
    public function update(ProductRequest $request, Product $product)
    {
        try {
            $data = $request->validated();
            $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;

            $product->update($data);

            return redirect()
                ->route('products.index')
                ->with('success', "Producto '{$product->name}' actualizado correctamente.");
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Ocurrió un error al actualizar el producto: ' . $e->getMessage()]);
        }
    }

    /**
     * Eliminar un producto de la base de datos.
     */
    public function destroy(Product $product)
    {
        try {
            $productName = $product->name;
            $product->delete();

            return redirect()
                ->route('products.index')
                ->with('success', "Producto '{$productName}' eliminado correctamente.");
        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'No se pudo eliminar el producto: ' . $e->getMessage()]);
        }
    }
}
