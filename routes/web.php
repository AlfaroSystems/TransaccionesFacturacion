<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseCategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;

Route::resource('branches', BranchController::class);

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Gestión de Empresas (CRUD) protegida por autenticación y permisos
    Route::resource('companies', CompanyController::class);

    // Gestión de Proveedores (CRUD) protegida por autenticación
    Route::resource('suppliers', SupplierController::class);

    // Gestión de Usuarios (CRUD) protegida por autenticación
    Route::resource('users', UserController::class);

    // Gestión de Roles y Permisos (CRUD) protegida por autenticación
    Route::resource('roles', RoleController::class);

    // Gestión de Empleados (CRUD) protegida por autenticación
    Route::resource('empleados', EmpleadoController::class);

    // Mapa de Bodega
    Route::get('locations/map', [LocationController::class, 'map'])->name('locations.map');

    // Gestión de Ubicaciones (CRUD) protegida por autenticación
    Route::resource('locations', LocationController::class);

    // Gestión de Productos (CRUD) protegida por autenticación
    Route::resource('products', ProductController::class);

    // Bitácora de Auditoría (protegida por autenticación y permiso)
    Route::get('/audit-logs', [App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('can:bitacora.ver')
        ->name('audit-logs.index');

    // Gestión de Almacenes (CRUD) protegida por autenticación
    Route::resource('warehouses', WarehouseController::class);

    // Gestión de Categorías de Almacenes (CRUD) protegida por autenticación
    Route::resource('warehouse_categories', WarehouseCategoryController::class);

    // Gestión de Categorías de Productos (CRUD) protegida por autenticación
    Route::resource('categories', CategoryController::class);
    Route::patch('/categories/{category}/toggle', [CategoryController::class, 'toggleStatus'])->name('categories.toggle');

    // Gestión de Subcategorías (CRUD) protegida por autenticación
    Route::resource('subcategories', SubCategoryController::class);

    // Gestión de Unidades de Medida (CRUD) protegida por autenticación
    Route::resource('units', UnitController::class);

    // API AJAX: Subcategorías por categoría (desarrollado por Dev 3)
    Route::get('/api/categories/{id}/sub-categories', function ($id) {
        $subCategories = \App\Models\SubCategory::where('id_category', $id)
            ->where('is_active', true)
            ->get(['id', 'name']);
        return response()->json($subCategories);
    })->name('api.categories.subcategories');
});

require __DIR__.'/auth.php';
