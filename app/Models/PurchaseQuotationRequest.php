<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Solicitud de cotización a proveedores. Reúne una o varias solicitudes de compra
 * aprobadas (de cualquier sucursal); cada línea apunta a una línea de solicitud de compra.
 */
class PurchaseQuotationRequest extends Model
{
    use HasFactory, BelongsToBranch;

    // Visible si alguna de sus solicitudes de compra es visible para el usuario
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->whereHas('details.purchaseRequestDetail.purchaseRequest');
    }

    const UPDATED_AT = null;

    protected $table = 'purchase_quotation_requests';

    protected $primaryKey = 'id_purchase_quotation_request';

    protected $fillable = [
        'id_purchase_quotation',
    ];

    /**
     * Crea la solicitud de cotización con todas las líneas de las solicitudes de compra
     * (se cotizan completas). Las solicitudes deben traer cargado su detalle.
     *
     * @param  Collection<int, PurchaseRequest>  $purchaseRequests
     */
    public static function createFromPurchaseRequests(Collection $purchaseRequests): self
    {
        $quotationRequest = static::create();

        foreach ($purchaseRequests as $purchaseRequest) {
            foreach ($purchaseRequest->details as $detail) {
                $quotationRequest->details()->create([
                    'id_purchase_request_detail' => $detail->id_purchase_request_detail,
                    'quantity' => $detail->quantity,
                ]);
            }
        }

        return $quotationRequest;
    }

    /**
     * Líneas de la solicitud, una por cada línea de solicitud de compra.
     */
    public function details(): HasMany
    {
        return $this->hasMany(
            PurchaseQuotationRequestDetail::class,
            'id_purchase_quotation_request',
            'id_purchase_quotation_request'
        );
    }

    /**
     * Solicitudes de compra que reúne (las de sus líneas). Requiere cargar
     * details.purchaseRequestDetail.purchaseRequest.
     *
     * @return Collection<int, PurchaseRequest>
     */
    public function getPurchaseRequestsAttribute(): Collection
    {
        return $this->details
            ->map(fn ($detail) => $detail->purchaseRequestDetail?->purchaseRequest)
            ->filter()
            ->unique('id_purchase_request')
            ->sortBy('purchase_request_code')
            ->values();
    }

    /**
     * Lo que se cotiza a los proveedores: una línea por producto y unidad con la cantidad
     * total, y cuánto corresponde a cada solicitud de compra (para distribuir después).
     * Requiere cargar details.purchaseRequestDetail con product, unit y purchaseRequest.
     *
     * @return Collection<int, object{key: string, product: ?Product, unit: ?Unit, quantity: float, sources: Collection}>
     */
    public function quotationLines(): Collection
    {
        return $this->details
            ->filter(fn ($detail) => $detail->purchaseRequestDetail)
            ->groupBy(fn ($detail) => $detail->purchaseRequestDetail->id_product.'-'.$detail->purchaseRequestDetail->id_unit)
            ->map(fn (Collection $group, string $key) => (object) [
                'key' => $key,
                'product' => $group->first()->purchaseRequestDetail->product,
                'unit' => $group->first()->purchaseRequestDetail->unit,
                'quantity' => $group->sum(fn ($detail) => (float) $detail->quantity),
                'sources' => $group->map(fn ($detail) => (object) [
                    'purchaseRequest' => $detail->purchaseRequestDetail->purchaseRequest,
                    'quantity' => (float) $detail->quantity,
                ])->values(),
            ])
            ->values();
    }

    /**
     * Ya se adjudicó: sus líneas apuntan a la línea ganadora de alguna oferta.
     */
    public function isAwarded(): bool
    {
        if ($this->relationLoaded('details')) {
            return $this->details->contains(fn ($detail) => $detail->id_purchase_quotation_detail !== null);
        }

        return $this->details()->whereNotNull('id_purchase_quotation_detail')->exists();
    }

    /**
     * Órdenes de compra generadas de sus ofertas (de cualquier sucursal destino).
     */
    public function generatedOrders(): Builder
    {
        return PurchaseOrder::queryAllBranches()->whereIn(
            'id_purchase_quotation',
            PurchaseQuotation::queryAllBranches()
                ->where('id_purchase_quotation_request', $this->id_purchase_quotation_request)
                ->select('id_purchase_quotation')
        );
    }

    /**
     * Cotizaciones recibidas de proveedores para esta solicitud.
     */
    public function supplierQuotations(): HasMany
    {
        return $this->hasMany(
            PurchaseQuotation::class,
            'id_purchase_quotation_request',
            'id_purchase_quotation_request'
        );
    }
}
