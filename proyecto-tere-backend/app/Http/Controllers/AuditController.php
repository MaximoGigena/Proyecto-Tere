<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    /**
     * Mostrar historial de auditorías
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user');
        
        // Filtros
        if ($request->filled('table_name')) {
            $query->where('table_name', $request->table_name);
        }
        
        if ($request->filled('record_id')) {
            $query->where('record_id', $request->record_id);
        }
        
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        }
        
        $audits = $query->orderBy('created_at', 'desc')
                       ->paginate(50);
        
        return view('audits.index', compact('audits'));
    }
    
    /**
     * Mostrar detalles de una auditoría
     */
    public function show($id)
    {
        $audit = AuditLog::with('user')->findOrFail($id);
        
        return view('audits.show', compact('audit'));
    }
    
    /**
     * Obtener auditorías de un usuario específico
     */
    public function getUserAudits($userId)
    {
        $audits = AuditLog::byUser($userId)
                         ->with('user')
                         ->orderBy('created_at', 'desc')
                         ->paginate(50);
        
        return view('audits.user', compact('audits'));
    }
    
    /**
     * Obtener auditorías de una tabla específica
     */
    public function getTableAudits($tableName)
    {
        $audits = AuditLog::forTable($tableName)
                         ->with('user')
                         ->orderBy('created_at', 'desc')
                         ->paginate(50);
        
        return view('audits.table', compact('audits', 'tableName'));
    }
    
    /**
     * API: Obtener historial de auditorías
     */
    public function apiIndex(Request $request)
    {
        $query = AuditLog::with('user');
        
        // Aplicar filtros
        foreach (['table_name', 'record_id', 'user_id', 'action'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->$filter);
            }
        }
        
        $audits = $query->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 25));
        
        return response()->json([
            'success' => true,
            'data' => $audits,
            'message' => 'Auditorías obtenidas correctamente'
        ]);
    }
}