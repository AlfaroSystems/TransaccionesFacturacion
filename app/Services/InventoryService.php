<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryCostAverage;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Support\DocumentSequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Inventario por lote, bodega y ubicación, y costo promedio por empresa.
 *
 * Toda compra lleva retaceo, y el inventario sigue este orden:
 * 1. Al aplicarse el retaceo, cada línea recibida de la compra se vuelve un lote por ubicar,
 *    con su costo puesto en bodega. Todavía no es existencia.
 * 2. Se imprimen sus viñetas y se reparte en una o varias ubicaciones de su bodega; el reparto
 *    se puede guardar y corregir mientras el lote esté por ubicar.
 * 3. Al confirmar la colocación, cada ubicación del reparto es una entrada al inventario y el
 *    lote queda disponible. Desde ahí solo se mueve con movimientos (cambio de ubicación,
 *    traslados), que quedan en el kardex.
 *
 * Revertir el retaceo anula sus lotes, si ninguno se ha colocado. inventory_cost_averages lleva
 * la existencia, el valor y el costo promedio ponderado de cada producto por empresa.
 */
class InventoryService
{
    public function __construct(private readonly ProductCostService $costos = new ProductCostService) {}

    /**
     * Crea los lotes de la compra al aplicarse su retaceo: uno por línea recibida, por ubicar.
     * Se llama dentro de la transacción que aplica el retaceo; repetirlo no duplica nada.
     */
    public function crearLotes(Purchase $purchase): void
    {
        // En el orden de las líneas: los correlativos de los lotes lo siguen
        $purchase = Purchase::queryAllBranches()
            ->with(['details' => fn ($q) => $q->orderBy('id_purchase_detail')])
            ->findOrFail($purchase->getKey());

        $warehouseId = $purchase->id_warehouse
            ?? PurchaseOrder::queryAllBranches()->whereKey($purchase->id_purchase_order)->value('id_warehouse');

        if (! $warehouseId) {
            throw new InvalidArgumentException('La compra no tiene bodega de destino: elíjala para crear los lotes de su mercadería.');
        }

        $conLote = InventoryLot::queryAllBranches()
            ->whereIn('id_purchase_detail', $purchase->details->pluck('id_purchase_detail'))
            ->where('status', '!=', InventoryLot::STATUS_CANCELLED)
            ->pluck('id_purchase_detail')
            ->all();

        $costos = $this->costos->costosDeCompra($purchase);
        $fecha = $purchase->purchase_date ?? now();

        foreach ($purchase->details as $detail) {
            $cantidad = (float) $detail->quantity_received;
            if ($cantidad <= 0 || in_array($detail->id_purchase_detail, $conLote)) {
                continue;
            }

            $costo = (float) ($costos[$detail->id_purchase_detail] ?? 0);

            InventoryLot::create([
                'lot_code' => $this->codigoDeLote($fecha),
                'id_product' => $detail->id_product,
                'id_purchase_detail' => $detail->id_purchase_detail,
                'id_warehouse' => $warehouseId,
                'received_at' => $fecha,
                'quantity_received' => $cantidad,
                'unit_cost' => $costo,
                'total_cost' => round($cantidad * $costo, 4),
                'status' => InventoryLot::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Anula los lotes de la compra al revertir su retaceo. Solo si ninguno entró al inventario
     * (todos siguen por ubicar); sus viñetas impresas dejan de valer.
     */
    public function anularLotes(Purchase $purchase): void
    {
        $lots = InventoryLot::queryAllBranches()
            ->whereIn('id_purchase_detail', $purchase->details()->pluck('id_purchase_detail'))
            ->where('status', '!=', InventoryLot::STATUS_CANCELLED)
            ->orderBy('id_inventory_lot')
            ->lockForUpdate()
            ->get();

        if ($colocado = $lots->first(fn (InventoryLot $lot) => ! $lot->isPending())) {
            throw new InvalidArgumentException("No se puede revertir el retaceo: el lote {$colocado->lot_code} ya se colocó y entró al inventario.");
        }

        foreach ($lots as $lot) {
            // Uno por uno, para que cada borrado quede en la bitácora
            $lot->placements()->get()->each->delete();
            $lot->update(['status' => InventoryLot::STATUS_CANCELLED]);
        }
    }

    /**
     * Guarda el reparto del lote por ubicar. Puede quedar incompleto y terminarse después;
     * confirmarColocacion exige que sume todo el lote.
     *
     * @param  array<int, array{id_location: int|string, quantity: numeric}>  $lineas
     */
    public function guardarReparto(InventoryLot $lot, array $lineas): void
    {
        DB::transaction(function () use ($lot, $lineas) {
            if (! $lot->refreshWithLock()->isPending()) {
                throw new InvalidArgumentException('Solo se reparte un lote por ubicar.');
            }

            $this->validarReparto($lot, $lineas, completo: false);

            $lot->placements()->get()->each->delete();

            foreach ($lineas as $linea) {
                $lot->placements()->create([
                    'id_location' => (int) $linea['id_location'],
                    'quantity' => (float) $linea['quantity'],
                ]);
            }
        });
    }

    /**
     * Número de lote del proveedor y vencimiento del lote por ubicar.
     *
     * @param  array{supplier_lot_number?: ?string, expiration_date?: ?string}  $datos
     */
    public function actualizarDatos(InventoryLot $lot, array $datos): void
    {
        DB::transaction(function () use ($lot, $datos) {
            if (! $lot->refreshWithLock()->isPending()) {
                throw new InvalidArgumentException('Solo se editan los datos de un lote por ubicar.');
            }

            $lot->update([
                'supplier_lot_number' => $datos['supplier_lot_number'] ?? null,
                'expiration_date' => $datos['expiration_date'] ?? null,
            ]);
        });
    }

    /**
     * Divide el lote por ubicar en partes (lo que llegó en tandas o con lotes distintos del
     * fabricante). La primera parte se queda con el lote; cada una de las demás es un lote nuevo
     * de la misma línea, bodega, fecha y costo. Las partes deben sumar todo el lote y su valor
     * también: la última toma el redondeo. Su reparto en ubicaciones se vuelve a hacer.
     *
     * @param  array<int, array{quantity: numeric, supplier_lot_number?: ?string, expiration_date?: ?string}>  $partes
     * @return Collection<int, InventoryLot> el lote y los nuevos, en orden
     */
    public function dividirLote(InventoryLot $lot, array $partes): Collection
    {
        return DB::transaction(function () use ($lot, $partes) {
            if (! $lot->refreshWithLock()->isPending()) {
                throw new InvalidArgumentException('Solo se divide un lote por ubicar.');
            }

            $partes = array_values($partes);
            $lote = (float) $lot->quantity_received;
            $suma = array_sum(array_map(fn ($p) => (float) $p['quantity'], $partes));

            if (count($partes) < 2) {
                throw ValidationException::withMessages(['parts' => 'Indique al menos dos partes para dividir el lote.']);
            }

            if (abs($suma - $lote) > 0.00001) {
                throw ValidationException::withMessages(['parts' => 'Las partes suman '.$this->formato($suma).' y el lote tiene '.$this->formato($lote).': deben sumar todo el lote.']);
            }

            $costo = (float) $lot->unit_cost;
            $pendiente = (float) $lot->total_cost;
            $lotes = collect();

            foreach ($partes as $i => $parte) {
                $cantidad = (float) $parte['quantity'];
                $total = $i === count($partes) - 1 ? round($pendiente, 4) : round($cantidad * $costo, 4);
                $pendiente -= $total;

                $datos = [
                    'quantity_received' => $cantidad,
                    'total_cost' => $total,
                    'supplier_lot_number' => $parte['supplier_lot_number'] ?? null,
                    'expiration_date' => $parte['expiration_date'] ?? null,
                ];

                if ($i === 0) {
                    $lot->update($datos);
                    $lotes->push($lot);

                    continue;
                }

                $lotes->push(InventoryLot::create($datos + [
                    'lot_code' => $this->codigoDeLote($lot->received_at),
                    'id_product' => $lot->id_product,
                    'id_purchase_detail' => $lot->id_purchase_detail,
                    'id_warehouse' => $lot->id_warehouse,
                    'received_at' => $lot->received_at,
                    'unit_cost' => $costo,
                    'status' => InventoryLot::STATUS_PENDING,
                ]));
            }

            // Su reparto era para la cantidad de antes (uno por uno, para la bitácora)
            $lot->placements()->get()->each->delete();

            return $lotes;
        });
    }

    /**
     * Coloca el lote según su reparto: cada ubicación es una entrada al inventario, la empresa
     * suma su existencia y valor, y el lote queda disponible.
     */
    public function confirmarColocacion(InventoryLot $lot): void
    {
        DB::transaction(function () use ($lot) {
            if (! $lot->refreshWithLock()->isPending()) {
                throw new InvalidArgumentException("El lote {$lot->lot_code} ya no está por ubicar.");
            }

            $reparto = $lot->placements()->orderBy('id_inventory_lot_placement')->get();
            $lineas = $reparto->map(fn ($p) => ['id_location' => $p->id_location, 'quantity' => (float) $p->quantity])->all();

            if ($reparto->isEmpty()) {
                throw ValidationException::withMessages(['placements' => 'Asigne el lote a al menos una ubicación antes de confirmar su colocación.']);
            }

            // Se bloquean las ubicaciones: dos colocaciones a la vez no deben pasar su capacidad
            Location::queryAllBranches()->whereKey($reparto->pluck('id_location'))->orderBy('id_location')->lockForUpdate()->get();

            $this->validarReparto($lot, $lineas, completo: true);

            $costo = (float) $lot->unit_cost;
            $pendiente = (float) $lot->total_cost;
            $purchaseId = $lot->purchaseDetail()->value('id_purchase');

            foreach ($reparto->values() as $i => $parte) {
                $cantidad = (float) $parte->quantity;
                // El redondeo de cada parte puede dejar una diferencia: la última toma lo que
                // falta para que las entradas sumen el valor del lote
                $total = $i === $reparto->count() - 1 ? round($pendiente, 4) : round($cantidad * $costo, 4);
                $pendiente -= $total;

                $this->registrarMovimiento($lot, [
                    'id_warehouse' => $lot->id_warehouse,
                    'id_location' => $parte->id_location,
                    'movement_type' => InventoryMovement::TYPE_PURCHASE_RECEIPT,
                    'quantity_in' => $cantidad,
                    'unit_cost' => $costo,
                    'total_cost' => $total,
                    'reference_type' => InventoryMovement::REFERENCE_PURCHASE,
                    'reference_id' => $purchaseId,
                ]);
            }

            $this->acumularPromedio($this->empresaDeBodega($lot->id_warehouse), $lot->id_product, (float) $lot->quantity_received, (float) $lot->total_cost);

            $lot->update(['status' => InventoryLot::STATUS_ACTIVE, 'placed_at' => now()]);
        });
    }

    /**
     * Mueve existencia de un lote disponible a otra ubicación de la misma bodega: sale de una y
     * entra a la otra al costo del lote. La empresa no cambia su existencia ni su valor.
     */
    public function cambiarUbicacion(InventoryLot $lot, int $warehouseId, ?int $origenId, int $destinoId, float $cantidad): void
    {
        DB::transaction(function () use ($lot, $warehouseId, $origenId, $destinoId, $cantidad) {
            if ($lot->refreshWithLock()->status !== InventoryLot::STATUS_ACTIVE) {
                throw new InvalidArgumentException('Solo se cambia de ubicación un lote disponible.');
            }

            if ($origenId === $destinoId) {
                throw ValidationException::withMessages(['id_destination_location' => 'La ubicación destino debe ser distinta de la de origen.']);
            }

            $destino = Location::queryAllBranches()->whereKey($destinoId)->lockForUpdate()->first();
            $this->validarUbicacion($destino, $warehouseId, $cantidad, 'id_destination_location');

            $hay = (float) InventoryMovement::queryAllBranches()
                ->where('id_inventory_lot', $lot->id_inventory_lot)
                ->where('id_warehouse', $warehouseId)
                ->where(fn ($q) => $origenId ? $q->where('id_location', $origenId) : $q->whereNull('id_location'))
                ->sum(DB::raw('quantity_in - quantity_out'));

            if ($cantidad > $hay + 0.00001) {
                throw ValidationException::withMessages(['quantity' => 'Del lote '.$lot->lot_code.' solo hay '.$this->formato($hay).' en esa ubicación.']);
            }

            $costo = (float) $lot->unit_cost;
            $total = round($cantidad * $costo, 4);

            foreach ([[$origenId, InventoryMovement::TYPE_RELOCATION_OUT, 'quantity_out'], [$destinoId, InventoryMovement::TYPE_RELOCATION_IN, 'quantity_in']] as [$ubicacion, $tipo, $campo]) {
                $this->registrarMovimiento($lot, [
                    'id_warehouse' => $warehouseId,
                    'id_location' => $ubicacion,
                    'movement_type' => $tipo,
                    $campo => $cantidad,
                    'unit_cost' => $costo,
                    'total_cost' => $total,
                    'reference_type' => InventoryMovement::REFERENCE_RELOCATION,
                ]);
            }
        });
    }

    /**
     * Revisa cada parte del reparto (ubicación de la bodega del lote, activa, con capacidad, una
     * vez cada una) y que no pase del lote o, al confirmarlo, que lo cubra completo. Si algo falla
     * se vuelve a asignar: los errores van a la fila del formulario (placements.N.campo).
     *
     * @param  array<int, array{id_location: int|string, quantity: numeric}>  $lineas
     */
    private function validarReparto(InventoryLot $lot, array $lineas, bool $completo): void
    {
        $ubicaciones = Location::queryAllBranches()->whereKey(array_column($lineas, 'id_location'))->get()->keyBy('id_location');
        $errores = [];
        $vistas = [];
        $total = 0.0;

        foreach ($lineas as $i => $linea) {
            $id = (int) $linea['id_location'];
            $cantidad = (float) $linea['quantity'];
            $total += $cantidad;

            if (in_array($id, $vistas, true)) {
                $errores["placements.{$i}.id_location"] = 'Esa ubicación ya está en otra fila del reparto: sume ahí la cantidad.';

                continue;
            }
            $vistas[] = $id;

            if ($cantidad <= 0) {
                $errores["placements.{$i}.quantity"] = 'La cantidad debe ser mayor que cero.';

                continue;
            }

            try {
                $this->validarUbicacion($ubicaciones->get($id), $lot->id_warehouse, $cantidad, "placements.{$i}.id_location");
            } catch (ValidationException $e) {
                $errores += $e->errors();
            }
        }

        $lote = (float) $lot->quantity_received;

        if ($total > $lote + 0.00001) {
            $errores['placements'] = 'El reparto suma '.$this->formato($total).' y el lote tiene '.$this->formato($lote).'.';
        } elseif ($completo && abs($total - $lote) > 0.00001) {
            $errores['placements'] = 'Falta ubicar '.$this->formato($lote - $total).' de '.$this->formato($lote).': el reparto debe cubrir todo el lote para confirmar su colocación.';
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * La ubicación debe ser de la bodega, estar activa y tener lugar para la cantidad. Una
     * capacidad de 0 es sin límite.
     */
    private function validarUbicacion(?Location $location, int $warehouseId, float $cantidad, string $campo): void
    {
        if (! $location || (int) $location->id_warehouse !== $warehouseId) {
            throw ValidationException::withMessages([$campo => 'La ubicación debe ser de la bodega del lote.']);
        }

        if (! $location->is_active) {
            throw ValidationException::withMessages([$campo => "La ubicación {$location->code} está inactiva."]);
        }

        if ((int) $location->capacity > 0) {
            $ocupado = (float) InventoryMovement::queryAllBranches()
                ->where('id_location', $location->id_location)
                ->sum(DB::raw('quantity_in - quantity_out'));

            if ($ocupado + $cantidad > (float) $location->capacity + 0.00001) {
                throw ValidationException::withMessages([$campo => sprintf(
                    'La ubicación %s tiene capacidad para %s y ya guarda %s: caben %s más.',
                    $location->code,
                    $this->formato($location->capacity),
                    $this->formato($ocupado),
                    $this->formato(max(0, $location->capacity - $ocupado))
                )]);
            }
        }
    }

    /**
     * Existencia del lote en cada bodega y ubicación (solo donde queda algo).
     *
     * @return Collection<int, object{id_warehouse: int, id_location: ?int, stock: float}>
     */
    public function existenciasDelLote(InventoryLot $lot): Collection
    {
        return InventoryMovement::query()
            ->where('id_inventory_lot', $lot->id_inventory_lot)
            ->groupBy('id_warehouse', 'id_location')
            ->selectRaw('id_warehouse, id_location, SUM(quantity_in - quantity_out) AS stock')
            ->havingRaw('SUM(quantity_in - quantity_out) > 0')
            ->with(['warehouse', 'location'])
            ->get();
    }

    private function registrarMovimiento(InventoryLot $lot, array $datos): InventoryMovement
    {
        return InventoryMovement::create($datos + [
            'id_product' => $lot->id_product,
            'id_inventory_lot' => $lot->id_inventory_lot,
            'movement_date' => now(),
            'quantity_in' => 0,
            'quantity_out' => 0,
            'id_user' => Auth::id(),
        ]);
    }

    /**
     * Suma (o resta) cantidad y valor al producto en la empresa y recalcula su costo
     * promedio. Sin existencia, el valor queda en cero y se conserva el último promedio.
     */
    public function acumularPromedio(int $companyId, int $productId, float $cantidad, float $valor): void
    {
        // Se crea si no existe y se bloquea: dos entradas a la vez no deben pisarse
        InventoryCostAverage::queryAllBranches()->insertOrIgnore([
            'id_company' => $companyId,
            'id_product' => $productId,
            'updated_at' => now(),
        ]);

        $promedio = InventoryCostAverage::queryAllBranches()
            ->where('id_company', $companyId)
            ->where('id_product', $productId)
            ->lockForUpdate()
            ->firstOrFail();

        $existencia = round((float) $promedio->quantity_on_hand + $cantidad, 4);
        $valorTotal = $existencia > 0 ? round((float) $promedio->inventory_value + $valor, 4) : 0;

        $promedio->update([
            'quantity_on_hand' => $existencia,
            'inventory_value' => $valorTotal,
            'average_unit_cost' => $existencia > 0 ? round($valorTotal / $existencia, 4) : $promedio->average_unit_cost,
        ]);
    }

    /**
     * Código del lote con la fecha de recepción: LOT-20261012-001.
     */
    private function codigoDeLote(\DateTimeInterface $fecha): string
    {
        return DocumentSequence::next('LOT-'.$fecha->format('Ymd').'-', 'inventory_lots', 'lot_code', 3);
    }

    private function empresaDeBodega(int $warehouseId): int
    {
        return (int) Branch::queryAllBranches()
            ->whereKey(Warehouse::queryAllBranches()->whereKey($warehouseId)->value('id_branch'))
            ->value('id_company');
    }

    private function formato(float|int|string $cantidad): string
    {
        return rtrim(rtrim(number_format((float) $cantidad, 4, '.', ''), '0'), '.');
    }
}
