<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
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
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\PurchaseQuotationRequestController;
use App\Http\Controllers\SupplierQuotationController;
use App\Http\Controllers\PurchaseQuotationController;
use App\Http\Controllers\PurchaseOrderController;

Route::get('/', function () {
    return redirect()->route('login');
});
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
    Route::resource('branches', BranchController::class);
    Route::resource('companies', CompanyController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::get(
        'locations/map',
        [LocationController::class, 'map']
    )->name('locations.map');
    Route::post(
        'locations/batch-store',
        [LocationController::class, 'batchStore']
    )->name('locations.batch-store');
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
    Route::resource(
        'warehouse_categories',
        WarehouseCategoryController::class
    );
    Route::resource('categories', CategoryController::class);
    Route::patch(
        '/categories/{category}/toggle',
        [CategoryController::class, 'toggleStatus']
    )->name('categories.toggle');
    Route::resource('subcategories', SubCategoryController::class);
    Route::resource('units', UnitController::class);
    // Gestión de Tipos de Gastos Adicionales
    Route::resource(
        'expense-types',
        ExpenseTypeController::class
    )->only([
        'index',
        'store',
        'update',
        'destroy'
    ]);
    // Solicitudes de Compra
    Route::resource(
        'purchase-requests',
        PurchaseRequestController::class
    );
    Route::patch(
        'purchase-requests/{purchaseRequest}/status',
        [PurchaseRequestController::class, 'updateStatus']
    )->name('purchase-requests.update-status');
    // Solicitudes de Cotización a Proveedores
    Route::get(
        'purchase-quotation-requests/approved-requests',
        [PurchaseQuotationRequestController::class, 'getApprovedPurchaseRequests']
    )->name('purchase-quotation-requests.approved-requests');
    Route::get(
        'purchase-quotation-requests/request-details/{id}',
        [PurchaseQuotationRequestController::class, 'getPurchaseRequestDetails']
    )->name('purchase-quotation-requests.request-details');
    Route::get(
        'purchase_orders/quotation-data/{id}',
        [PurchaseOrderController::class, 'getQuotationData']
    )->name('purchase_orders.quotation-data');
    Route::resource(
        'purchase_orders',
        PurchaseOrderController::class
    );
    Route::get(
        'purchase_orders/{purchase_order}/pdf',
        [PurchaseOrderController::class, 'generatePdf']
    )->name('purchase_orders.pdf');
    Route::patch(
        'purchase_orders/{purchase_order}/status',
        [PurchaseOrderController::class, 'updateStatus']
    )->name('purchase_orders.updateStatus');
    Route::patch(
        'purchase-quotation-requests/{purchaseQuotationRequest}/select-quotation/{purchaseQuotation}',
        [PurchaseQuotationRequestController::class, 'selectQuotation']
    )->name('purchase-quotation-requests.select-quotation');
    Route::resource(
        'purchase-quotation-requests',
        PurchaseQuotationRequestController::class
    )->only([
        'index',
        'store',
        'show'
    ]);
    Route::get(
        '/api/categories/{id}/sub-categories',
        [CategoryController::class, 'subCategories']
    )->name('api.categories.subcategories');

    // Rutas para Ofertas de Proveedor (PurchaseQuotation)
    Route::resource('purchase-quotations', PurchaseQuotationController::class)
        ->only(['store', 'destroy']);

    Route::post('/supplier-quotations', [SupplierQuotationController::class, 'store'])->name('supplier-quotations.store');

    // Sugerencia: Ruta para eliminar una oferta de proveedor por su ID
    Route::delete('/supplier-quotations/{supplierQuotation}', [SupplierQuotationController::class, 'destroy'])->name('supplier-quotations.destroy');
});

require __DIR__.'/auth.php';