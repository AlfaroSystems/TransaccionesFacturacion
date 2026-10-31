<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
            $modelClass = 'App\\Models\\'.basename($file, '.php');
            if (class_exists($modelClass) && is_subclass_of($modelClass, Model::class) && $modelClass !== AuditLog::class) {
                $modelClass::observe(AuditObserver::class);
            }
        }

        // Permisos: el administrador tiene acceso total; los demás, los permisos de sus roles.
        // Se resuelve aquí, con los roles del usuario, en lugar de definir un Gate por cada
        // permiso de la base: así no se consulta la tabla de permisos en cada petición.
        Gate::before(function (User $user, string $ability) {
            return $user->isAdmin() || $user->hasPermission($ability) ? true : null;
        });

        // Crear y editar empresas y administrar roles: solo el administrador. No es un permiso
        // asignable porque un rol con él podría darse a sí mismo cualquier otro permiso
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Los parámetros de ruta que son el id de un registro solo aceptan números: con un texto
        // (/purchases/abc) la consulta llegaría a PostgreSQL y respondería con un error 500 en
        // lugar de un 404. Las rutas se registran después de este boot, así que ya los toman
        Route::patterns(array_fill_keys([
            'id', 'branch', 'category', 'company', 'expense_type', 'image', 'location', 'product',
            'purchase', 'purchase_order', 'purchase_quotation', 'purchase_quotation_request',
            'purchaseQuotationRequest', 'purchase_request', 'purchaseRequest', 'retaceo', 'role',
            'subcategory', 'supplier', 'unit', 'user', 'warehouse', 'warehouse_category', 'transfer', 'lot', 'print',
        ], '[0-9]+'));
    }
}
