<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationDetail;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDetail;
use App\Models\InventoryTransferDiscrepancy;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Traslados de lotes entre bodegas de una misma empresa (Casa Matriz reparte a las sucursales).
 *
 * 1. Borrador: líneas (lote, ubicación de origen y cantidad solicitada), vehículo y motorista.
 *    No mueve nada; se edita, cancela o elimina.
 * 2. Por autorizar: se envía con vehículo, motorista y existencia suficiente. Quien autoriza lo
 *    aprueba o lo rechaza con un motivo (vuelve a borrador).
 * 3. Autorizado → En preparación: se prepara lo solicitado (puede prepararse menos).
 * 4. Despachar: lo preparado sale de su ubicación de origen y entra al espacio de carga del
 *    vehículo, al costo actual del lote. Queda en tránsito.
 * 5. Recibir (una o varias veces): lo recibido sale del vehículo y entra a la ubicación destino.
 *    Lo que falta sigue en el vehículo (recepción parcial); lo que llega de más es un sobrante.
 * 6. Discrepancias: lo que sigue en el vehículo y no llegará se registra como faltante o daño;
 *    al regularizar, faltantes y daños salen del vehículo como pérdida (la empresa descuenta
 *    existencia y valor) y los sobrantes entran a la bodega destino. El traslado queda recibido.
 *
 * Las ubicaciones son ubicaciones de inventario (inventory_locations). Mientras la mercadería va
 * en el vehículo sigue en la empresa, así que el costo promedio solo cambia al regularizar.
 */
class InventoryTransferService
{
    // Fecha de la operación en curso: todos sus movimientos la llevan (el seguimiento agrupa por ella)
    private ?Carbon $momento = null;

    public function __construct(private readonly InventoryService $inventario = new InventoryService) {}

    /**
     * @param  array{id_source_warehouse: int, id_destination_warehouse: int, id_vehicle?: ?int, id_driver_employee?: ?int, transfer_date: string, notes?: ?string, details: array}  $datos
     */
    public function crear(array $datos): InventoryTransfer
    {
        return DB::transaction(function () use ($datos) {
            $this->validarExistencias((int) $datos['id_source_warehouse'], $datos['details']);

            $transfer = InventoryTransfer::create([
                'id_source_warehouse' => $datos['id_source_warehouse'],
                'id_destination_warehouse' => $datos['id_destination_warehouse'],
                'id_vehicle' => $datos['id_vehicle'] ?? null,
                'id_driver_employee' => $datos['id_driver_employee'] ?? null,
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
                'id_vehicle' => $datos['id_vehicle'] ?? null,
                'id_driver_employee' => $datos['id_driver_employee'] ?? null,
                'transfer_date' => $datos['transfer_date'],
                'notes' => $datos['notes'] ?? null,
            ]);

            // Uno por uno, para que cada borrado quede en la bitácora
            $transfer->details()->get()->each->delete();
            $this->guardarLineas($transfer, $datos['details']);

            return $transfer;
        });
    }

