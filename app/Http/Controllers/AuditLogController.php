<?php

namespace App\Http\Controllers;
use App\Models\AuditLog;
use App\Models\User;
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
        $query = AuditLog::with('user');

        // Filtrar por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filtrar por controlador
        if ($request->filled('controller')) {
            $query->where('controller', 'like', "%{$request->input('controller')}%");
        }

        // Filtrar por acción
        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->input('action')}%");
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
        $users = User::orderBy('name')->get();

        return view('audit_logs.index', compact('logs', 'users'));
    }
}