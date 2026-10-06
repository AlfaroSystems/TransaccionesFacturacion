<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\WarehouseCategory;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\ExpenseType;
use App\Models\PurchaseRequest;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseQuotationRequestDetail;
use App\Models\PurchaseQuotation;
use Illuminate\Support\Str;

test('se puede registrar una oferta de proveedor completa con items y gastos adicionales', function () {
    // 1. Simular usuario autenticado
    $user = User::factory()->create();
    $user->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    // 2. Crear datos maestros
    $company = Company::first() ?? Company::create(['name' => 'Empresa Test']);
    $branch = Branch::first() ?? Branch::create(['name' => 'Sucursal Central', 'id_company' => $company->id_company]);
    $warehouseCategory = WarehouseCategory::first() ?? WarehouseCategory::create(['name' => 'General', 'description' => 'General']);
    $warehouse = Warehouse::first() ?? Warehouse::create([
        'name' => 'Bodega Central',
        'id_branch' => $branch->id_branch,
        'id_warehouse_category' => $warehouseCategory->id_warehouse_category
    ]);
    $unit = Unit::first() ?? Unit::create(['name' => 'Unidad', 'abbreviation' => 'UND']);
    $product = Product::first() ?? Product::create([
        'name' => 'Monitor 27 Pulgadas',
        'sku' => 'MON-27-TEST',
        'id_unit' => $unit->id_unit,
    ]);

    $supplier = Supplier::first() ?? Supplier::create([
        'name' => 'Proveedor Global S.A.',
        'email' => 'ventas@proveedorglobal.com',
        'country' => 'Nicaragua',
        'is_active' => true,
    ]);

    $expenseType = ExpenseType::first() ?? ExpenseType::create([
        'name' => 'Flete Terrestre',
        'description' => 'Costo de envío y transporte',
        'is_active' => true,
    ]);

    // 3. Crear solicitud de compra y solicitud de cotización
    $purchaseRequest = PurchaseRequest::create([
        'uuid' => (string) Str::uuid(),
        'purchase_request_code' => 'REQ-TEST-PROV',
        'id_branch' => $branch->id_branch,
        'id_warehouse' => $warehouse->id_warehouse,
        'id_user' => $user->id_user,
        'request_date' => now(),
        'required_date' => now()->addDays(5),
        'justification' => 'Compra de monitores',
        'status' => 'approved',
    ]);

    $detail = $purchaseRequest->details()->create([
        'id_product' => $product->id_product,
        'quantity' => 10.0000,
        'id_unit' => $unit->id_unit,
        'description' => 'Monitores para oficinas',
    ]);

    $quotationRequest = PurchaseQuotationRequest::create([
        'id_purchase_request' => $purchaseRequest->id_purchase_request,
    ]);

    PurchaseQuotationRequestDetail::create([
        'id_purchase_request_detail' => $detail->id_purchase_request_detail,
        'quantity' => 10.0000,
    ]);

    // 4. Enviar petición para guardar la oferta del proveedor
    // 10 unidades x $200 = $2000 subtotal
    // Descuento $100 -> Base $1900
    // IVA 15% -> $285
    // Gasto adicional de flete $50
    // Total esperado: ($2000 - $100) + $285 + $50 = $2235
    $postData = [
        'id_purchase_quotation_request' => $quotationRequest->id_purchase_quotation_request,
        'id_supplier' => $supplier->id_supplier,
        'quotation_date' => now()->format('Y-m-d'),
        'valid_until' => now()->addDays(30)->format('Y-m-d'),
        'currency' => 'USD',
        'payment_terms' => 'Crédito 30 días',
        'delivery_days' => 7,
        'notes' => 'Precios sujetos a disponibilidad',
        'items' => [
            [
                'id_product' => $product->id_product,
                'quantity' => 10.0000,
                'id_unit' => $unit->id_unit,
                'unit_price' => 200.0000,
                'discount' => 100.0000,
                'tax_rate' => 15.00,
                'delivery_days' => 7,
                'notes' => 'Empaque individual',
            ],
        ],
        'expenses' => [
            [
                'id_expense_type' => $expenseType->id_expense_type,
                'description' => 'Envío exprés',
                'amount' => 50.0000,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post(route('purchase-quotations.store'), $postData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // 5. Verificar que se creó la oferta en purchase_quotations
    $this->assertDatabaseHas('purchase_quotations', [
        'id_purchase_quotation_request' => $quotationRequest->id_purchase_quotation_request,
        'id_supplier' => $supplier->id_supplier,
        'subtotal' => 2000.0000,
        'discount' => 100.0000,
        'tax' => 285.0000,
        'total' => 2235.0000,
        'currency' => 'USD',
    ]);

    // 6. Verificar detalle de producto en purchase_quotation_details
    $this->assertDatabaseHas('purchase_quotation_details', [
        'id_product' => $product->id_product,
        'unit_price' => 200.0000,
        'discount' => 100.0000,
        'subtotal' => 2000.0000,
        'tax_amount' => 285.0000,
        'total' => 2185.0000,
    ]);

    // 7. Verificar gasto adicional en purchase_quotation_expenses
    $this->assertDatabaseHas('purchase_quotation_expenses', [
        'id_expense_type' => $expenseType->id_expense_type,
        'amount' => 50.0000,
        'description' => 'Envío exprés',
    ]);
});
