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
use App\Http\Controllers\PurchaseQuotationController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RetaceoController;

use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('login');
});
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
    // Los módulos crean y editan con modales en el listado; se excluyen las
    // acciones que no tienen método en el controlador
    Route::resource('branches', BranchController::class);
    Route::resource('companies', CompanyController::class)->except(['create', 'show']);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class)->except(['show']);
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
    Route::resource('warehouses', WarehouseController::class)->except(['show']);
    Route::resource(
        'warehouse_categories',
        WarehouseCategoryController::class
    )->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
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
    // Flujo: la sucursal envía; compras devuelve o rechaza (con motivo)
    Route::post(
        'purchase-requests/{purchaseRequest}/send',
        [PurchaseRequestController::class, 'send']
    )->name('purchase-requests.send');
    Route::post(
        'purchase-requests/{purchaseRequest}/return',
        [PurchaseRequestController::class, 'returnToBranch']
    )->name('purchase-requests.return');
    Route::post(
        'purchase-requests/{purchaseRequest}/reject',
        [PurchaseRequestController::class, 'reject']
    )->name('purchase-requests.reject');
    // Solicitudes de Cotización a Proveedores
    Route::get(
        'purchase-quotation-requests/sent-requests',
        [PurchaseQuotationRequestController::class, 'getSentPurchaseRequests']
    )->name('purchase-quotation-requests.sent-requests');
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

    // Facturas y Recepciones de Compra
    Route::get(
        'purchases/order-data/{id}',
        [PurchaseController::class, 'getOrderData']
    )->name('purchases.order-data');
    Route::get(
        'purchases/{purchase}/edit-data',
        [PurchaseController::class, 'getEditData']
    )->name('purchases.edit-data');
    Route::patch(
        'purchases/{purchase}/status',
        [PurchaseController::class, 'updateStatus']
    )->name('purchases.updateStatus');
    Route::get(
        'purchases/{purchase}/pdf',
        [PurchaseController::class, 'generatePdf']
    )->name('purchases.pdf');
    Route::resource('purchases', PurchaseController::class);

    // Retaceos / Prorrateo de Costos de Importación
    Route::get(
        'retaceos/purchase-data/{id}',
        [RetaceoController::class, 'getPurchaseData']
    )->name('retaceos.purchase-data');
    Route::get(
        'retaceos/{retaceo}/edit-data',
        [RetaceoController::class, 'getEditData']
    )->name('retaceos.edit-data');
    Route::patch(
        'retaceos/{retaceo}/status',
        [RetaceoController::class, 'updateStatus']
    )->name('retaceos.updateStatus');
    Route::get(
        'retaceos/{retaceo}/pdf',
        [RetaceoController::class, 'generatePdf']
    )->name('retaceos.pdf');
    Route::resource('retaceos', RetaceoController::class);
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

    // Rutas para Ofertas de Proveedor (PurchaseQuotation)
    Route::resource('purchase-quotations', PurchaseQuotationController::class)
        ->only(['store', 'destroy']);
});

require __DIR__.'/auth.php';