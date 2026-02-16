<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MetricaUsuario; // Cambia esto
use App\Services\UsuariosMetricasService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerarMetricasDiarias extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'metricas:generar-diarias 
                            {--fecha= : Fecha para la que generar métricas (formato: Y-m-d)}
                            {--force : Forzar generación incluso si ya existen métricas para esa fecha}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Genera métricas diarias de volumen de usuarios';

    /**
     * Ejecutar el comando.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando generación de métricas diarias...');

        // Determinar la fecha
        $fecha = $this->option('fecha') 
            ? Carbon::createFromFormat('Y-m-d', $this->option('fecha'))
            : Carbon::yesterday();

        $this->line("Fecha objetivo: {$fecha->format('Y-m-d')}");

        // Verificar si ya existen métricas para esta fecha (si no se fuerza)
        if (!$this->option('force')) {
            $existe = MetricaUsuario::where('fecha', $fecha->format('Y-m-d')) // Usa MetricaUsuario
                ->where('tipo_reporte', 'volumen')
                ->exists();

            if ($existe) {
                $this->warn('Ya existen métricas para esta fecha. Usa --force para regenerarlas.');
                return 1;
            }
        } else {
            $this->warn('Forzando regeneración de métricas...');
        }

        try {
            $service = new UsuariosMetricasService();
            
            // Generar métricas - Necesitas pasar todos los parámetros requeridos
            $metricasVolumen = $service->calcularMetricasVolumen(
                $fecha->copy()->startOfDay(),
                $fecha->copy()->endOfDay(),
                'diaria',
                null,  // tipo_usuario (null para todos)
                null   // estado (null para todos)
            );
            
            // Verifica la estructura de los datos devueltos
            $metricasData = $metricasVolumen['metricas'] ?? $metricasVolumen;
            
            $registrosGuardados = 0;
            
            // Guardar en base de datos
            foreach ($metricasData as $metrica) {
                MetricaUsuario::updateOrCreate( // Usa MetricaUsuario
                    [
                        'fecha' => $metrica['fecha'] ?? $fecha->format('Y-m-d'),
                        'tipo_reporte' => 'volumen'
                    ],
                    [
                        'tipo_usuario' => $metrica['tipo_usuario'] ?? null,
                        'datos' => json_encode($metrica),
                        'total_usuarios' => $metrica['total_usuarios'] ?? 0,
                        'usuarios_activos' => $metrica['activos'] ?? 0,
                        'usuarios_nuevos' => $metrica['usuarios'] ?? 0,
                        'tasa_crecimiento' => $metrica['tasa_crecimiento'] ?? 0,
                        'dau' => $metrica['dau'] ?? 0,
                        'mau' => $metrica['mau'] ?? 0,
                        'ratio_dau_mau' => $metrica['ratio_dau_mau'] ?? 0
                    ]
                );
                
                $registrosGuardados++;
            }
            
            Log::info('Métricas diarias generadas para: ' . $fecha->format('Y-m-d'));
            
            $this->info("✓ Métricas generadas exitosamente para {$fecha->format('Y-m-d')}");
            $this->info("✓ Registros guardados: {$registrosGuardados}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error al generar métricas: ' . $e->getMessage());
            Log::error('Error generando métricas diarias: ' . $e->getMessage());
            return 1;
        }
    }
}