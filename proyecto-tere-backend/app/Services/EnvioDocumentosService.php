<?php

namespace App\Services;

use App\Models\ContactoUsuario;
use App\Models\Mascota;
use App\Models\ProcedimientosMedicos\Vacuna;
use App\Models\ProcedimientosMedicos\Desparasitacion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\CertificadoVacunaMail;
use App\Mail\CertificadoDesparasitacionMail;

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

    /**
     * Enviar certificado de vacuna por Email
     */
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
        // Aquí implementarías el envío por WhatsApp
        // Por ahora solo log
        Log::info('Envío por WhatsApp preparado', [
            'telefono' => $tutor->telefono,
            'mascota' => $mascota->nombre
        ]);

        return ['success' => true, 'message' => 'Envío por WhatsApp configurado (implementar servicio)'];
    }

     /**
     * Enviar certificado de desparasitación
     */
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

    /**
     * Enviar certificado de desparasitación por Email
     */
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
            // Limpiar archivo temporal en caso de error
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
        // Aquí implementarías el envío por WhatsApp
        Log::info('Envío por WhatsApp preparado - Desparasitación', [
            'telefono' => $tutor->telefono,
            'mascota' => $mascota->nombre
        ]);

        return ['success' => true, 'message' => 'Envío por WhatsApp configurado (implementar servicio)'];
    }

    /**
     * Generar PDF para desparasitación
     */
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

    /**
     * Limpiar archivo temporal
     */
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