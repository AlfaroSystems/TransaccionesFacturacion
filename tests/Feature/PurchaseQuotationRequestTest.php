<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseQuotationRequestDetail;
use Illuminate\Support\Str;

test('invitacion a cotizar se puede crear y relacionar correctamente', function () {
    // 1. Simular usuario autenticado
    $user = User::factory()->create();

    // 2. Crear datos base
    $branch = Branch::first() ?? Branch::create(['name' => 'Sucursal Central']);
    $warehouse = Warehouse::first() ?? Warehouse::create(['name' => 'Bodega Principal', 'id_branch' => $branch->id]);
    $unit = Unit::first() ?? Unit::create(['name' => 'Unidad', 'abbreviation' => 'UND']);
    $product = Product::first() ?? Product::create([
        'name' => 'Laptop Core i7',
        'sku' => 'LAP-001',
        'id_unit' => $unit->id,
    ]);

    $supplier1 = Supplier::create([
        'name' => 'Proveedor Tech S.A.',
        'email' => 'tech@proveedor.com',
        'country' => 'El Salvador',
    ]);
    $supplier2 = Supplier::create([
        'name' => 'Distribuidora Global S.A.',
        'email' => 'global@proveedor.com',
        'country' => 'Guatemala',
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

    // 4. Enviar petición para convocar a ambos proveedores
    $response = $this->actingAs($user)->post(route('purchase-quotation-requests.store'), [
        'id_purchase_request' => $purchaseRequest->id_purchase_request,
        'supplier_ids' => [$supplier1->id_supplier, $supplier2->id_supplier],
        'notes' => 'Cotizar con entrega a más tardar el próximo viernes.',
        'items' => [
            [
                'id_purchase_request_detail' => $detail->id_purchase_request_detail,
                'quantity' => 5.0000,
                'notes' => 'Garantía mínima de 2 años',
            ],
        ],
    ]);

    $response->assertRedirect(route('purchase-quotation-requests.index'));
    $response->assertSessionHas('success');

    // 5. Verificar que se crearon dos invitaciones (una por cada proveedor convocado)
    $this->assertDatabaseHas('purchase_quotation_requests', [
        'id_purchase_request' => $purchaseRequest->id_purchase_request,
        'id_supplier' => $supplier1->id_supplier,
        'status' => 'sent',
    ]);

    $this->assertDatabaseHas('purchase_quotation_requests', [
        'id_purchase_request' => $purchaseRequest->id_purchase_request,
        'id_supplier' => $supplier2->id_supplier,
        'status' => 'sent',
    ]);

    // 6. Verificar que los ítems convocados se guardaron
    $quotation1 = PurchaseQuotationRequest::where('id_supplier', $supplier1->id_supplier)->first();
    expect($quotation1->details)->toHaveCount(1);
    expect((float)$quotation1->details->first()->quantity)->toBe(5.0);

    // 7. Probar endpoint AJAX de solicitudes aprobadas
    $ajaxResponse = $this->actingAs($user)->getJson(route('purchase-quotation-requests.approved-requests'));
    $ajaxResponse->assertOk();
    $ajaxResponse->assertJsonFragment([
        'purchase_request_code' => 'REQ-2026-TEST',
    ]);

    // 8. Probar vista detalle show
    $showResponse = $this->actingAs($user)->get(route('purchase-quotation-requests.show', $quotation1->id_purchase_quotation_request));
    $showResponse->assertOk();
    $showResponse->assertSee('Proveedor Tech S.A.');
    $showResponse->assertSee('REQ-2026-TEST');
});
