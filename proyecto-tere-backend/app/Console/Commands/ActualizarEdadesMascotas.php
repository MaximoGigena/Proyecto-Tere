<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mascota;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActualizarEdadesMascotas extends Command
{
    protected $signature = 'mascotas:actualizar-edades';
    protected $description = 'Actualiza las edades calculadas de las mascotas cada hora';

    public function handle()
    {
        $this->info('Iniciando actualización de edades de mascotas...');
        Log::info('Iniciando job diario: ActualizarEdadesMascotas');

        $mascotas = Mascota::withTrashed()->get(); // Incluye mascotas dadas de baja si quieres
        $contador = 0;

        foreach ($mascotas as $mascota) {
            try {
                if ($mascota->fecha_nacimiento) {
                    $edadCalculada = $this->calcularEdadDesdeFecha($mascota->fecha_nacimiento);
                    
                    // ✅ GUARDAR EN LA BD
                    $mascota->edad_actual = $edadCalculada;
                    $mascota->save();
                    
                    $contador++;
                    $this->info("Mascota ID {$mascota->id}: {$edadCalculada}");
            }
            }catch (\Exception $e) {
                Log::error("Error actualizando edad mascota ID {$mascota->id}: " . $e->getMessage());
                $this->error("Error en mascota ID {$mascota->id}: " . $e->getMessage());
            }
        }

        $mensaje = "Job completado. {$contador} mascotas procesadas.";
        $this->info($mensaje);
        Log::info($mensaje);

        return Command::SUCCESS;
    }

    /**
     * Calcula la edad formateada a partir de la fecha de nacimiento
     */
    private function calcularEdadDesdeFecha($fechaNacimiento)
    {
        $nacimiento = Carbon::parse($fechaNacimiento);
        $hoy = Carbon::now();
        
        $diffDias = $hoy->diffInDays($nacimiento);
        
        if ($diffDias < 30) {
            return "{$diffDias} días";
        } else if ($diffDias < 365) {
            $meses = $hoy->diffInMonths($nacimiento);
            return "{$meses} " . ($meses === 1 ? 'mes' : 'meses');
        } else {
            $años = $hoy->diffInYears($nacimiento);
            $mesesRestantes = $hoy->diffInMonths($nacimiento) % 12;
            
            if ($mesesRestantes > 0) {
                return "{$años} " . ($años === 1 ? 'año' : 'años') . " y {$mesesRestantes} " . ($mesesRestantes === 1 ? 'mes' : 'meses');
            }
            
            return "{$años} " . ($años === 1 ? 'año' : 'años');
        }
    }
}
