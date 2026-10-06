<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'logs';
    protected $primaryKey = 'id_log';

    // Desactivamos timestamps estándar ya que la tabla solo maneja created_at
    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'auditable_type',
        'id_record',
        'controller',
        'action',
        'original_data',
        'modified_data'
    ];

    protected $casts = [
        'original_data' => 'array',
        'modified_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Nombre corto del modelo afectado (por ejemplo, "PurchaseOrder").
     */
    public function getAuditableNameAttribute(): ?string
    {
        return $this->auditable_type ? class_basename($this->auditable_type) : null;
    }

    /**
     * Relación con el usuario que ejecutó la acción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}