<?php

use App\Models\User;
use App\Models\Company;
use App\Models\WarehouseCategory;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Unit;
use App\Models\PurchaseRequest;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseQuotationRequestDetail;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('solicitud de cotizacion se puede crear y relacionar correctamente', function () {
    // 1. Simular usuario autenticado
    $user = User::factory()->create();

    // 2. Crear datos base
    $company = Company::first() ?? Company::create(['name' => 'Empresa Matriz']);
    $branch = Branch::first() ?? Branch::create(['name' => 'Sucursal Central', 'company_id' => $company->id]);
    $warehouseCategory = WarehouseCategory::first() ?? WarehouseCategory::create(['name' => 'General', 'description' => 'General']);
    $warehouse = Warehouse::first() ?? Warehouse::create([
        'name' => 'Bodega Principal',
        'branch_id' => $branch->id,
        'id_branch' => $branch->id,
        'warehouse_category_id' => $warehouseCategory->id
    ]);
    $unit = Unit::first() ?? Unit::create(['name' => 'Unidad', 'abbreviation' => 'UND']);
    $product = Product::first() ?? Product::create([
        'name' => 'Laptop Core i7',
        'sku' => 'LAP-001',
        'id_unit' => $unit->id,
    ]);

    // 3. Crear solicitud de compra aprobada
    $purchaseRequest = PurchaseRequest::create([
        'uuid' => (string) Str::uuid(),
        'purchase_request_code' => 'REQ-2026-TEST',
        'id_branch' => $branch->id,
        'id_warehouse' => $warehouse->id,
        'id_user' => $user->id,
        'request_date' => now(),
        'required_date' => now()->addDays(7),
        'justification' => 'Renovación de equipos informáticos',
        'status' => 'approved',
    ]);

    $detail = $purchaseRequest->details()->create([
        'id_product' => $product->id,
        'quantity' => 5.0000,
        'id_unit' => $unit->id,
        'description' => 'Equipos para desarrollo',
    ]);

    // 4. Enviar petición para crear la solicitud de cotización
    $response = $this->actingAs($user)->post(route('purchase-quotation-requests.store'), [
        'id_purchase_request' => $purchaseRequest->id_purchase_request,
        'items' => [
            [
                'id_purchase_request_detail' => $detail->id_purchase_request_detail,
                'quantity' => 5.0000,
            ],
        ],
    ]);

    $response->assertRedirect(route('purchase-quotation-requests.index'));
    $response->assertSessionHas('success');

    // 5. Verificar que se creó la solicitud de cotización
    $this->assertDatabaseHas('purchase_quotation_requests', [
        'id_purchase_request' => $purchaseRequest->id_purchase_request,
        'id_purchase_quotation' => null,
    ]);

    // 6. Verificar que el detalle se guardó
    $this->assertDatabaseHas('purchase_quotation_request_details', [
        'id_purchase_request_detail' => $detail->id_purchase_request_detail,
        'id_purchase_quotation_detail' => null,
        'quantity' => 5.0000,
    ]);

    // 7. Probar endpoint AJAX de solicitudes aprobadas
    $ajaxResponse = $this->actingAs($user)->getJson(route('purchase-quotation-requests.approved-requests'));
    $ajaxResponse->assertOk();
    $ajaxResponse->assertJsonFragment([
        'purchase_request_code' => 'REQ-2026-TEST',
    ]);

    // 8. Probar vista detalle show
    $quotation = PurchaseQuotationRequest::where('id_purchase_request', $purchaseRequest->id_purchase_request)->first();
    $showResponse = $this->actingAs($user)->get(route('purchase-quotation-requests.show', $quotation->id_purchase_quotation_request));
    $showResponse->assertOk();
    $showResponse->assertSee('REQ-2026-TEST');
});
