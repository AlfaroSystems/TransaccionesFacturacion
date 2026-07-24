<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    // Desactivamos timestamps estándar ya que la tabla solo maneja created_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
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
     * Relación con el usuario que ejecutó la acción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
