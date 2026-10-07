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

    // 3. Crear solicitud de compra aprobada por el departamento de compras
    $purchaseRequest = PurchaseRequest::create([
        'uuid' => (string) Str::uuid(),
        'purchase_request_code' => 'REQ-2026-TEST',
        'id_branch' => $branch->id_branch,
        'id_warehouse' => $warehouse->id_warehouse,
        'id_user' => $user->id_user,
        'request_date' => now(),
        'required_date' => now()->addDays(7),
        'justification' => 'Renovación de equipos informáticos',
        'status' => 'approved',
    ]);

    $detail = $purchaseRequest->details()->create([
        'id_product' => $product->id_product,
        'quantity' => 5.0000,
        'id_unit' => $unit->id_unit,
        'description' => 'Equipos para desarrollo',
    ]);

    // Mientras no se cotiza, aparece en la lista de solicitudes aprobadas
    $this->actingAs($user)->getJson(route('purchase-quotation-requests.approved-requests'))
        ->assertOk()
        ->assertJsonFragment(['purchase_request_code' => 'REQ-2026-TEST']);

    // 4. Enviar petición para crear la solicitud de cotización
    $response = $this->actingAs($user)->post(route('purchase-quotation-requests.store'), [
        'purchase_requests' => [$purchaseRequest->id_purchase_request],
    ]);

    $response->assertRedirect(route('purchase-quotation-requests.index'));
    $response->assertSessionHas('success');

    // 5. Se creó la solicitud de cotización con la línea completa de la solicitud de compra
    $quotation = PurchaseQuotationRequest::sole();
    expect($quotation->id_purchase_quotation)->toBeNull();
    $this->assertDatabaseHas('purchase_quotation_request_details', [
        'id_purchase_quotation_request' => $quotation->id_purchase_quotation_request,
        'id_purchase_request_detail' => $detail->id_purchase_request_detail,
        'id_purchase_quotation_detail' => null,
        'quantity' => 5.0000,
    ]);

    // 7. La solicitud de compra queda en cotización y sale de la lista de aprobadas
    expect($purchaseRequest->fresh()->status)->toBe('quoted');
    $this->actingAs($user)->getJson(route('purchase-quotation-requests.approved-requests'))
        ->assertOk()
        ->assertJsonMissing(['purchase_request_code' => 'REQ-2026-TEST']);

    // No se puede generar otra solicitud de cotización de la misma solicitud de compra
    $this->actingAs($user)->post(route('purchase-quotation-requests.store'), [
        'purchase_requests' => [$purchaseRequest->id_purchase_request],
    ])->assertSessionHasErrors('purchase_requests.0');
    expect(PurchaseQuotationRequest::count())->toBe(1);

    // 8. Probar vista detalle show
    $showResponse = $this->actingAs($user)->get(route('purchase-quotation-requests.show', $quotation->id_purchase_quotation_request));
    $showResponse->assertOk();
    $showResponse->assertSee('REQ-2026-TEST');
});
