<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sancion;
use Illuminate\Support\Facades\Log;

class VerificarExpiracionSanciones extends Command
{
    protected $signature = 'sanciones:verificar-expiracion';
    protected $description = 'Verifica y actualiza el estado de sanciones expiradas';

    public function handle()
    {
        $sanciones = Sancion::where('estado', 'ACTIVA')
            ->whereNotNull('fecha_fin')
            ->where('fecha_fin', '<', now())
            ->get();

        foreach ($sanciones as $sancion) {
            $sancion->verificarExpiracion();
            $this->info("Sanción {$sancion->id} expirada automáticamente");
        }

        Log::info('Verificación de expiración de sanciones completada', [
            'sanciones_expiradas' => $sanciones->count()
        ]);

        return Command::SUCCESS;
    }
}