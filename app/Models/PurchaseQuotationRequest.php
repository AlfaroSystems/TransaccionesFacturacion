<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Scopes\BranchScope;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Solicitud de cotización a proveedores. Reúne solicitudes de compra aprobadas (de
 * cualquier sucursal) y productos que el departamento de compras cotiza sin solicitud de
 * compra, que van a su destino (id_branch / id_warehouse).
 *
 * En borrador se pueden cambiar sus productos; al registrar la primera oferta de un
 * proveedor queda enviada.
 */
class PurchaseQuotationRequest extends Model
{
    use BelongsToBranch, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_SENT => 'Enviada',
    ];

    // Visible si alguna de sus solicitudes de compra es visible para el usuario, o si lo
    // es la sucursal de destino de sus productos sin solicitud de compra
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $destinations = BranchAccess::isPurchasingDepartment() ? BranchAccess::companyBranchIds() : [$branchId];

        $query->where(fn (Builder $q) => $q
            ->whereHas('details.purchaseRequestDetail.purchaseRequest')
            ->orWhereIn($this->qualifyColumn('id_branch'), $destinations));
    }

    const UPDATED_AT = null;

    protected $table = 'purchase_quotation_requests';

    protected $primaryKey = 'id_purchase_quotation_request';

    protected $fillable = [
        'id_purchase_quotation',
        'status',
        'sent_at',
        'id_branch',
        'id_warehouse',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Crea la solicitud de cotización en borrador con todas las líneas de las solicitudes
     * de compra (deben traer cargado su detalle) y los productos sin solicitud de compra,
     * que van al destino indicado en $attributes (id_branch / id_warehouse).
     *
     * @param  Collection<int, PurchaseRequest>  $purchaseRequests
     * @param  array<int, array{id_product: int, id_unit: int, quantity: float|string}>  $products
     */
    public static function createFromPurchaseRequests(Collection $purchaseRequests, array $products = [], array $attributes = []): self
    {
        $quotationRequest = static::create($attributes);

        foreach ($purchaseRequests as $purchaseRequest) {
            foreach ($purchaseRequest->details as $detail) {
                $quotationRequest->details()->create([
                    'id_purchase_request_detail' => $detail->id_purchase_request_detail,
                    'id_product' => $detail->id_product,
                    'id_unit' => $detail->id_unit,
                    'quantity' => $detail->quantity,
                ]);
            }
        }

        $quotationRequest->addProducts($products);

        return $quotationRequest;
    }

    /**
     * Agrega líneas sin solicitud de compra.
     *
     * @param  array<int, array{id_product: int, id_unit: int, quantity: float|string}>  $products
     */
    public function addProducts(array $products): void
    {
        foreach ($products as $product) {
            $this->details()->create([
                'id_product' => $product['id_product'],
                'id_unit' => $product['id_unit'],
                'quantity' => $product['quantity'],
            ]);
        }
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * Pasa a enviada (al registrar una oferta); desde entonces sus productos ya no cambian.
     */
    public function markAsSent(): void
    {
        if ($this->isDraft()) {
            $this->update(['status' => self::STATUS_SENT, 'sent_at' => now()]);
        }
    }

    /**
     * Sucursal de destino de los productos sin solicitud de compra.
     */
    public function branch(): BelongsTo
    {
        // Sin el filtro por sucursal: el departamento de compras elige cualquier sucursal
        // de su empresa como destino
        return $this->belongsTo(Branch::class, 'id_branch')->withoutGlobalScope(BranchScope::class);
    }

    /**
     * Bodega de destino de los productos sin solicitud de compra.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse')->withoutGlobalScope(BranchScope::class);
    }

    /**
     * Líneas de la solicitud: una por cada línea de solicitud de compra y una por cada
     * producto agregado sin solicitud de compra.
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
     * total, y cuánto corresponde a cada solicitud de compra (para distribuir después); los
     * productos sin solicitud de compra tienen purchaseRequest null.
     * Requiere cargar details con product, unit y purchaseRequestDetail.purchaseRequest.
     *
     * @return Collection<int, object{key: string, product: ?Product, unit: ?Unit, quantity: float, sources: Collection}>
     */
    public function quotationLines(): Collection
    {
        return $this->details
            ->sortBy('id_purchase_quotation_request_detail')
            ->groupBy(fn ($detail) => $detail->lineKey())
            ->map(fn (Collection $group, string $key) => (object) [
                'key' => $key,
                'product' => $group->first()->product,
                'unit' => $group->first()->unit,
                'quantity' => $group->sum(fn ($detail) => (float) $detail->quantity),
                'sources' => $group->map(fn ($detail) => (object) [
                    'purchaseRequest' => $detail->purchaseRequestDetail?->purchaseRequest,
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
