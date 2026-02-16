<?php
// app/Console/Commands/EjecutarReportesProgramados.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportesUsuarios;
use App\Services\Reportes\ReporteUsuariosService;
use App\Models\EjecucionReporteUsuario;

class EjecutarReportesProgramados extends Command
{
    protected $signature = 'reportes:ejecutar-programados';
    protected $description = 'Ejecuta los reportes programados que están pendientes';

    public function handle()
    {
        $reportes = ReportesUsuarios::programados()
            ->where(function ($query) {
                // Reportes que nunca se han ejecutado o cuya última ejecución fue antes del período programado
                $query->whereNull('ultima_generacion')
                      ->orWhereRaw('ultima_generacion < DATE_SUB(NOW(), INTERVAL 1 DAY)');
            })
            ->get();

        $this->info("Encontrados {$reportes->count()} reportes programados para ejecutar");

        foreach ($reportes as $reporte) {
            $this->ejecutarReporte($reporte);
        }

        $this->info('Ejecución de reportes programados completada');
    }

    private function ejecutarReporte(ReportesUsuarios $reporte)
    {
        $this->info("Ejecutando reporte: {$reporte->nombre}");

        try {
            $service = new ReporteUsuariosService($reporte);
            $resultados = $service->ejecutar();
            $service->guardarEjecucion($resultados);

            $this->info("✓ Reporte {$reporte->nombre} ejecutado exitosamente");

        } catch (\Exception $e) {
            $this->error("✗ Error ejecutando reporte {$reporte->nombre}: " . $e->getMessage());
            
            EjecucionReporteUsuario::create([
                'reporte_id' => $reporte->id,
                'estado' => EjecucionReporteUsuario::ESTADO_FALLIDO,
                'mensaje_error' => $e->getMessage()
            ]);
        }
    }
}