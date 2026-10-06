<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $primaryKey = 'id_permission';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id_permission', 'name', 'description', 'action'];

    /**
     * Relación con los roles que tienen este permiso.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_permissions', 'id_permission', 'id_role');
    }
}