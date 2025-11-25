<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mascota;
use App\Models\EdadMascota;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActualizarEdadesMascotas extends Command
{
    protected $signature = 'mascotas:actualizar-edades';
    protected $description = 'Actualiza las edades calculadas de las mascotas';

    public function handle()
    {
        $this->info('Iniciando actualización de edades de mascotas...');
        Log::info('Iniciando job: ActualizarEdadesMascotas');

        $mascotas = Mascota::withTrashed()->with('edadRelacion')->get();
        $contador = 0;

        foreach ($mascotas as $mascota) {
            try {
                if ($mascota->fecha_nacimiento) {
                    // Obtener o crear la relación de edad usando el método correcto
                    $edadMascota = $mascota->edadRelacion;
                    
                    if (!$edadMascota) {
                        // Crear registro si no existe
                        $edadMascota = EdadMascota::create(['mascota_id' => $mascota->id]);
                    }
                    
                    // Actualizar usando el método del modelo
                    $edadMascota->actualizarDesdeFechaNacimiento($mascota->fecha_nacimiento);
                    
                    $contador++;
                    $this->info("Mascota ID {$mascota->id}: {$edadMascota->edad_formateada}");
                } else {
                    // Si no tiene fecha de nacimiento, limpiar el registro de edad
                    EdadMascota::where('mascota_id', $mascota->id)->delete();
                    $this->info("Mascota ID {$mascota->id}: Sin fecha de nacimiento - registro de edad eliminado");
                }
            } catch (\Exception $e) {
                Log::error("Error actualizando edad mascota ID {$mascota->id}: " . $e->getMessage());
                $this->error("Error en mascota ID {$mascota->id}: " . $e->getMessage());
                $this->error("Detalles: " . $e->getFile() . ":" . $e->getLine());
            }
        }

        $mensaje = "Job completado. {$contador} mascotas procesadas.";
        $this->info($mensaje);
        Log::info($mensaje);

        return Command::SUCCESS;
    }
}