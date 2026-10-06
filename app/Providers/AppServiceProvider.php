<?php

namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

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
        // Fuera de producción, cargar una relación fila por fila en un listado (consultas N+1)
        // lanza un error en lugar de pasar desapercibido
        Model::preventLazyLoading(! $this->app->isProduction());

        // Registrar el observador de auditoría para todos los modelos de negocio del sistema
        $modelFiles = glob(app_path('Models/*.php'));
        foreach ($modelFiles as $file) {
            $modelClass = 'App\\Models\\' . basename($file, '.php');
            if (class_exists($modelClass) && is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class) && $modelClass !== \App\Models\AuditLog::class) {
                $modelClass::observe(AuditObserver::class);
            }
        }

        // Permisos: el administrador tiene acceso total; los demás, los permisos de sus roles.
        // Se resuelve aquí, con los roles del usuario, en lugar de definir un Gate por cada
        // permiso de la base: así no se consulta la tabla de permisos en cada petición.
        Gate::before(function (User $user, string $ability) {
            return $user->isAdmin() || $user->hasPermission($ability) ? true : null;
        });
    }
}