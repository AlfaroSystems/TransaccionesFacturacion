<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * Relación con los usuarios asignados a este rol.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('assigned_at');
    }

    /**
     * Relación con los permisos asignados a este rol.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
