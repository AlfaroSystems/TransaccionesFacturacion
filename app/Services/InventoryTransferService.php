<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDetail;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Traslados de lotes entre bodegas de una misma empresa (Casa Matriz reparte a las sucursales).
 *
 * - Borrador: se preparan las líneas (lote, ubicación de origen y cantidad); no mueve nada.
 * - Despachar: cada línea sale de su bodega y ubicación de origen (salida por traslado) al costo
 *   actual de su lote. Mientras viaja no está en ninguna bodega.
 * - Recibir: lo despachado entra a la bodega destino (entrada por traslado); si llegó menos, la
 *   diferencia sale ahí mismo como merma y la empresa la descuenta de su existencia y su valor.
 * - Solo se editan, cancelan o eliminan los borradores.
 *
 * El inventario sigue en la misma empresa, así que el costo promedio solo cambia por la merma.
 */
class InventoryTransferService
{
    public function __construct(private readonly InventoryService $inventario = new InventoryService) {}

    /**
     * @param  array{id_source_warehouse: int, id_destination_warehouse: int, id_vehicle_location?: ?int, transfer_date: string, notes?: ?string, details: array}  $datos
     */
    public function crear(array $datos): InventoryTransfer
    {
        return DB::transaction(function () use ($datos) {
            $this->validarExistencias((int) $datos['id_source_warehouse'], $datos['details']);

            $transfer = InventoryTransfer::create([
                'id_source_warehouse' => $datos['id_source_warehouse'],
                'id_destination_warehouse' => $datos['id_destination_warehouse'],
                'id_vehicle_location' => $datos['id_vehicle_location'] ?? null,
                'transfer_date' => $datos['transfer_date'],
                'notes' => $datos['notes'] ?? null,
                'id_user' => Auth::id(),
            ]);

            $this->guardarLineas($transfer, $datos['details']);

            return $transfer;
        });
    }

