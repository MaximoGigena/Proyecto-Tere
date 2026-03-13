<?php
// app/Console/Commands/EnviarRecordatoriosVacunas.php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Models\ProcedimientosMedicos\Vacuna;
use App\Models\Mascota;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EnviarRecordatoriosVacunas extends Command
{
    protected $signature = 'notificaciones:recordatorios-vacunas';
    protected $description = 'Envía notificaciones recordatorias para vacunas próximas a vencer';

    public function handle()
    {
        $this->info('Iniciando envío de recordatorios de vacunas...');
        
        // Configuración: días de anticipación para enviar el recordatorio
        $diasAnticipacion = 7; // Puedes hacerlo configurable
        
        // Buscar vacunas activas cuya próxima dosis esté dentro de X días
        $vacunasProximas = Vacuna::with(['procesoMedico.mascota.usuario'])
            ->whereNotNull('fecha_proxima_dosis')
            ->where('fecha_proxima_dosis', '>=', now())
            ->where('fecha_proxima_dosis', '<=', now()->addDays($diasAnticipacion))
            ->get();
        
        $this->info("Se encontraron {$vacunasProximas->count()} vacunas próximas a vencer.");
        
        $enviadas = 0;
        $errores = 0;
        
        foreach ($vacunasProximas as $vacuna) {
            try {
                // Obtener datos necesarios
                $procesoMedico = $vacuna->procesoMedico;
                
                if (!$procesoMedico || !$procesoMedico->mascota) {
                    $this->warn("Vacuna ID {$vacuna->id}: no tiene proceso médico o mascota asociada.");
                    $errores++;
                    continue;
                }
                
                $mascota = $procesoMedico->mascota;
                $usuario = $mascota->usuario; // Dueño de la mascota
                
                if (!$usuario) {
                    $this->warn("Mascota ID {$mascota->id}: no tiene usuario asociado.");
                    $errores++;
                    continue;
                }
                
                // Verificar si ya se envió una notificación para esta vacuna en los últimos 7 días
                $notificacionReciente = Notificacion::where('usuario_id', $usuario->id)
                    ->where('referencia_tipo', 'vacuna')
                    ->where('referencia_id', $vacuna->id)
                    ->where('tipo', 'PROCEDIMIENTO')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->exists();
                
                if ($notificacionReciente) {
                    $this->line("Vacuna ID {$vacuna->id}: ya se envió un recordatorio reciente. Omitiendo.");
                    continue;
                }
                
                // Calcular días restantes
                $diasRestantes = now()->diffInDays(Carbon::parse($vacuna->fecha_proxima_dosis), false);
                $diasTexto = $diasRestantes == 0 ? 'hoy' : ($diasRestantes == 1 ? 'mañana' : "en {$diasRestantes} días");
                
                // Crear notificación
                Notificacion::create([
                    'usuario_id' => $usuario->id,
                    'tipo' => 'PROCEDIMIENTO',
                    'titulo' => "💉 Recordatorio: Vacuna próxima para {$mascota->nombre}",
                    'contenido' => "Hola {$usuario->name},\n\n" .
                        "Te recordamos que la próxima dosis de la vacuna **{$vacuna->tipo->nombre}** " .
                        "para tu mascota **{$mascota->nombre}** está programada para **{$diasTexto}** " .
                        "({$vacuna->fecha_proxima_dosis->format('d/m/Y')}).\n\n" .
                        "No olvides agendar una cita con tu veterinario de confianza para mantener " .
                        "al día el calendario de vacunación de {$mascota->nombre}.\n\n" .
                        "Puedes ver más detalles en el historial médico de tu mascota.\n\n" .
                        "Saludos,\nEquipo de la plataforma",
                    'origen' => 'SISTEMA',
                    'referencia_tipo' => 'vacuna',
                    'referencia_id' => $vacuna->id,
                    'leida' => false,
                    'activa' => true
                ]);
                
                $enviadas++;
                $this->line("✓ Recordatorio enviado a usuario {$usuario->id} para vacuna {$vacuna->id}");
                
            } catch (\Exception $e) {
                $this->error("Error procesando vacuna ID {$vacuna->id}: " . $e->getMessage());
                Log::error('Error en comando recordatorios vacunas', [
                    'vacuna_id' => $vacuna->id,
                    'error' => $e->getMessage()
                ]);
                $errores++;
            }
        }
        
        $this->info("Proceso completado. Enviados: {$enviadas}, Errores: {$errores}");
        
        return Command::SUCCESS;
    }
}