<?php

namespace App\Http\Controllers;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request)
    {
        Gate::authorize('bitacora.ver');

        // Quien no es administrador solo ve los registros hechos por usuarios de su sucursal
        $visibleLogs = fn () => AuditLog::query()
            ->when(! BranchAccess::isUnrestricted(), fn ($q) => $q->whereIn('user_id', User::accessible()->select('id')));

        $query = $visibleLogs()->with('user');

        // Filtrar por modelo afectado
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->input('auditable_type'));
        }

        // Filtrar por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filtrar por controlador
        if ($request->filled('controller')) {
            $query->where('controller', 'ilike', "%{$request->input('controller')}%");
        }

        // Filtrar por acción
        if ($request->filled('action')) {
            $query->where('action', 'ilike', "%{$request->input('action')}%");
        }

        // Filtrar por fecha de inicio
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        // Filtrar por fecha de fin
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Obtener logs paginados con query string
        $logs = $query->latest('id')->paginate(15)->withQueryString();

        // Obtener usuarios para el selector del filtro
        $users = User::accessible()->orderBy('name')->get();

        // Modelos presentes en la bitácora visible, para el selector del filtro
        $auditableTypes = $visibleLogs()
            ->whereNotNull('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type');

        return view('audit_logs.index', compact('logs', 'users', 'auditableTypes'));
    }
}