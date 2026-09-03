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
use App\Http\Controllers\PurchaseRequestController;

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

    Route::resource('branches', BranchController::class);

    Route::resource('companies', CompanyController::class);

    Route::resource('suppliers', SupplierController::class);

    Route::resource('users', UserController::class);

    Route::resource('roles', RoleController::class);

    Route::resource('empleados', EmpleadoController::class);

    Route::get('locations/map', [LocationController::class, 'map'])->name('locations.map');
    Route::post('locations/batch-store', [LocationController::class, 'batchStore'])->name('locations.batch-store');

    Route::resource('locations', LocationController::class);

    Route::resource('products', ProductController::class);

    Route::delete(
        '/product-images/{image}',
        [ProductController::class, 'destroyImage']
    )->name('product-images.destroy');

    Route::get(
        '/audit-logs',
        [App\Http\Controllers\AuditLogController::class, 'index']
    )
        ->middleware('can:bitacora.ver')
        ->name('audit-logs.index');

    Route::resource('warehouses', WarehouseController::class);

    Route::resource('warehouse_categories', WarehouseCategoryController::class);

    Route::resource('categories', CategoryController::class);

    Route::patch(
        '/categories/{category}/toggle',
        [CategoryController::class, 'toggleStatus']
    )->name('categories.toggle');

    Route::resource('subcategories', SubCategoryController::class);

    Route::resource('units', UnitController::class);

    // Solicitudes de Compra
    Route::resource(
        'purchase-requests',
        PurchaseRequestController::class
    );
    Route::patch(
        'purchase-requests/{purchaseRequest}/status',
        [PurchaseRequestController::class, 'updateStatus']
    )->name('purchase-requests.update-status');

    Route::get('/api/categories/{id}/sub-categories', function ($id) {
        $subCategories = \App\Models\SubCategory::where('id_category', $id)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return response()->json($subCategories);
    })->name('api.categories.subcategories');
});
require __DIR__.'/auth.php';