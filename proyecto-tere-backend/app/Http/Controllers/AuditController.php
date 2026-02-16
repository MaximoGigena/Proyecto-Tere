<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditController extends Controller
{
    /**
     * API: Obtener historial de auditorías con filtros avanzados
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = AuditLog::with(['user:id,name,email'])
                ->select([
                    'id',
                    'table_name',
                    'record_id',
                    'action',
                    'old_data',
                    'new_data',
                    'changed_columns',
                    'user_id',
                    'ip_address',
                    'user_agent',
                    'created_at'
                ])
                ->orderBy('created_at', 'desc');

            // Filtro por ID
            if ($request->filled('id')) {
                $query->where('id', $request->id);
            }

            // Filtro por usuario (nombre o email)
            if ($request->filled('usuario')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->usuario . '%')
                      ->orWhere('email', 'like', '%' . $request->usuario . '%');
                });
            }

            // Filtro por tabla
            if ($request->filled('tabla')) {
                $query->where('table_name', 'like', '%' . $request->tabla . '%');
            }

            // Filtro por acción
            if ($request->filled('accion')) {
                $query->where('action', $request->accion);
            }

            // Filtro por fecha desde
            if ($request->filled('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            // Filtro por fecha hasta
            if ($request->filled('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            // Filtro por rango de fechas
            if ($request->filled('fecha')) {
                $query->whereDate('created_at', $request->fecha);
            }

            // Paginación
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            
            $auditorias = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $auditorias->items(),
                'pagination' => [
                    'current_page' => $auditorias->currentPage(),
                    'per_page' => $auditorias->perPage(),
                    'total' => $auditorias->total(),
                    'last_page' => $auditorias->lastPage(),
                    'from' => $auditorias->firstItem(),
                    'to' => $auditorias->lastItem(),
                ],
                'message' => 'Auditorías obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en apiIndex de auditorías: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las auditorías: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener estadísticas de auditoría
     */
    public function estadisticas()
    {
        try {
            $estadisticas = [
                'total_registros' => AuditLog::count(),
                'por_accion' => AuditLog::select('action', DB::raw('count(*) as total'))
                    ->groupBy('action')
                    ->orderBy('total', 'desc')
                    ->get(),
                'por_tabla' => AuditLog::select('table_name', DB::raw('count(*) as total'))
                    ->groupBy('table_name')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get(),
                'por_usuario' => AuditLog::select('user_id', DB::raw('count(*) as total'))
                    ->with(['user:id,name'])
                    ->groupBy('user_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get(),
                'actividad_reciente' => AuditLog::select(DB::raw('DATE(created_at) as fecha'), DB::raw('count(*) as total'))
                    ->whereDate('created_at', '>=', now()->subDays(30))
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->orderBy('fecha', 'desc')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en estadísticas de auditorías: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }

    /**
     * API: Exportar auditorías a CSV
     */
    public function exportarCSV(Request $request)
    {
        try {
            $query = AuditLog::with(['user:id,name,email'])
                ->orderBy('created_at', 'desc');

            // Aplicar filtros
            if ($request->filled('id')) {
                $query->where('id', $request->id);
            }

            if ($request->filled('usuario')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->usuario . '%')
                      ->orWhere('email', 'like', '%' . $request->usuario . '%');
                });
            }

            if ($request->filled('tabla')) {
                $query->where('table_name', 'like', '%' . $request->tabla . '%');
            }

            if ($request->filled('accion')) {
                $query->where('action', $request->accion);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $auditorias = $query->get();

            // Generar CSV
            $filename = 'auditorias_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($auditorias) {
                $file = fopen('php://output', 'w');
                
                // Encabezados
                fputcsv($file, [
                    'ID', 
                    'Tabla', 
                    'ID Registro', 
                    'Acción', 
                    'Usuario', 
                    'Email', 
                    'IP', 
                    'User Agent', 
                    'Fecha'
                ]);

                // Datos
                foreach ($auditorias as $audit) {
                    fputcsv($file, [
                        $audit->id,
                        $audit->table_name,
                        $audit->record_id,
                        $audit->action,
                        $audit->user ? $audit->user->name : 'Sistema',
                        $audit->user ? $audit->user->email : '',
                        $audit->ip_address ?? '',
                        $audit->user_agent ?? '',
                        $audit->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error al exportar CSV de auditorías: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el archivo CSV'
            ], 500);
        }
    }

    /**
     * API: Obtener detalles de una auditoría específica
     */
    public function show($id)
    {
        try {
            $audit = AuditLog::with(['user:id,name,email'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $audit,
                'message' => 'Auditoría obtenida correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener auditoría ' . $id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Auditoría no encontrada'
            ], 404);
        }
    }

    /**
     * API: Obtener tablas únicas para filtros
     */
    public function getTablasUnicas()
    {
        try {
            $tablas = AuditLog::select('table_name')
                ->distinct()
                ->orderBy('table_name')
                ->pluck('table_name');

            return response()->json([
                'success' => true,
                'data' => $tablas,
                'message' => 'Tablas obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener tablas únicas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tablas'
            ], 500);
        }
    }

    /**
     * Mostrar vista principal de auditorías (para web)
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user');
        
        // Filtros para vista web
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
     * Mostrar detalles de una auditoría (para web)
     */
    public function showWeb($id)
    {
        $audit = AuditLog::with('user')->findOrFail($id);
        
        return view('audits.show', compact('audit'));
    }
}