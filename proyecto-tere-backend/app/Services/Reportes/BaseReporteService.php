<?php
// app/Services/Reportes/BaseReporteService.php

namespace App\Services\Reportes;

use App\Models\Reporte;
use App\Models\EjecucionReporteUsuario;
use App\Models\ReportesUsuarios;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

abstract class BaseReporteService
{
    protected $reporte;
    protected $parametros;
    protected $filtros;
    protected $resultados = [];
    protected $inicioEjecucion;

    public function __construct(ReportesUsuarios $reporte, array $parametros = [])
    {
        $this->reporte = $reporte;
        $this->parametros = array_merge($reporte->parametros ?? [], $parametros);
        $this->filtros = $reporte->filtros ?? [];
        $this->inicioEjecucion = microtime(true);
    }

    abstract public function ejecutar(): array;

    protected function aplicarFiltrosFecha($query, $campo = 'created_at')
    {
        if (isset($this->parametros['fecha_inicio'])) {
            $query->whereDate($campo, '>=', $this->parametros['fecha_inicio']);
        }

        if (isset($this->parametros['fecha_fin'])) {
            $query->whereDate($campo, '<=', $this->parametros['fecha_fin']);
        }

        return $query;
    }

    protected function segmentarPor($query, $campo)
    {
        if (isset($this->parametros['segmentacion']) && $this->parametros['segmentacion'] === $campo) {
            return $query->groupBy($campo);
        }

        return $query;
    }

    protected function calcularComparacion($valorActual, $valorAnterior)
    {
        if ($valorAnterior == 0) {
            return $valorActual > 0 ? 100 : 0;
        }

        return (($valorActual - $valorAnterior) / $valorAnterior) * 100;
    }

    protected function formatearNumero($numero, $decimales = 2)
    {
        return number_format($numero, $decimales, '.', ',');
    }

    protected function generarResumen(array $datos): array
    {
        return [
            'total_registros' => count($datos),
            'periodo' => [
                'inicio' => $this->parametros['fecha_inicio'] ?? null,
                'fin' => $this->parametros['fecha_fin'] ?? null
            ],
            'generado_en' => now()->toDateTimeString(),
            'tiempo_ejecucion' => round(microtime(true) - $this->inicioEjecucion, 2)
        ];
    }

    public function guardarEjecucion(array $resultados, string $formato = EjecucionReporteUsuario::FORMATO_JSON): EjecucionReporteUsuario
    {
        $tiempoEjecucion = round(microtime(true) - $this->inicioEjecucion, 2);

        $ejecucion = EjecucionReporteUsuario::create([
            'reporte_id' => $this->reporte->id,
            'user_id' => auth()->id(),
            'parametros' => $this->parametros,
            'resultados' => $resultados,
            'estado' => EjecucionReporteUsuario::ESTADO_COMPLETADO,
            'tiempo_ejecucion' => $tiempoEjecucion,
            'tamano_datos' => strlen(json_encode($resultados)),
            'formato' => $formato
        ]);

        // Actualizar última generación del reporte
        $this->reporte->update([
            'ultima_generacion' => now()
        ]);

        return $ejecucion;
    }
}