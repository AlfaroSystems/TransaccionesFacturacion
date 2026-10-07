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

test('solicitud de cotizacion se puede crear y relacionar correctamente', function () {
    // 1. Simular usuario autenticado
    $user = User::factory()->create();
    $user->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    // 2. Crear datos base
    $company = Company::first() ?? Company::create(['name' => 'Empresa Matriz']);
    $branch = Branch::first() ?? Branch::create(['name' => 'Sucursal Central', 'id_company' => $company->id_company]);
    $warehouseCategory = WarehouseCategory::first() ?? WarehouseCategory::create(['name' => 'General', 'description' => 'General']);
    $warehouse = Warehouse::first() ?? Warehouse::create([
        'name' => 'Bodega Principal',
        'id_branch' => $branch->id_branch,
        'id_warehouse_category' => $warehouseCategory->id_warehouse_category
    ]);
    $unit = Unit::first() ?? Unit::create(['name' => 'Unidad', 'abbreviation' => 'UND']);
    $product = Product::first() ?? Product::create([
        'name' => 'Laptop Core i7',
        'sku' => 'LAP-001',
        'id_unit' => $unit->id_unit,
    ]);

    // 3. Crear solicitud de compra enviada al departamento de compras
    $purchaseRequest = PurchaseRequest::create([
        'uuid' => (string) Str::uuid(),
        'purchase_request_code' => 'REQ-2026-TEST',
        'id_branch' => $branch->id_branch,
        'id_warehouse' => $warehouse->id_warehouse,
        'id_user' => $user->id_user,
        'request_date' => now(),
        'required_date' => now()->addDays(7),
        'justification' => 'Renovación de equipos informáticos',
        'status' => 'sent',
    ]);

    $detail = $purchaseRequest->details()->create([
        'id_product' => $product->id_product,
        'quantity' => 5.0000,
        'id_unit' => $unit->id_unit,
        'description' => 'Equipos para desarrollo',
    ]);

    // Mientras no se cotiza, aparece en la lista de solicitudes enviadas
    $this->actingAs($user)->getJson(route('purchase-quotation-requests.sent-requests'))
        ->assertOk()
        ->assertJsonFragment(['purchase_request_code' => 'REQ-2026-TEST']);

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

    // 7. La solicitud de compra queda en cotización y sale de la lista de enviadas
    expect($purchaseRequest->fresh()->status)->toBe('quoted');
    $this->actingAs($user)->getJson(route('purchase-quotation-requests.sent-requests'))
        ->assertOk()
        ->assertJsonMissing(['purchase_request_code' => 'REQ-2026-TEST']);

    // No se puede generar otra solicitud de cotización de la misma solicitud de compra
    $this->actingAs($user)->post(route('purchase-quotation-requests.store'), [
        'id_purchase_request' => $purchaseRequest->id_purchase_request,
        'items' => [['id_purchase_request_detail' => $detail->id_purchase_request_detail, 'quantity' => 5]],
    ])->assertSessionHasErrors('id_purchase_request');
    expect(PurchaseQuotationRequest::where('id_purchase_request', $purchaseRequest->id_purchase_request)->count())->toBe(1);

    // 8. Probar vista detalle show
    $quotation = PurchaseQuotationRequest::where('id_purchase_request', $purchaseRequest->id_purchase_request)->first();
    $showResponse = $this->actingAs($user)->get(route('purchase-quotation-requests.show', $quotation->id_purchase_quotation_request));
    $showResponse->assertOk();
    $showResponse->assertSee('REQ-2026-TEST');
});
