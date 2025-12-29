<?php

namespace App\Services;

use App\Models\ContactoUsuario;
use App\Models\Mascota;
use App\Models\ProcedimientosMedicos\Vacuna;
use App\Models\ProcedimientosMedicos\Desparasitacion;
use App\Models\ProcedimientosMedicos\Revision;
use App\Models\ProcedimientosMedicos\Alergia;
use App\Mail\CertificadoRevisionMail;
use App\Mail\CertificadoVacunaMail;
use App\Mail\CertificadoDesparasitacionMail;
use App\Mail\CertificadoAlergiaMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf;

class EnvioDocumentosService
{
    protected $telegramService;
    protected $pdfService;

    public function __construct(TelegramService $telegramService, PdfService $pdfService)
    {
        $this->telegramService = $telegramService;
        $this->pdfService = $pdfService;
    }

    public function enviarCertificadoVacuna(Vacuna $vacuna, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $vacuna->procesoMedico->centroVeterinario;

            // Generar PDF
            $pdfInfo = $this->pdfService->generarCertificadoVacuna(
                $vacuna, 
                $mascota, 
                $tutor, 
                $centroVeterinario
            );

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarVacunaPorEmail($tutor, $pdfInfo, $mascota, $vacuna);
                
                case 'telegram':
                    return $this->enviarVacunaPorTelegram($tutor, $pdfInfo, $mascota);
                
                case 'whatsapp':
                    return $this->enviarVacunaPorWhatsapp($tutor, $pdfInfo, $mascota);
                
                default:
                    throw new \Exception('Medio de envío no soportado: ' . $medioEnvio);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando certificado de vacuna: ' . $e->getMessage());
            throw $e;
        }
    }

    private function enviarVacunaPorEmail(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Vacuna $vacuna)
    {
        try {
            // Obtener email del tutor
            $emailTutor = $tutor->email;
            
            if (!$emailTutor) {
                throw new \Exception("El tutor no tiene email registrado");
            }

            Log::info('📧 Preparando envío de email', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'vacuna_id' => $vacuna->id,
                'pdf_path' => $pdfInfo['full_path'] ?? 'No disponible'
            ]);

            // Enviar email - pasar también la vacuna
            Mail::to($emailTutor)
                ->send(new CertificadoVacunaMail($pdfInfo['full_path'], $mascota, $vacuna));

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            Log::info('✅ Email enviado exitosamente', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'vacuna_id' => $vacuna->id
            ]);

            return ['success' => true, 'message' => 'Certificado enviado por email'];

        } catch (\Exception $e) {
            // Limpiar archivo temporal en caso de error
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando email: ' . $e->getMessage());
            throw new \Exception("Error enviando email: " . $e->getMessage());
        }
    }

    private function enviarVacunaPorTelegram(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota)
    {
        if (!$tutor->telegram_chat_id) {
            throw new \Exception('El tutor no tiene Telegram configurado');
        }

        $caption = "🏥 Certificado de Vacunación\n\n" .
                  "Mascota: {$mascota->nombre}\n" .
                  "Fecha: " . now()->format('d/m/Y') . "\n\n" .
                  "Documento generado automáticamente por el Sistema Veterinario TERE";

        $result = $this->telegramService->sendDocument(
            $tutor->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Certificado enviado por Telegram'];
    }

    private function enviarVacunaPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota)
    {
        Log::info('Envío por WhatsApp preparado', [
            'telefono' => $tutor->telefono,
            'mascota' => $mascota->nombre
        ]);

        return ['success' => true, 'message' => 'Envío por WhatsApp configurado (implementar servicio)'];
    }

    public function enviarCertificadoDesparasitacion(Desparasitacion $desparasitacion, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $desparasitacion->procesoMedico->centroVeterinario;

            // Generar PDF
            $pdfInfo = $this->generarPdfDesparasitacion($desparasitacion, $mascota, $tutor, $centroVeterinario);

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarDesparasitacionPorEmail($tutor, $pdfInfo, $mascota, $desparasitacion);
                
                case 'telegram':
                    return $this->enviarDesparasitacionPorTelegram($tutor, $pdfInfo, $mascota);
                
                case 'whatsapp':
                    return $this->enviarDesparasitacionPorWhatsapp($tutor, $pdfInfo, $mascota);
                
                default:
                    throw new \Exception('Medio de envío no soportado: ' . $medioEnvio);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando certificado de desparasitación: ' . $e->getMessage());
            throw $e;
        }
    }

    private function enviarDesparasitacionPorEmail(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Desparasitacion $desparasitacion)
    {
        try {
            // Obtener email del tutor
            $emailTutor = $tutor->email;
            
            if (!$emailTutor) {
                throw new \Exception("El tutor no tiene email registrado");
            }

            Log::info('📧 Preparando envío de email - Desparasitación', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'desparasitacion_id' => $desparasitacion->id,
                'pdf_path' => $pdfInfo['full_path'] ?? 'No disponible'
            ]);

            // Enviar email
            Mail::to($emailTutor)
                ->send(new CertificadoDesparasitacionMail($pdfInfo['full_path'], $mascota, $desparasitacion));

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            Log::info('✅ Email de desparasitación enviado exitosamente', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'desparasitacion_id' => $desparasitacion->id
            ]);

            return ['success' => true, 'message' => 'Certificado de desparasitación enviado por email'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando email de desparasitación: ' . $e->getMessage());
            throw new \Exception("Error enviando email: " . $e->getMessage());
        }
    }

    private function enviarDesparasitacionPorTelegram(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota)
    {
        if (!$tutor->telegram_chat_id) {
            throw new \Exception('El tutor no tiene Telegram configurado');
        }

        $caption = "💊 Certificado de Desparasitación\n\n" .
                  "Mascota: {$mascota->nombre}\n" .
                  "Fecha: " . now()->format('d/m/Y') . "\n\n" .
                  "Documento generado automáticamente por el Sistema Veterinario TERE";

        $result = $this->telegramService->sendDocument(
            $tutor->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Certificado de desparasitación enviado por Telegram'];
    }

    private function enviarDesparasitacionPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota)
    {
        Log::info('Envío por WhatsApp preparado - Desparasitación', [
            'telefono' => $tutor->telefono,
            'mascota' => $mascota->nombre
        ]);

        return ['success' => true, 'message' => 'Envío por WhatsApp configurado (implementar servicio)'];
    }

    private function generarPdfDesparasitacion(Desparasitacion $desparasitacion, Mascota $mascota, ContactoUsuario $tutor, $centroVeterinario): array
    {
        $html = view('pdf.certificado-desparasitacion', [
            'desparasitacion' => $desparasitacion,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y')
        ])->render();

        $pdf = PDF::loadHTML($html)->setPaper('a4', 'portrait');

        $fileName = 'certificado_desparasitacion_' . uniqid() . '.pdf';
        $fullPath = storage_path('app/temp/' . $fileName);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }

        file_put_contents($fullPath, $pdf->output());

        return [
            'file_name' => $fileName,
            'full_path' => $fullPath
        ];
    }

    public function enviarInformeRevision(Revision $revision, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $revision->procesoMedico->centroVeterinario;

            // Generar PDF
            $pdfInfo = $this->generarPdfRevision($revision, $mascota, $tutor, $centroVeterinario);

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarRevisionPorEmail($tutor, $pdfInfo, $mascota, $revision);
                
                case 'telegram':
                    return $this->enviarRevisionPorTelegram($tutor, $pdfInfo, $mascota, $revision);
                
                case 'whatsapp':
                    return $this->enviarRevisionPorWhatsapp($tutor, $pdfInfo, $mascota);
                
                default:
                    throw new \Exception('Medio de envío no soportado: ' . $medioEnvio);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando informe de revisión: ' . $e->getMessage());
            throw $e;
        }
    }

    private function enviarRevisionPorEmail(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Revision $revision)
    {
        try {
            // Obtener email del tutor
            $emailTutor = $tutor->email;
            
            if (!$emailTutor) {
                throw new \Exception("El tutor no tiene email registrado");
            }

            Log::info('📧 Preparando envío de email - Revisión Médica', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'revision_id' => $revision->id,
                'pdf_path' => $pdfInfo['full_path'] ?? 'No disponible'
            ]);

            // Enviar email - pasar también la revisión
            Mail::to($emailTutor)
                ->send(new CertificadoRevisionMail($pdfInfo['full_path'], $mascota, $revision));

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            Log::info('✅ Email de revisión enviado exitosamente', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'revision_id' => $revision->id
            ]);

            return ['success' => true, 'message' => 'Informe de revisión enviado por email'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando email de revisión: ' . $e->getMessage());
            throw new \Exception("Error enviando email: " . $e->getMessage());
        }
    }

    private function enviarRevisionPorTelegram(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Revision $revision)
    {
        if (!$tutor->telegram_chat_id) {
            throw new \Exception('El tutor no tiene Telegram configurado');
        }

        $tipoRevision = $revision->tipoRevision->nombre ?? 'Revisión Médica';
        $urgenciaLabels = [
            'rutinaria' => 'Rutinaria',
            'preventiva' => 'Preventiva', 
            'urgencia' => 'Urgencia',
            'emergencia' => 'Emergencia'
        ];
        $urgencia = $urgenciaLabels[$revision->nivel_urgencia] ?? $revision->nivel_urgencia;

        $caption = "🏥 **INFORME DE REVISIÓN MÉDICA**\n\n" .
                  "📋 **Tipo:** $tipoRevision\n" .
                  "⚡ **Urgencia:** $urgencia\n" .
                  "🐾 **Mascota:** {$mascota->nombre}\n" .
                  "📅 **Fecha:** " . $revision->fecha_revision->format('d/m/Y H:i') . "\n\n" .
                  "📝 **Informe generado automáticamente por el Sistema Veterinario TERE**";

        $result = $this->telegramService->sendDocument(
            $tutor->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Informe de revisión enviado por Telegram'];
    }

    private function enviarRevisionPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota)
    {
        Log::info('Envío por WhatsApp preparado - Revisión Médica', [
            'telefono' => $tutor->telefono,
            'mascota' => $mascota->nombre
        ]);

        return ['success' => true, 'message' => 'Envío por WhatsApp configurado (implementar servicio)'];
    }

    private function generarPdfRevision(Revision $revision, Mascota $mascota, ContactoUsuario $tutor, $centroVeterinario = null): array
    {
        if (method_exists($this->pdfService, 'generarInformeRevision')) {
            return $this->pdfService->generarInformeRevision($revision, $mascota, $tutor, $centroVeterinario);
        }
        
        $html = view('pdf.informe-revision', [
            'revision' => $revision,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y H:i')
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $fileName = 'informe_revision_' . $revision->id . '_' . uniqid() . '.pdf';
        $fullPath = storage_path('app/temp/' . $fileName);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }

        file_put_contents($fullPath, $pdf->output());

        return [
            'file_name' => $fileName,
            'full_path' => $fullPath
        ];
    }

    public function enviarRegistroAlergia(Alergia $alergia, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $alergia->procesoMedico->centroVeterinario ?? null;

            // Generar PDF
            $pdfInfo = $this->pdfService->generarRegistroAlergia(
                $alergia, 
                $mascota, 
                $tutor, 
                $centroVeterinario
            );

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarAlergiaPorEmail($tutor, $pdfInfo, $mascota, $alergia);
                
                case 'telegram':
                    return $this->enviarAlergiaPorTelegram($tutor, $pdfInfo, $mascota, $alergia);
                
                case 'whatsapp':
                    return $this->enviarAlergiaPorWhatsapp($tutor, $pdfInfo, $mascota);
                
                default:
                    throw new \Exception('Medio de envío no soportado: ' . $medioEnvio);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando registro de alergia: ' . $e->getMessage());
            throw $e;
        }
    }

    private function enviarAlergiaPorEmail(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Alergia $alergia)
    {
        try {
            // Obtener email del tutor
            $emailTutor = $tutor->email;
            
            if (!$emailTutor) {
                throw new \Exception("El tutor no tiene email registrado");
            }

            Log::info('📧 Preparando envío de email - Registro de Alergia', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'alergia_id' => $alergia->id,
                'pdf_path' => $pdfInfo['full_path'] ?? 'No disponible'
            ]);

            // Enviar email
            Mail::to($emailTutor)
                ->send(new CertificadoAlergiaMail($pdfInfo['full_path'], $mascota, $alergia));

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            Log::info('✅ Email de alergia enviado exitosamente', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'alergia_id' => $alergia->id
            ]);

            return ['success' => true, 'message' => 'Registro de alergia enviado por email'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando email de alergia: ' . $e->getMessage());
            throw new \Exception("Error enviando email: " . $e->getMessage());
        }
    }

    private function enviarAlergiaPorTelegram(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Alergia $alergia)
    {
        if (!$tutor->telegram_chat_id) {
            throw new \Exception('El tutor no tiene Telegram configurado');
        }

        $gravedadLabels = [
            'leve' => 'Leve',
            'moderada' => 'Moderada', 
            'grave' => 'Grave'
        ];
        $gravedad = $gravedadLabels[$alergia->gravedad] ?? $alergia->gravedad;
        
        $estadoLabels = [
            'activa' => 'Activa',
            'superada' => 'Superada', 
            'seguimiento' => 'Bajo seguimiento'
        ];
        $estado = $estadoLabels[$alergia->estado] ?? $alergia->estado;
        
        // CORREGIR: Mover la lógica del operador ?? fuera de la interpolación
        $nombreAlergia = $alergia->tipoAlergia->nombre ?? 'No especificada';

        $caption = "⚠️ **REGISTRO DE ALERGIA/SENSIBILIDAD**\n\n" .
                "🐾 **Mascota:** {$mascota->nombre}\n" .
                "🤧 **Alergia:** {$nombreAlergia}\n" .  // Usar la variable aquí
                "📊 **Gravedad:** $gravedad\n" .
                "📈 **Estado:** $estado\n" .
                "📅 **Fecha de detección:** " . $alergia->fecha_deteccion->format('d/m/Y') . "\n" .
                "🔄 **Reacción común:** {$alergia->reaccion_comun}\n\n" .
                "📝 **Documento generado automáticamente por el Sistema Veterinario TERE**";

        $result = $this->telegramService->sendDocument(
            $tutor->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Registro de alergia enviado por Telegram'];
    }

    private function enviarAlergiaPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota)
    {
        Log::info('Envío por WhatsApp preparado - Registro de Alergia', [
            'telefono' => $tutor->telefono,
            'mascota' => $mascota->nombre
        ]);

        return ['success' => true, 'message' => 'Envío por WhatsApp configurado (implementar servicio)'];
    }

    private function limpiarArchivoTemporal(string $filePath)
    {
        try {
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info('🗑️ Archivo temporal eliminado: ' . $filePath);
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo eliminar archivo temporal: ' . $filePath);
        }
    }
}