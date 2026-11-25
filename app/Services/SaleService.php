<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryLocation;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCostDetail;
use App\Models\SaleDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ventas con salida de inventario por PEPS (primeros en entrar, primeros en salir).
 *
 * La venta se registra y se entrega en una sola transacción: por cada línea se toma el precio
 * vigente del producto (snapshot) y, al entregar, se consume el inventario de la sucursal
 * empezando por el lote más antiguo. Cada consumo crea un movimiento de salida en el Kardex
 * (que descuenta la existencia por el hook de InventoryMovement) y una fila en sales_cost_details
 * con el costo del lote. El costo de venta (COGS) es la suma de lo consumido, y baja la existencia
 * y el valor global de la empresa con ese costo real (ver InventoryService::acumularPromedio).
 */
class SaleService
{
    public function __construct(private readonly InventoryService $inventory = new InventoryService) {}

    /**
     * Registra la venta y la entrega: descuenta el inventario de la sucursal con PEPS.
     *
     * @param  array{id_branch:int, id_customer:int, document_type:string, payment_method:string,
     *               fiscal_document_number?:?string, tax_rate?:float, notes?:?string,
     *               lines: array<int, array{id_product:int, quantity:numeric, discount?:numeric}>}  $data
     */
    public function vender(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $companyId = (int) Branch::queryAllBranches()->whereKey($data['id_branch'])->value('id_company');
            $taxRate = round((float) ($data['tax_rate'] ?? 13), 2);

            $sale = Sale::create([
                'id_branch' => $data['id_branch'],
                'id_customer' => $data['id_customer'],
                'id_user' => Auth::id(),
                'sale_date' => now(),
                'document_type' => $data['document_type'],
                'fiscal_document_number' => $data['fiscal_document_number'] ?? null,
                'payment_method' => $data['payment_method'],
                'currency' => 'USD',
                'status' => Sale::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0.0;
            $descuento = 0.0;
            $impuesto = 0.0;

            foreach ($data['lines'] as $i => $linea) {
                $product = Product::active()->find($linea['id_product']);

                if (! $product) {
                    throw ValidationException::withMessages(["lines.{$i}.id_product" => 'El producto no existe o está inactivo.']);
                }

                $precio = $product->precioVigente();

                if ($precio === null) {
                    throw ValidationException::withMessages(["lines.{$i}.id_product" => "El producto {$product->name} no tiene precio de venta asignado."]);
                }

                $cantidad = round((float) $linea['quantity'], 4);
                $desc = round((float) ($linea['discount'] ?? 0), 2);

                if ($cantidad <= 0) {
                    throw ValidationException::withMessages(["lines.{$i}.quantity" => 'La cantidad debe ser mayor que cero.']);
                }

                $lineSub = round($cantidad * $precio - $desc, 2);

                if ($lineSub < 0) {
                    throw ValidationException::withMessages(["lines.{$i}.discount" => 'El descuento no puede ser mayor que el importe de la línea.']);
                }

                $lineTax = round($lineSub * $taxRate / 100, 2);

                SaleDetail::create([
                    'id_sale' => $sale->id_sale,
                    'id_product' => $product->id_product,
                    'quantity' => $cantidad,
                    'id_unit' => $product->id_sale_unit,
                    'unit_price' => $precio,
                    'discount' => $desc,
                    'subtotal' => $lineSub,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => round($lineSub + $lineTax, 2),
                    'notes' => $linea['notes'] ?? null,
                ]);

                $subtotal += $lineSub;
                $descuento += $desc;
                $impuesto += $lineTax;
            }

            $sale->update([
                'subtotal' => round($subtotal, 2),
                'discount' => round($descuento, 2),
                'tax' => round($impuesto, 2),
                'total' => round($subtotal + $impuesto, 2),
            ]);

            $this->entregar($sale, $companyId);

            return $sale->fresh(['details.product']);
        });
    }

