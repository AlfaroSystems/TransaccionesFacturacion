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
class PurchaseRequest extends Model
{
    use HasFactory, BelongsToBranch;

    /**
     * Flujo: la sucursal crea el borrador y lo envía al departamento de compras, que la
     * aprueba, la devuelve (vuelve a la sucursal para corregirla) o la rechaza. De una
     * aprobada se genera la solicitud de cotización. Una vez enviada, nadie puede
     * editarla ni eliminarla.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_QUOTED = 'quoted';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_SENT => 'Enviada',
        self::STATUS_RETURNED => 'Devuelta',
        self::STATUS_REJECTED => 'Rechazada',
        self::STATUS_APPROVED => 'Aprobada',
        self::STATUS_QUOTED => 'En cotización',
    ];

    /** Estados en que la sucursal aún puede editarla, eliminarla y enviarla */
    public const EDITABLE_STATUSES = [self::STATUS_DRAFT, self::STATUS_RETURNED];

    /** Estados que el departamento de compras ve de las demás sucursales */
    public const SUBMITTED_STATUSES = [self::STATUS_SENT, self::STATUS_REJECTED, self::STATUS_APPROVED, self::STATUS_QUOTED];

    public function restrictToBranch(Builder $query, int $branchId): void
    {
        // El departamento de compras ve además lo que le enviaron las demás sucursales
        // de su empresa (no sus borradores ni las devueltas, que están en la sucursal)
        if (BranchAccess::isPurchasingDepartment()) {
            $query->where(fn (Builder $q) => $q
                ->where($this->qualifyColumn('id_branch'), $branchId)
                ->orWhere(fn (Builder $q) => $q
                    ->whereIn($this->qualifyColumn('status'), self::SUBMITTED_STATUSES)
                    ->whereIn($this->qualifyColumn('id_branch'), BranchAccess::companyBranchIds())));

            return;
        }

        $query->where($this->qualifyColumn('id_branch'), $branchId);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    protected $table = 'purchase_requests';
    protected $primaryKey = 'id_purchase_request';
    protected $fillable = [
        'uuid',
        'purchase_request_code',
        'id_branch',
        'id_warehouse',
        'id_user',
        'request_date',
        'required_date',
        'justification',
        'status',
        'status_reason',
        'sent_at',
        'notes',
    ];
    protected $casts = [
        'request_date' => 'datetime',
        'required_date' => 'datetime',
        'sent_at' => 'datetime',
    ];
    /**
     * Usuario que creó la solicitud.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
    /**
     * Sucursal de la solicitud.
     */
    public function branch(): BelongsTo
    {
        // Sin el filtro por sucursal: quien ve la solicitud (p. ej. el departamento de
        // compras, de otra sucursal) debe ver también su sucursal y su bodega
        return $this->belongsTo(Branch::class, 'id_branch')->withoutGlobalScope(BranchScope::class);
    }
    /**
     * Bodega de la solicitud.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse')->withoutGlobalScope(BranchScope::class);
    }
    /**
     * Productos/detalles de la solicitud.
     */
    public function details(): HasMany
    {
        return $this->hasMany(
            PurchaseRequestDetail::class,
            'id_purchase_request',
            'id_purchase_request'
        );
    }

    /**
     * Solicitudes de cotización vinculadas a esta solicitud de compra.
     */
    public function quotationRequests(): HasMany
    {
        return $this->hasMany(
            PurchaseQuotationRequest::class,
            'id_purchase_request',
            'id_purchase_request'
        );
    }
}