<?php

namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Permission;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar el observador de auditoría para todos los modelos de negocio del sistema
        $modelFiles = glob(app_path('Models/*.php'));
        foreach ($modelFiles as $file) {
            $modelClass = 'App\\Models\\' . basename($file, '.php');
            if (class_exists($modelClass) && is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class) && $modelClass !== \App\Models\AuditLog::class) {
                $modelClass::observe(AuditObserver::class);
            }
        }

        // 1. Bypass global: Super Administrador obtiene acceso total por defecto
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // 2. Registrar Gates dinámicamente a partir de los permisos en base de datos
        try {
            if (Schema::hasTable('permissions')) {
                // Precargar relaciones para optimizar consultas a base de datos
                $permissions = Permission::with('roles')->get();
                
                foreach ($permissions as $permission) {
                    Gate::define($permission->id, function (User $user) use ($permission) {
                        return $user->hasPermission($permission->id);
                    });
                }
            }
        } catch (\Exception $e) {
            // Evitar errores durante migraciones iniciales o consola
        }
    }
}