    /**
     * Borrador → Por autorizar. Debe tener líneas, vehículo con espacio de carga, motorista y
     * existencia para lo solicitado.
     */
    public function enviarAAutorizacion(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if (! $transfer->refreshWithLock()->isDraft()) {
                throw new InvalidArgumentException('Solo se envía a autorizar un traslado en borrador.');
            }

            $this->validarParaAutorizar($transfer);

            $transfer->update(['status' => InventoryTransfer::STATUS_PENDING_AUTHORIZATION]);
        });
    }

    public function autorizar(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if ($transfer->refreshWithLock()->status !== InventoryTransfer::STATUS_PENDING_AUTHORIZATION) {
                throw new InvalidArgumentException('Solo se autoriza un traslado enviado a autorización.');
            }

            // La existencia o el vehículo pudieron cambiar desde que se envió
            $this->validarParaAutorizar($transfer);

            $transfer->update([
                'status' => InventoryTransfer::STATUS_AUTHORIZED,
                'authorized_at' => now(),
                'id_authorized_by' => Auth::id(),
                'status_reason' => null,
            ]);
        });
    }

    /**
     * Vuelve a borrador con el motivo, para corregirlo y enviarlo de nuevo.
     */
    public function rechazar(InventoryTransfer $transfer, string $motivo): void
    {
        DB::transaction(function () use ($transfer, $motivo) {
            if ($transfer->refreshWithLock()->status !== InventoryTransfer::STATUS_PENDING_AUTHORIZATION) {
                throw new InvalidArgumentException('Solo se rechaza un traslado enviado a autorización.');
            }

            $transfer->update([
                'status' => InventoryTransfer::STATUS_DRAFT,
                'status_reason' => $motivo,
                'authorized_at' => null,
                'id_authorized_by' => null,
            ]);
        });
    }

    public function prepararDespacho(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if ($transfer->refreshWithLock()->status !== InventoryTransfer::STATUS_AUTHORIZED) {
                throw new InvalidArgumentException('Solo se prepara el despacho de un traslado autorizado.');
            }

            $transfer->update(['status' => InventoryTransfer::STATUS_PREPARING, 'prepared_at' => now()]);
        });
    }

    /**
     * Despacha lo preparado de cada línea (sin indicar, lo solicitado): sale de su ubicación de
     * origen y entra al espacio de carga elegido del vehículo.
     *
     * @param  array<int, numeric>  $preparado  cantidad por id de línea
     */
    public function despachar(InventoryTransfer $transfer, array $preparado, int $vehicleLocationId): void
    {
        DB::transaction(function () use ($transfer, $preparado, $vehicleLocationId) {
            if ($transfer->refreshWithLock()->status !== InventoryTransfer::STATUS_PREPARING) {
                throw new InvalidArgumentException('Solo se despacha un traslado en preparación.');
            }

            $this->momento = now();

            $detalles = $transfer->details()->with('product')->orderBy('id_inventory_transfer_detail')->get();
            $cantidades = [];
            $errores = [];

            foreach ($detalles as $detalle) {
                $id = $detalle->id_inventory_transfer_detail;
                $cantidad = round((float) ($preparado[$id] ?? $detalle->quantity_requested), 4);

                if ($cantidad < 0 || $cantidad > (float) $detalle->quantity_requested + 0.00001) {
                    $errores["prepared.{$id}"] = 'De '.($detalle->product?->name ?? 'el producto').' se solicitaron '
                        .$this->formato($detalle->quantity_requested).': no se despacha más de lo solicitado.';
                }

                $cantidades[$id] = $cantidad;
            }

            if ($errores) {
                throw ValidationException::withMessages($errores);
            }

            if (array_sum($cantidades) <= 0) {
                throw ValidationException::withMessages(['prepared' => 'Indique lo preparado de al menos una línea.']);
            }

            $espacio = $this->espacioDelVehiculo($transfer, $vehicleLocationId);

            // Se bloquean sus lotes: dos despachos a la vez no deben sacar la misma existencia
            InventoryLot::queryAllBranches()
                ->whereKey($detalles->pluck('id_inventory_lot')->unique())
                ->orderBy('id_inventory_lot')
                ->lockForUpdate()
                ->get();

            $lineas = $detalles->filter(fn ($d) => $cantidades[$d->id_inventory_transfer_detail] > 0)
                ->map(fn ($d) => [
                    'id_inventory_lot' => $d->id_inventory_lot,
                    'id_source_inventory_location' => $d->id_source_inventory_location,
                    'quantity_requested' => $cantidades[$d->id_inventory_transfer_detail],
                ])->all();

            if ($faltantes = $this->faltantes($transfer->id_source_warehouse, $lineas)) {
                throw new InvalidArgumentException('No se puede despachar: '.reset($faltantes));
            }

            $this->validarCapacidad($espacio, array_sum($cantidades));

            $lugarVehiculo = (int) $espacio->inventoryLocation->id_inventory_location;
            $costos = InventoryLot::queryAllBranches()->whereKey($detalles->pluck('id_inventory_lot'))->pluck('unit_cost', 'id_inventory_lot');

            foreach ($detalles as $detalle) {
                $cantidad = $cantidades[$detalle->id_inventory_transfer_detail];
                $costo = (float) $costos[$detalle->id_inventory_lot];

                $detalle->update(['quantity_dispatched' => $cantidad, 'unit_cost' => $costo]);

                if ($cantidad <= 0) {
                    continue;
                }

                $total = round($cantidad * $costo, 4);

                $this->registrarMovimiento($transfer, $detalle, [
                    'id_warehouse' => $transfer->id_source_warehouse,
                    'id_inventory_location' => $detalle->id_source_inventory_location,
                    'movement_type' => InventoryMovement::TYPE_TRANSFER_OUT,
                    'quantity_out' => $cantidad,
                    'unit_cost' => $costo,
                    'total_cost' => $total,
                ]);

                $this->registrarMovimiento($transfer, $detalle, [
                    'id_warehouse' => $espacio->id_warehouse,
                    'id_inventory_location' => $lugarVehiculo,
                    'movement_type' => InventoryMovement::TYPE_TRANSFER_LOAD,
                    'quantity_in' => $cantidad,
                    'unit_cost' => $costo,
                    'total_cost' => $total,
                ]);
            }

            $transfer->update([
                'status' => InventoryTransfer::STATUS_IN_TRANSIT,
                'dispatched_at' => now(),
                'id_vehicle_location' => $espacio->id_vehicle_location,
            ]);
        });
    }

    /**
     * Recibe lo que llegó de cada línea (sin indicar, todo lo que sigue en el vehículo) en una
     * ubicación de la bodega destino. Lo que pase de lo pendiente queda como sobrante.
     *
     * @param  array<int, array{quantity_received?: numeric, id_destination_inventory_location?: ?int}>  $recibido  por id de línea
     */
    public function recibir(InventoryTransfer $transfer, array $recibido): void
    {
        DB::transaction(function () use ($transfer, $recibido) {
            if (! $transfer->refreshWithLock()->isReceivable()) {
                throw new InvalidArgumentException('Solo se recibe un traslado en tránsito o con recepción parcial.');
            }

            $this->momento = now();

            $detalles = $transfer->details()->with(['product', 'discrepancies'])->orderBy('id_inventory_transfer_detail')->get();
            $lineas = [];
            $errores = [];

            foreach ($detalles as $detalle) {
                $id = $detalle->id_inventory_transfer_detail;
                $pendiente = $detalle->pendingQuantity();
                $cantidad = round((float) ($recibido[$id]['quantity_received'] ?? $pendiente), 4);
                $ubicacion = $this->ubicacionDestino($detalle, $recibido[$id] ?? []);

                if ($cantidad < 0) {
                    $errores["received.{$id}.quantity_received"] = 'La cantidad recibida no puede ser negativa.';
                } elseif ($cantidad > 0 && ! $ubicacion) {
                    $errores["received.{$id}.id_destination_inventory_location"] = 'Elija la ubicación de la bodega destino donde se recibe '
                        .($detalle->product?->name ?? 'el producto').'.';
                }

                $lineas[$id] = [$detalle, $cantidad, $pendiente, $ubicacion];
            }

            if ($errores) {
                throw ValidationException::withMessages($errores);
            }

            if (array_sum(array_column($lineas, 1)) <= 0) {
                throw ValidationException::withMessages(['received' => 'Indique lo recibido de al menos una línea.']);
            }

            $espacio = $this->espacioDelTraslado($transfer);

            foreach ($lineas as [$detalle, $cantidad, $pendiente, $ubicacion]) {
                if ($cantidad <= 0) {
                    continue;
                }

                $costo = (float) $detalle->unit_cost;
                $delVehiculo = min($cantidad, $pendiente);
                $sobrante = round($cantidad - $delVehiculo, 4);

                if ($delVehiculo > 0) {
                    $total = round($delVehiculo * $costo, 4);

                    $this->registrarMovimiento($transfer, $detalle, [
                        'id_warehouse' => $espacio->id_warehouse,
                        'id_inventory_location' => $espacio->inventoryLocation->id_inventory_location,
                        'movement_type' => InventoryMovement::TYPE_TRANSFER_UNLOAD,
                        'quantity_out' => $delVehiculo,
                        'unit_cost' => $costo,
                        'total_cost' => $total,
                    ]);

                    $this->registrarMovimiento($transfer, $detalle, [
                        'id_warehouse' => $transfer->id_destination_warehouse,
                        'id_inventory_location' => $ubicacion,
                        'movement_type' => InventoryMovement::TYPE_TRANSFER_IN,
                        'quantity_in' => $delVehiculo,
                        'unit_cost' => $costo,
                        'total_cost' => $total,
                    ]);
                }

                // Llegó más de lo que iba en el vehículo: se regulariza como sobrante
                if ($sobrante > 0) {
                    $detalle->discrepancies()->create([
                        'discrepancy_type' => InventoryTransferDiscrepancy::TYPE_SURPLUS,
                        'quantity' => $sobrante,
                        'id_destination_inventory_location' => $ubicacion,
                        'notes' => 'Llegó más de lo despachado.',
                        'id_user' => Auth::id(),
                    ]);
                }

                $detalle->update([
                    'quantity_received' => round((float) $detalle->quantity_received + $delVehiculo, 4),
                    'id_destination_inventory_location' => $ubicacion,
                ]);
            }

            $this->actualizarEstadoDeRecepcion($transfer);
        });
    }

    /**
     * Recepción parcial → En tránsito: lo que falta sigue en el vehículo y llega después.
     */
    public function continuarEnTransito(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if ($transfer->refreshWithLock()->status !== InventoryTransfer::STATUS_PARTIALLY_RECEIVED) {
                throw new InvalidArgumentException('Solo vuelve a tránsito un traslado con recepción parcial.');
            }

            $transfer->update(['status' => InventoryTransfer::STATUS_IN_TRANSIT]);
        });
    }

    /**
     * Registra (o corrige, mientras no se regularicen) los faltantes, daños y sobrantes de cada
     * línea. Faltantes y daños no pueden pasar de lo que sigue en el vehículo.
     *
     * @param  array<int, array{shortage?: numeric, damage?: numeric, surplus?: numeric, id_destination_inventory_location?: ?int, notes?: ?string}>  $lineas  por id de línea
     */
    public function registrarDiscrepancias(InventoryTransfer $transfer, array $lineas): void
    {
        DB::transaction(function () use ($transfer, $lineas) {
            if (! $transfer->refreshWithLock()->isReceivable()) {
                throw new InvalidArgumentException('Las discrepancias se registran al recibir un traslado en tránsito.');
            }

            $detalles = $transfer->details()->with(['product', 'discrepancies'])->orderBy('id_inventory_transfer_detail')->get();
            $errores = [];
            $total = 0.0;

            foreach ($detalles as $detalle) {
                $id = $detalle->id_inventory_transfer_detail;
                $linea = $lineas[$id] ?? [];
                $faltante = round((float) ($linea['shortage'] ?? 0), 4);
                $dano = round((float) ($linea['damage'] ?? 0), 4);
                $sobrante = round((float) ($linea['surplus'] ?? 0), 4);
                $producto = $detalle->product?->name ?? 'el producto';

                if ($faltante < 0 || $dano < 0 || $sobrante < 0) {
                    $errores["discrepancies.{$id}.shortage"] = 'Las cantidades no pueden ser negativas.';
                } elseif ($faltante + $dano > $detalle->pendingQuantity() + 0.00001) {
                    $errores["discrepancies.{$id}.shortage"] = 'De '.$producto.' quedan '.$this->formato($detalle->pendingQuantity())
                        .' en el vehículo: faltante y daño no pueden pasar de eso.';
                } elseif ($sobrante > 0 && empty($linea['id_destination_inventory_location'])) {
                    $errores["discrepancies.{$id}.id_destination_inventory_location"] = 'Elija la ubicación donde entra el sobrante de '.$producto.'.';
                }

                $total += $faltante + $dano + $sobrante;
            }

            if ($errores) {
                throw ValidationException::withMessages($errores);
            }

            if ($total <= 0) {
                throw ValidationException::withMessages(['discrepancies' => 'Indique al menos un faltante, daño o sobrante.']);
            }

            foreach ($detalles as $detalle) {
                $linea = $lineas[$detalle->id_inventory_transfer_detail] ?? [];

                // Las que no se han regularizado se reemplazan (una por una, para la bitácora)
                $detalle->discrepancies->whereNull('regularized_at')->each->delete();

                foreach ([
                    InventoryTransferDiscrepancy::TYPE_SHORTAGE => 'shortage',
                    InventoryTransferDiscrepancy::TYPE_DAMAGE => 'damage',
                    InventoryTransferDiscrepancy::TYPE_SURPLUS => 'surplus',
                ] as $tipo => $campo) {
                    $cantidad = round((float) ($linea[$campo] ?? 0), 4);

                    if ($cantidad > 0) {
                        $detalle->discrepancies()->create([
                            'discrepancy_type' => $tipo,
                            'quantity' => $cantidad,
                            'id_destination_inventory_location' => $tipo === InventoryTransferDiscrepancy::TYPE_SURPLUS ? (int) $linea['id_destination_inventory_location'] : null,
                            'notes' => $linea['notes'] ?? null,
                            'id_user' => Auth::id(),
                        ]);
                    }
                }
            }

            $transfer->update(['status' => InventoryTransfer::STATUS_WITH_DISCREPANCY]);
        });
    }

    /**
     * Aplica las discrepancias al inventario y cierra el traslado. Todo lo que sigue en el
     * vehículo debe estar registrado como faltante o daño.
     */
    public function regularizar(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if ($transfer->refreshWithLock()->status !== InventoryTransfer::STATUS_WITH_DISCREPANCY) {
                throw new InvalidArgumentException('Solo se regulariza un traslado con discrepancias.');
            }

            $this->momento = now();

            $detalles = $transfer->details()->with(['product', 'discrepancies'])->orderBy('id_inventory_transfer_detail')->get();

            foreach ($detalles as $detalle) {
                $bajas = $detalle->discrepancies->whereNull('regularized_at')
                    ->where('discrepancy_type', '!=', InventoryTransferDiscrepancy::TYPE_SURPLUS)
                    ->sum(fn ($d) => (float) $d->quantity);

                if (abs($detalle->pendingQuantity() - $bajas) > 0.00001) {
                    throw new InvalidArgumentException('Del producto '.($detalle->product?->name ?? '').' quedan '
                        .$this->formato($detalle->pendingQuantity() - $bajas).' en el vehículo sin recibir ni registrar como faltante o daño: '
                        .'recíbalos o corrija las discrepancias.');
                }
            }

            $espacio = $this->espacioDelTraslado($transfer);
            $empresa = $this->empresaDeBodega($transfer->id_source_warehouse);

            foreach ($detalles as $detalle) {
                $costo = (float) $detalle->unit_cost;

                foreach ($detalle->discrepancies->whereNull('regularized_at') as $discrepancia) {
                    $cantidad = (float) $discrepancia->quantity;
                    $valor = round($cantidad * $costo, 4);

                    if ($discrepancia->discrepancy_type === InventoryTransferDiscrepancy::TYPE_SURPLUS) {
                        $this->registrarMovimiento($transfer, $detalle, [
                            'id_warehouse' => $transfer->id_destination_warehouse,
                            'id_inventory_location' => $discrepancia->id_destination_inventory_location,
                            'movement_type' => InventoryMovement::TYPE_TRANSFER_SURPLUS,
                            'quantity_in' => $cantidad,
                            'unit_cost' => $costo,
                            'total_cost' => $valor,
                        ]);
                        $this->inventario->acumularPromedio($empresa, $detalle->id_product, $cantidad, $valor);
                    } else {
                        $this->registrarMovimiento($transfer, $detalle, [
                            'id_warehouse' => $espacio->id_warehouse,
                            'id_inventory_location' => $espacio->inventoryLocation->id_inventory_location,
                            'movement_type' => $discrepancia->discrepancy_type === InventoryTransferDiscrepancy::TYPE_DAMAGE
                                ? InventoryMovement::TYPE_TRANSFER_DAMAGE
                                : InventoryMovement::TYPE_TRANSFER_LOSS,
                            'quantity_out' => $cantidad,
                            'unit_cost' => $costo,
                            'total_cost' => $valor,
                        ]);
                        $this->inventario->acumularPromedio($empresa, $detalle->id_product, -$cantidad, -$valor);
                    }

                    $discrepancia->update(['regularized_at' => now()]);
                }
            }

            $transfer->update(['status' => InventoryTransfer::STATUS_RECEIVED, 'received_at' => now()]);
        });
    }

    public function cancelar(InventoryTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if (! $transfer->refreshWithLock()->isCancellable()) {
                throw new InvalidArgumentException('Solo se cancela un traslado que no se ha despachado: uno en tránsito se recibe.');
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
     * Espacios de carga activos del vehículo del traslado, en bodegas de su empresa.
     *
     * @return Collection<int, VehicleLocation>
     */
    public function espaciosDelVehiculo(InventoryTransfer $transfer): Collection
    {
        if (! $transfer->id_vehicle) {
            return collect();
        }

        return VehicleLocation::queryAllBranches()
            ->with(['inventoryLocation', 'warehouseAllBranches'])
            ->where('id_vehicle', $transfer->id_vehicle)
            ->where('is_active', true)
            ->whereIn('id_warehouse', Warehouse::queryAllBranches()->whereIn('id_branch', $this->sucursalesDeLaEmpresa($transfer->id_source_warehouse))->select('id_warehouse'))
            ->orderBy('code')
            ->get();
    }

    /**
     * Existencia de cada lote por ubicación de inventario en la bodega, con clave "lote|ubicación".
     *
     * @return Collection<string, float>
     */
    public function existencias(int $warehouseId, ?Collection $lotIds = null): Collection
    {
        return InventoryLocationDetail::queryAllBranches()
            ->whereIn('id_inventory_location', InventoryLocation::query()->inWarehouses($warehouseId)->select('id_inventory_location'))
            ->when($lotIds, fn ($q) => $q->whereIn('id_inventory_lot', $lotIds))
            ->get(['id_inventory_lot', 'id_inventory_location', 'quantity'])
            ->mapWithKeys(fn ($fila) => [self::clave($fila->id_inventory_lot, $fila->id_inventory_location) => (float) $fila->quantity]);
    }

    public static function clave(int|string $lotId, int|string|null $inventoryLocationId): string
    {
        return $lotId.'|'.($inventoryLocationId ?? '');
    }

    /**
     * Para enviarlo o autorizarlo: líneas, vehículo activo con espacio de carga, motorista activo
     * y existencia para lo solicitado.
     */
    private function validarParaAutorizar(InventoryTransfer $transfer): void
    {
        $detalles = $transfer->details()->get();

        if ($detalles->isEmpty()) {
            throw new InvalidArgumentException('El traslado no tiene productos.');
        }

        $vehiculo = $transfer->id_vehicle ? Vehicle::find($transfer->id_vehicle) : null;
        if (! $vehiculo?->is_active) {
            throw new InvalidArgumentException('Asigne un vehículo activo al traslado (edite el borrador).');
        }

        if ($this->espaciosDelVehiculo($transfer)->isEmpty()) {
            throw new InvalidArgumentException("El vehículo {$vehiculo->license_plate} no tiene espacios de carga activos en bodegas de la empresa: agréguelos en Vehículos.");
        }

        $motorista = $transfer->id_driver_employee ? Employee::queryAllBranches()->find($transfer->id_driver_employee) : null;
        if (! $motorista?->is_active) {
            throw new InvalidArgumentException('Asigne un motorista activo al traslado (edite el borrador).');
        }

        $lineas = $detalles->map->only(['id_inventory_lot', 'id_source_inventory_location', 'quantity_requested'])->all();

        if ($faltantes = $this->faltantes($transfer->id_source_warehouse, $lineas)) {
            throw new InvalidArgumentException('No hay existencia suficiente: '.reset($faltantes));
        }
    }

    /**
     * El espacio elegido debe ser un espacio activo del vehículo del traslado.
     */
    private function espacioDelVehiculo(InventoryTransfer $transfer, int $vehicleLocationId): VehicleLocation
    {
        $espacio = $this->espaciosDelVehiculo($transfer)->firstWhere('id_vehicle_location', $vehicleLocationId);

        if (! $espacio) {
            throw ValidationException::withMessages(['id_vehicle_location' => 'Elija un espacio de carga activo del vehículo del traslado.']);
        }

        return $espacio;
    }

    /**
     * Espacio del vehículo donde viaja la mercadería del traslado.
     */
    private function espacioDelTraslado(InventoryTransfer $transfer): VehicleLocation
    {
        return VehicleLocation::queryAllBranches()->with('inventoryLocation')->findOrFail($transfer->id_vehicle_location);
    }

    /**
     * Lo que se carga debe caber en el espacio. Una capacidad de 0 es sin límite.
     */
    private function validarCapacidad(VehicleLocation $espacio, float $cantidad): void
    {
        if ((int) $espacio->capacity <= 0) {
            return;
        }

        $ocupado = (float) InventoryLocationDetail::queryAllBranches()
            ->where('id_inventory_location', $espacio->inventoryLocation->id_inventory_location)
            ->sum('quantity');

        if ($ocupado + $cantidad > (float) $espacio->capacity + 0.00001) {
            throw ValidationException::withMessages(['id_vehicle_location' => sprintf(
                'El espacio %s tiene capacidad para %s y ya lleva %s: caben %s más.',
                $espacio->code,
                $this->formato($espacio->capacity),
                $this->formato($ocupado),
                $this->formato(max(0, $espacio->capacity - $ocupado))
            )]);
        }
    }

    /**
     * Recibido todo y sin discrepancias por regularizar: recibido. Con discrepancias: con
     * discrepancia. Si no, sigue en recepción parcial.
     */
    private function actualizarEstadoDeRecepcion(InventoryTransfer $transfer): void
    {
        $detalles = $transfer->details()->with('discrepancies')->get();
        $porRegularizar = $detalles->contains(fn ($d) => $d->discrepancies->whereNull('regularized_at')->isNotEmpty());
        $pendiente = $detalles->contains(fn ($d) => $d->pendingQuantity() > 0.00001);

        if ($porRegularizar) {
            $transfer->update(['status' => InventoryTransfer::STATUS_WITH_DISCREPANCY]);
        } elseif ($pendiente) {
            $transfer->update(['status' => InventoryTransfer::STATUS_PARTIALLY_RECEIVED]);
        } else {
            $transfer->update(['status' => InventoryTransfer::STATUS_RECEIVED, 'received_at' => now()]);
        }
    }

    /**
     * Ubicación de inventario donde se recibe la línea: la elegida al recibir o la del borrador.
     */
    private function ubicacionDestino(InventoryTransferDetail $detalle, array $linea): ?int
    {
        $ubicacion = array_key_exists('id_destination_inventory_location', $linea)
            ? $linea['id_destination_inventory_location']
            : $detalle->id_destination_inventory_location;

        return $ubicacion ? (int) $ubicacion : null;
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
                'id_source_inventory_location' => $linea['id_source_inventory_location'],
                'id_destination_inventory_location' => $linea['id_destination_inventory_location'] ?? null,
                'quantity_requested' => $linea['quantity_requested'],
                'quantity_dispatched' => 0,
                'unit_cost' => $lote->unit_cost,
                'notes' => $linea['notes'] ?? null,
            ]);
        }
    }

    /**
     * Líneas que piden más de lo que hay del lote en su ubicación de origen (sumando las líneas
     * del mismo lote y ubicación).
     *
     * @return array<string, string> mensaje por campo (details.N.quantity_requested)
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
            $clave = self::clave($linea['id_inventory_lot'], $linea['id_source_inventory_location'] ?? null);
            $pedido[$clave] = ($pedido[$clave] ?? 0) + (float) $linea['quantity_requested'];
            $hay = $stock[$clave] ?? 0;

            if ($pedido[$clave] > $hay + 0.00001) {
                $errores["details.{$i}.quantity_requested"] = 'Del lote '.($codigos[$linea['id_inventory_lot']] ?? '')
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
            'movement_date' => $this->momento ?? now(),
            'quantity_in' => 0,
            'quantity_out' => 0,
            'reference_type' => InventoryMovement::REFERENCE_TRANSFER,
            'reference_id' => $transfer->id_inventory_transfer,
            'id_user' => Auth::id(),
        ]);
    }

    private function empresaDeBodega(int $warehouseId): int
    {
        return (int) Branch::queryAllBranches()
            ->whereKey(Warehouse::queryAllBranches()->whereKey($warehouseId)->value('id_branch'))
            ->value('id_company');
    }

    private function sucursalesDeLaEmpresa(int $warehouseId)
    {
        return Branch::queryAllBranches()
            ->where('id_company', $this->empresaDeBodega($warehouseId))
            ->select('id_branch');
    }

    private function formato(float|int|string $cantidad): string
    {
        return rtrim(rtrim(number_format((float) $cantidad, 4, '.', ''), '0'), '.');
    }
}
