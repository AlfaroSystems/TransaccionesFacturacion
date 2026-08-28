<?php

namespace App\Observers;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->logActivity($model, 'created', null, $model->getAttributes());
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Obtener solo las columnas que cambiaron
        $changes = $model->getChanges();
        
        // Si no hay cambios reales en base de datos, no registramos nada
        if (empty($changes)) {
            return;
        }

        $original = [];
        foreach ($changes as $key => $value) {
            $original[$key] = $model->getOriginal($key);
        }

        $this->logActivity($model, 'updated', $original, $changes);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->logActivity($model, 'deleted', $model->getAttributes(), null);
    }

    /**
     * Registra la actividad de auditoría en la tabla audit_logs.
     */
    protected function logActivity(Model $model, string $event, ?array $originalData, ?array $modifiedData): void
    {
        // Evitar bucle infinito si se audita el propio modelo de logs
        if ($model instanceof \App\Models\AuditLog) {
            return;
        }

        // 1. Filtrar campos sensibles por seguridad
        if ($originalData) {
            $originalData = $this->filterSensibleFields($originalData);
        }
        if ($modifiedData) {
            $modifiedData = $this->filterSensibleFields($modifiedData);
        }

        // 2. Determinar controlador y acción que originó la solicitud
        $controller = 'Console/System';
        $action = 'Console/System';

        if (!app()->runningInConsole() && request()->route()) {
            $actionName = request()->route()->getActionName(); // Ej: "App\Http\Controllers\UserController@store"
            if (strpos($actionName, '@') !== false) {
                list($ctrl, $act) = explode('@', $actionName);
                $controller = class_basename($ctrl);
                $action = $act;
            } else {
                $controller = class_basename($actionName);
                $action = 'Closure';
            }
        }

        // 3. Insertar el registro en la bitácora
        AuditLog::create([
            'user_id' => auth()->check() ? auth()->id() : null,
            'id_record' => is_numeric($model->getKey()) ? (int) $model->getKey() : null,
            'controller' => $controller,
            'action' => $action,
            'original_data' => $originalData,
            'modified_data' => $modifiedData,
        ]);
    }

    /**
     * Enmascara valores de campos confidenciales para evitar guardarlos en texto plano en la bitácora.
     */
    protected function filterSensibleFields(array $data): array
    {
        $sensitiveFields = ['password', 'password_hash', 'remember_token', 'token', 'csrf-token', '_token'];
        foreach ($sensitiveFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = '[PROTEGIDO]';
            }
        }
        return $data;
    }
}