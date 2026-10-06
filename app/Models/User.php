<?php

namespace App\Models;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\BranchAccess;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'id_user';

    /**
     * Columna de la contraseña (para la autenticación de Laravel).
     */
    protected $authPasswordName = 'password_hash';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'id_branch',
        'password_hash',
        'is_active',
    ];

    /**
     * Valores por defecto en memoria, iguales a los de la base de datos.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relación con la sucursal asignada al usuario.
     */
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class, 'id_branch');
    }

    /**
     * Relación con los roles asignados al usuario.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'users_roles', 'id_user', 'id_role')->withPivot('assigned_at');
    }

    /**
     * Verifica si el usuario tiene un rol determinado.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * Usuarios que el usuario actual puede ver: todos si no tiene restricción, o solo
     * los de su sucursal. User no usa BranchScope porque la autenticación consulta
     * este modelo y el scope depende del usuario autenticado.
     */
    public function scopeAccessible(Builder $query): void
    {
        if (BranchAccess::isUnrestricted()) {
            return;
        }

        $branchId = BranchAccess::branchId();

        $branchId === null
            ? $query->whereRaw('1 = 0')
            : $query->where($this->qualifyColumn('id_branch'), $branchId);
    }

    /**
     * Verifica si la cuenta del usuario está activa.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Verifica si el usuario es administrador del sistema.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Obtiene el nombre del primer rol asignado al usuario.
     */
    public function getRoleAttribute(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Verifica si el usuario tiene un permiso determinado a través de sus roles.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles->flatMap->permissions->contains('id_permission', $permission);
    }

    /**
     * Envía la notificación de restablecimiento de contraseña en español con diseño premium.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}