    /**
     * Entrega la venta: por cada línea consume el inventario de la sucursal con PEPS, registra la
     * salida en el Kardex y el desglose de costo, y baja la existencia y el valor global. Se llama
     * dentro de la transacción de la venta.
     */
    private function entregar(Sale $sale, int $companyId): void
    {
        $sale->load('details.product');
        $costoVenta = 0.0;

        foreach ($sale->details as $detail) {
            $product = $detail->product;
            $factor = (float) ($product->base_to_sale_factor ?: 1);
            $factor = $factor > 0 ? $factor : 1;
            $cantidadBase = round((float) $detail->quantity / $factor, 4);

            $filas = $this->filasPeps($product->id_product, (int) $sale->id_branch);
            $apartado = InventoryTransfer::apartado($filas->pluck('id_inventory_lot')->unique()->all());

            $disponible = $filas->sum(fn ($f) => max(0, (float) $f->quantity - (float) ($apartado[$f->id_inventory_lot.'|'.$f->id_inventory_location] ?? 0)));

            if ($disponible + 0.00001 < $cantidadBase) {
                throw ValidationException::withMessages([
                    'lines' => "Existencia insuficiente de {$product->name} en la sucursal: se necesitan "
                        .$this->formato($cantidadBase).' y hay '.$this->formato($disponible).' disponibles.',
                ]);
            }

            $pendiente = $cantidadBase;
            $costoLinea = 0.0;

            foreach ($filas as $fila) {
                $reservado = (float) ($apartado[$fila->id_inventory_lot.'|'.$fila->id_inventory_location] ?? 0);
                $disp = (float) $fila->quantity - $reservado;

                if ($disp <= 0.00001) {
                    continue;
                }

                $tomar = round(min($pendiente, $disp), 4);
                $costoUnit = (float) $fila->unit_cost;
                $costoTotal = round($tomar * $costoUnit, 4);

                $mov = InventoryMovement::create([
                    'id_product' => $product->id_product,
                    'id_inventory_lot' => $fila->id_inventory_lot,
                    'id_warehouse' => $fila->id_warehouse,
                    'id_inventory_location' => $fila->id_inventory_location,
                    'movement_type' => InventoryMovement::TYPE_SALE_ISSUE,
                    'movement_date' => now(),
                    'quantity_in' => 0,
                    'quantity_out' => $tomar,
                    'unit_cost' => $costoUnit,
                    'total_cost' => $costoTotal,
                    'reference_type' => InventoryMovement::REFERENCE_SALE,
                    'reference_id' => $sale->id_sale,
                    'id_user' => Auth::id(),
                ]);

                SaleCostDetail::create([
                    'id_sale_detail' => $detail->id_sale_detail,
                    'id_inventory_lot' => $fila->id_inventory_lot,
                    'id_inventory_location' => $fila->id_inventory_location,
                    'id_inventory_movement' => $mov->id_inventory_movement,
                    'quantity' => $tomar,
                    'unit_cost' => $costoUnit,
                    'total_cost' => $costoTotal,
                ]);

                $costoLinea += $costoTotal;
                $pendiente = round($pendiente - $tomar, 4);

                if ($pendiente <= 0.00001) {
                    break;
                }
            }

            $detail->update([
                'total_cost' => round($costoLinea, 4),
                'unit_cost' => (float) $detail->quantity > 0 ? round($costoLinea / (float) $detail->quantity, 4) : 0,
            ]);

            // Baja la existencia y el valor global de la empresa con el costo PEPS real
            $this->inventory->acumularPromedio($companyId, (int) $product->id_product, -$cantidadBase, -round($costoLinea, 4));

            $costoVenta += $costoLinea;
        }

        $sale->update([
            'total_cost' => round($costoVenta, 4),
            'status' => Sale::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Existencia disponible del producto en las bodegas de la sucursal, por lote y ubicación,
     * en orden PEPS (lote más antiguo primero). Consulta directa para no depender de los scopes.
     *
     * @return Collection<int, object{id_inventory_lot:int, id_inventory_location:int, id_warehouse:int, quantity:float, unit_cost:float}>
     */
    private function filasPeps(int $productId, int $branchId): Collection
    {
        return collect(DB::table('inventory_location_details as ild')
            ->join('inventory_lots as l', 'l.id_inventory_lot', '=', 'ild.id_inventory_lot')
            ->join('inventory_locations as il', 'il.id_inventory_location', '=', 'ild.id_inventory_location')
            ->join('locations as loc', 'loc.id_location', '=', 'il.id_location')
            ->join('warehouses as w', 'w.id_warehouse', '=', 'loc.id_warehouse')
            ->where('l.id_product', $productId)
            ->where('l.status', InventoryLot::STATUS_ACTIVE)
            ->where('il.location_type', InventoryLocation::TYPE_LOCATION)
            ->where('w.id_branch', $branchId)
            ->where('ild.quantity', '>', 0)
            ->orderBy('l.received_at')
            ->orderBy('l.id_inventory_lot')
            ->orderBy('ild.id_inventory_location')
            ->get([
                'ild.id_inventory_lot',
                'ild.id_inventory_location',
                'w.id_warehouse',
                'ild.quantity',
                'l.unit_cost',
            ]));
    }

    private function formato(float|int|string $cantidad): string
    {
        return rtrim(rtrim(number_format((float) $cantidad, 4, '.', ''), '0'), '.');
    }
}