    /**
     * Las bodegas del traslado no cambian; se reemplazan sus líneas.
     */
    public function actualizar(InventoryTransfer $transfer, array $datos): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $datos) {
            if (! $transfer->refreshWithLock()->isDraft()) {
                throw new InvalidArgumentException('Solo se pueden editar traslados en borrador.');
            }

            $this->validarExistencias($transfer->id_source_warehouse, $datos['details']);

            $transfer->update([
                'id_vehicle_location' => $datos['id_vehicle_location'] ?? null,
                'transfer_date' => $datos['transfer_date'],
                'notes' => $datos['notes'] ?? null,
            ]);

            // Uno por uno, para que cada borrado quede en la bitácora
            $transfer->details()->get()->each->delete();
            $this->guardarLineas($transfer, $datos['details']);

            return $transfer;
        });
    }

    public function despachar(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if (! $transfer->refreshWithLock()->isDraft()) {
                throw new InvalidArgumentException('Solo se puede despachar un traslado en borrador.');
            }

            $detalles = $transfer->details()->get();

            if ($detalles->isEmpty()) {
                throw new InvalidArgumentException('El traslado no tiene productos.');
            }

            // Se bloquean sus lotes: dos despachos a la vez no deben sacar la misma existencia
            $lotes = InventoryLot::queryAllBranches()
                ->whereKey($detalles->pluck('id_inventory_lot')->unique())
                ->orderBy('id_inventory_lot')
                ->lockForUpdate()
                ->get()
                ->keyBy('id_inventory_lot');

            $faltantes = $this->faltantes($transfer->id_source_warehouse, $detalles->map->only(['id_inventory_lot', 'id_source_location', 'quantity_dispatched'])->all());

            if ($faltantes) {
                throw new InvalidArgumentException('No se puede despachar: '.reset($faltantes));
            }

            foreach ($detalles as $detalle) {
                $costo = (float) $lotes[$detalle->id_inventory_lot]->unit_cost;
                $cantidad = (float) $detalle->quantity_dispatched;

                $detalle->update(['unit_cost' => $costo]);

                $this->registrarMovimiento($transfer, $detalle, [
                    'id_warehouse' => $transfer->id_source_warehouse,
                    'id_location' => $detalle->id_source_location,
                    'movement_type' => InventoryMovement::TYPE_TRANSFER_OUT,
                    'quantity_out' => $cantidad,
                    'unit_cost' => $costo,
                    'total_cost' => round($cantidad * $costo, 4),
                ]);
            }

            $transfer->update(['status' => InventoryTransfer::STATUS_DISPATCHED, 'dispatched_at' => now()]);
        });
    }

    /**
     * @param  array<int, array{quantity_received?: numeric, id_destination_location?: ?int}>  $recibido  por id de línea
     */
    public function recibir(InventoryTransfer $transfer, array $recibido): void
    {
        DB::transaction(function () use ($transfer, $recibido) {
            if ($transfer->refreshWithLock()->status !== InventoryTransfer::STATUS_DISPATCHED) {
                throw new InvalidArgumentException('Solo se puede recibir un traslado despachado.');
            }

            $detalles = $transfer->details()->with('product')->get();
            $errores = [];

            foreach ($detalles as $detalle) {
                $cantidad = (float) ($recibido[$detalle->id_inventory_transfer_detail]['quantity_received'] ?? $detalle->quantity_dispatched);

                if ($cantidad < 0 || $cantidad > (float) $detalle->quantity_dispatched + 0.00001) {
                    $errores["received.{$detalle->id_inventory_transfer_detail}.quantity_received"] = 'De '.($detalle->product?->name ?? 'el producto')
                        .' se despacharon '.$this->formato($detalle->quantity_dispatched).': no puede recibirse más.';
                }
            }

            if ($errores) {
                throw ValidationException::withMessages($errores);
            }

            $empresa = (int) Branch::queryAllBranches()
                ->whereKey(Warehouse::queryAllBranches()->whereKey($transfer->id_destination_warehouse)->value('id_branch'))
                ->value('id_company');

            foreach ($detalles as $detalle) {
                $linea = $recibido[$detalle->id_inventory_transfer_detail] ?? [];
                $despachado = (float) $detalle->quantity_dispatched;
                $cantidad = (float) ($linea['quantity_received'] ?? $despachado);
                $costo = (float) $detalle->unit_cost;
                $ubicacion = array_key_exists('id_destination_location', $linea) ? $linea['id_destination_location'] : $detalle->id_destination_location;

                $detalle->update(['quantity_received' => $cantidad, 'id_destination_location' => $ubicacion]);

                // Entra lo despachado; si llegó menos, la diferencia sale como merma. Así la
                // existencia de la bodega es lo recibido y el kardex muestra la pérdida
                $this->registrarMovimiento($transfer, $detalle, [
                    'id_warehouse' => $transfer->id_destination_warehouse,
                    'id_location' => $ubicacion,
                    'movement_type' => InventoryMovement::TYPE_TRANSFER_IN,
                    'quantity_in' => $despachado,
                    'unit_cost' => $costo,
                    'total_cost' => round($despachado * $costo, 4),
                ]);

                $merma = round($despachado - $cantidad, 4);

                if ($merma > 0) {
                    $valor = round($merma * $costo, 4);

                    $this->registrarMovimiento($transfer, $detalle, [
                        'id_warehouse' => $transfer->id_destination_warehouse,
                        'id_location' => $ubicacion,
                        'movement_type' => InventoryMovement::TYPE_TRANSFER_LOSS,
                        'quantity_out' => $merma,
                        'unit_cost' => $costo,
                        'total_cost' => $valor,
                    ]);

                    $this->inventario->acumularPromedio($empresa, $detalle->id_product, -$merma, -$valor);
                }
            }

            $transfer->update(['status' => InventoryTransfer::STATUS_RECEIVED, 'received_at' => now()]);
        });
    }

    public function cancelar(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if (! $transfer->refreshWithLock()->isDraft()) {
                throw new InvalidArgumentException('Solo se puede cancelar un traslado en borrador: uno despachado se recibe.');
            }

            $transfer->update(['status' => InventoryTransfer::STATUS_CANCELLED]);
        });
    }

    public function eliminar(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if (! $transfer->refreshWithLock()->isDraft()) {
                throw new InvalidArgumentException('Solo se pueden eliminar traslados en borrador.');
            }

            $transfer->details()->get()->each->delete();
            $transfer->delete();
        });
    }

    /**
     * Existencia de cada lote por ubicación en la bodega, con clave "lote|ubicación".
     *
     * @return Collection<string, float>
     */
    public function existencias(int $warehouseId, ?Collection $lotIds = null): Collection
    {
        return InventoryMovement::queryAllBranches()
            ->where('id_warehouse', $warehouseId)
            ->when($lotIds, fn ($q) => $q->whereIn('id_inventory_lot', $lotIds))
            ->groupBy('id_inventory_lot', 'id_location')
            ->selectRaw('id_inventory_lot, id_location, SUM(quantity_in - quantity_out) AS stock')
            ->get()
            ->mapWithKeys(fn ($fila) => [self::clave($fila->id_inventory_lot, $fila->id_location) => (float) $fila->stock]);
    }

    public static function clave(int|string $lotId, int|string|null $locationId): string
    {
        return $lotId.'|'.($locationId ?? '');
    }

    private function guardarLineas(InventoryTransfer $transfer, array $lineas): void
    {
        $lotes = InventoryLot::queryAllBranches()->whereKey(array_column($lineas, 'id_inventory_lot'))->get()->keyBy('id_inventory_lot');

        foreach ($lineas as $linea) {
            $lote = $lotes[$linea['id_inventory_lot']];

            InventoryTransferDetail::create([
                'id_inventory_transfer' => $transfer->id_inventory_transfer,
                'id_inventory_lot' => $lote->id_inventory_lot,
                'id_product' => $lote->id_product,
                'id_source_location' => $linea['id_source_location'] ?? null,
                'id_destination_location' => $linea['id_destination_location'] ?? null,
                'quantity_dispatched' => $linea['quantity_dispatched'],
                'unit_cost' => $lote->unit_cost,
                'notes' => $linea['notes'] ?? null,
            ]);
        }
    }

    /**
     * Líneas que piden más de lo que hay del lote en su ubicación de origen (sumando las líneas
     * del mismo lote y ubicación).
     *
     * @return array<string, string> mensaje por campo (details.N.quantity_dispatched)
     */
    private function faltantes(int $warehouseId, array $lineas): array
    {
        $lotIds = collect($lineas)->pluck('id_inventory_lot')->unique();
        $stock = $this->existencias($warehouseId, $lotIds);
        $codigos = InventoryLot::queryAllBranches()->whereKey($lotIds)->pluck('lot_code', 'id_inventory_lot');
        $pedido = [];
        $errores = [];

        // Con la clave de cada línea: el formulario muestra el error en su fila
        foreach ($lineas as $i => $linea) {
            $clave = self::clave($linea['id_inventory_lot'], $linea['id_source_location'] ?? null);
            $pedido[$clave] = ($pedido[$clave] ?? 0) + (float) $linea['quantity_dispatched'];
            $hay = $stock[$clave] ?? 0;

            if ($pedido[$clave] > $hay + 0.00001) {
                $errores["details.{$i}.quantity_dispatched"] = 'Del lote '.($codigos[$linea['id_inventory_lot']] ?? '')
                    .' solo hay '.$this->formato($hay).' en esa ubicación de la bodega origen.';
            }
        }

        return $errores;
    }

    private function validarExistencias(int $warehouseId, array $lineas): void
    {
        if ($errores = $this->faltantes($warehouseId, $lineas)) {
            throw ValidationException::withMessages($errores);
        }
    }

    private function registrarMovimiento(InventoryTransfer $transfer, InventoryTransferDetail $detalle, array $datos): void
    {
        InventoryMovement::create($datos + [
            'id_product' => $detalle->id_product,
            'id_inventory_lot' => $detalle->id_inventory_lot,
            'movement_date' => now(),
            'quantity_in' => 0,
            'quantity_out' => 0,
            'reference_type' => InventoryMovement::REFERENCE_TRANSFER,
            'reference_id' => $transfer->id_inventory_transfer,
            'id_user' => Auth::id(),
        ]);
    }

    private function formato(float|string $cantidad): string
    {
        return rtrim(rtrim(number_format((float) $cantidad, 4, '.', ''), '0'), '.');
    }
}
