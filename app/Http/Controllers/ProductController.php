<?php

namespace App\Http\Controllers;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProductController extends Controller
{
    /**
     * Mostrar listado de productos con paginación y filtros.
     */
    public function index(Request $request)
    {
        Gate::authorize('products.ver');

        $query = Product::with(['category', 'subCategory', 'purchaseUnit', 'saleUnit', 'images']);

        // Filtro por búsqueda (nombre, sku, código original, código interno)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('original_code', 'like', "%{$search}%")
                    ->orWhere('internal_code', 'like', "%{$search}%");
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
        Gate::authorize('products.crear');

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
        Gate::authorize('products.crear');

        try {
            $data = $request->validated();
            
            // Si no se envía checkbox de is_active, por defecto es true
            $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

            // Extraer las imágenes del array de datos antes de crear el producto
            unset($data['images']);

            $product = Product::create($data);

            // Guardar imágenes si se proporcionaron
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('products', 'public');
                        $product->images()->create([
                            'path' => $path,
                            'is_active' => true,
                        ]);
                    }
                }
            }

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
        Gate::authorize('products.ver');

        $relations = ['category', 'subCategory', 'purchaseUnit', 'saleUnit', 'images'];

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
        Gate::authorize('products.editar');

        return redirect()->route('products.index', ['edit' => $product->id]);
    }

    /**
     * Actualizar los datos del producto en la base de datos.
     */
    public function update(ProductRequest $request, Product $product)
    {
        Gate::authorize('products.editar');

        try {
            $data = $request->validated();
            $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;

            unset($data['images']);

            $product->update($data);

            // Guardar nuevas imágenes si se subieron
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('products', 'public');
                        $product->images()->create([
                            'path' => $path,
                            'is_active' => true,
                        ]);
                    }
                }
            }

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
        Gate::authorize('products.eliminar');

        $productName = $product->name;
        $product->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', "Producto '{$productName}' inactivado correctamente.");
    }

    /**
     * Eliminar una imagen de un producto.
     */
    public function destroyImage(ProductImage $image)
    {
        Gate::authorize('products.editar');

        try {
            if (Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }

            $image->delete();

            return back()->with('success', 'Imagen eliminada correctamente.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar la imagen: ' . $e->getMessage()]);
        }
    }
}