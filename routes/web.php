<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryLabelController;
use App\Http\Controllers\InventoryLotController;
use App\Http\Controllers\InventoryTransferController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseQuotationController;
use App\Http\Controllers\PurchaseQuotationRequestController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\RetaceoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseCategoryController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
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
    // Inventario: existencias por lote, bodega y ubicación, y su kardex
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    // Traslados entre bodegas: Casa Matriz reparte a las sucursales lo que entró con las compras
    Route::post('inventory-transfers/{transfer}/dispatch', [InventoryTransferController::class, 'dispatchTransfer'])->name('inventory-transfers.dispatch');
    Route::post('inventory-transfers/{transfer}/receive', [InventoryTransferController::class, 'receive'])->name('inventory-transfers.receive');
    Route::post('inventory-transfers/{transfer}/cancel', [InventoryTransferController::class, 'cancel'])->name('inventory-transfers.cancel');
    Route::resource('inventory-transfers', InventoryTransferController::class)->parameters(['inventory-transfers' => 'transfer']);
    // Lotes: nacen por ubicar al aplicar el retaceo; se reparten en ubicaciones y al confirmar
    // la colocación entran al inventario. Luego se cambian de ubicación con movimientos
    Route::get('inventory/lots', [InventoryLotController::class, 'index'])->name('inventory.lots.index');
    Route::get('inventory/lots/{lot}', [InventoryLotController::class, 'show'])->name('inventory.lots.show');
    Route::put('inventory/lots/{lot}', [InventoryLotController::class, 'update'])->name('inventory.lots.update');
    Route::post('inventory/lots/{lot}/split', [InventoryLotController::class, 'split'])->name('inventory.lots.split');
    Route::put('inventory/lots/{lot}/placements', [InventoryLotController::class, 'updatePlacements'])->name('inventory.lots.placements');
    Route::post('inventory/lots/{lot}/relocate', [InventoryLotController::class, 'relocate'])->name('inventory.lots.relocate');
    // Etiquetas de lote: la primera impresión y cada reimpresión (con motivo) quedan registradas
    Route::get('inventory/lots/{lot}/labels', [InventoryLabelController::class, 'create'])->name('inventory.labels');
    Route::post('inventory/lots/{lot}/labels', [InventoryLabelController::class, 'store'])->name('inventory.labels.store');
    Route::get('inventory/labels/{print}', [InventoryLabelController::class, 'show'])->name('inventory.labels.print');
    Route::resource('products', ProductController::class);
    Route::delete(
        '/product-images/{image}',
        [ProductController::class, 'destroyImage']
    )->name('product-images.destroy');
    Route::get(
        '/audit-logs',
        [AuditLogController::class, 'index']
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
        'destroy',
    ]);
    // Solicitudes de Compra
    Route::resource(
        'purchase-requests',
        PurchaseRequestController::class
    );
    // Flujo: la sucursal envía; compras aprueba, devuelve o rechaza (con motivo)
    Route::post(
        'purchase-requests/{purchaseRequest}/send',
        [PurchaseRequestController::class, 'send']
    )->name('purchase-requests.send');
    Route::post(
        'purchase-requests/{purchaseRequest}/approve',
        [PurchaseRequestController::class, 'approve']
    )->name('purchase-requests.approve');
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
        'purchase-quotation-requests/approved-requests',
        [PurchaseQuotationRequestController::class, 'getApprovedPurchaseRequests']
    )->name('purchase-quotation-requests.approved-requests');
    Route::get(
        'purchase-quotation-requests/request-details/{id}',
        [PurchaseQuotationRequestController::class, 'getPurchaseRequestDetails']
    )->name('purchase-quotation-requests.request-details');
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
    // Adjudicación por producto: todo a un proveedor o repartido entre varios
    Route::post(
        'purchase-quotation-requests/{purchaseQuotationRequest}/award',
        [PurchaseQuotationRequestController::class, 'award']
    )->name('purchase-quotation-requests.award');
    // Órdenes de compra de la adjudicación: una por proveedor y sucursal destino
    Route::post(
        'purchase-quotation-requests/{purchaseQuotationRequest}/generate-orders',
        [PurchaseQuotationRequestController::class, 'generateOrders']
    )->name('purchase-quotation-requests.generate-orders');
    // PDF para los proveedores con los productos elegidos
    Route::post(
        'purchase-quotation-requests/{purchaseQuotationRequest}/pdf',
        [PurchaseQuotationRequestController::class, 'generatePdf']
    )->name('purchase-quotation-requests.pdf');
    Route::resource(
        'purchase-quotation-requests',
        PurchaseQuotationRequestController::class
    )->only([
        'index',
        'store',
        'show',
        'update',
    ]);

    // Rutas para Ofertas de Proveedor (PurchaseQuotation)
    Route::resource('purchase-quotations', PurchaseQuotationController::class)
        ->only(['store', 'destroy']);
});

require __DIR__.'/auth.php';
