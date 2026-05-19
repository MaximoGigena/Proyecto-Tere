<?php

namespace App\Services;

use App\Models\ContactoUsuario;
use App\Models\User;
use App\Models\Mascota;
use App\Models\ProcedimientosMedicos\Vacuna;
use App\Models\ProcedimientosMedicos\Desparasitacion;
use App\Models\ProcedimientosMedicos\Revision;
use App\Models\ProcedimientosMedicos\Alergia;
use App\Models\ProcedimientosMedicos\Farmaco;
use App\Models\ProcedimientosMedicos\Diagnostico;
use App\Models\ProcedimientosMedicos\Cirugia;
use App\Models\ProcedimientosMedicos\CuidadoPaliativo;
use App\Mail\CertificadoRevisionMail;
use App\Mail\CertificadoVacunaMail;
use App\Mail\CertificadoDesparasitacionMail;
use App\Mail\CertificadoAlergiaMail;
use App\Mail\CertificadoDiagnosticoMail;
use App\Mail\RecetaFarmacoMail;
use App\Mail\CertificadoPaliativoMail;
use App\Models\ProcedimientosMedicos\Terapia;
use App\Mail\CertificadoTerapiaMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf;

class EnvioDocumentosService
{
    protected $telegramService;
    protected $pdfService;
    protected $whatsAppService;

    public function __construct(
        TelegramService $telegramService, 
        PdfService $pdfService,
        WhatsAppService $whatsAppService  // ← Añadir esto
    ) {
        $this->telegramService = $telegramService;
        $this->pdfService = $pdfService;
        $this->whatsAppService = $whatsAppService; // ← Añadir esto
    }

    public function enviarCertificadoVacuna(Vacuna $vacuna, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            $user = $mascota->usuario->user; // Asumiendo que Mascota tiene relación usuario, y usuario tiene user
        

            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $vacuna->procesoMedico->centroVeterinario;
            
            // ✅ NUEVO: Obtener datos del veterinario
            $veterinario = $vacuna->procesoMedico->veterinario;

            // Generar PDF - PASAR TAMBIÉN EL VETERINARIO
            $pdfInfo = $this->pdfService->generarCertificadoVacuna(
                $vacuna, 
                $mascota, 
                $tutor, 
                $centroVeterinario,
                $veterinario // ← Nuevo parámetro
            );

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarVacunaPorEmail($tutor, $pdfInfo, $mascota, $vacuna);
                
                case 'telegram':
                     return $this->enviarVacunaPorTelegram($user, $pdfInfo, $mascota);
                
                case 'whatsapp':
                    return $this->enviarVacunaPorWhatsapp($tutor, $pdfInfo, $mascota, $vacuna);
                
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

    private function enviarVacunaPorTelegram(User $user, array $pdfInfo, Mascota $mascota)
    {
        if (!$user->telegram_chat_id) {
            throw new \Exception('El tutor no tiene Telegram configurado');
        }

        $caption = "🏥 Certificado de Vacunación\n\n" .
                  "Mascota: {$mascota->nombre}\n" .
                  "Fecha: " . now()->format('d/m/Y') . "\n\n" .
                  "Documento generado automáticamente por el Sistema Veterinario TERE";

        $result = $this->telegramService->sendDocument(
            $user->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Certificado enviado por Telegram'];
    }

    private function enviarVacunaPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Vacuna $vacuna)
    {
        try {
            if (!$tutor->telefono) {
                throw new \Exception('El tutor no tiene número de teléfono registrado para WhatsApp');
            }

            $telefono = $this->limpiarNumeroTelefono($tutor->telefono);
            
            // Usar el método con fallback
            $result = $this->whatsAppService->sendCertificateWithFallback(
                $telefono,
                $pdfInfo['full_path'],
                'CERTIFICADO DE VACUNACIÓN',
                $tutor->nombre ?? 'Tutor',
                $mascota->nombre,
                "certificado_vacuna_{$mascota->id}_{$vacuna->id}.pdf"
            );

            // Limpiar archivo temporal
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info('✅ Vacuna enviada exitosamente', [
                'telefono' => $telefono,
                'mascota' => $mascota->nombre,
                'method' => $result['method'] ?? 'standard'
            ]);

            return ['success' => true, 'message' => 'Certificado enviado por WhatsApp'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando vacuna: ' . $e->getMessage());
            throw $e;
        }
    }

    // Método auxiliar para limpiar teléfono
    private function limpiarNumeroTelefono($telefono)
    {
        // Eliminar +, espacios, guiones, paréntesis
        $limpio = preg_replace('/[^0-9]/', '', $telefono);
        
        // Argentina: convertir +54 9 3758 526513 a 5493758526513
        if (strlen($limpio) === 12 && substr($limpio, 0, 2) === '54') {
            // Ya tiene 54 + 10 dígitos, agregar 9
            return '549' . substr($limpio, 2);
        }
        
        if (strlen($limpio) === 10) {
            // Solo números locales, asumir Argentina
            return '549' . $limpio;
        }
        
        if (strlen($limpio) === 11 && substr($limpio, 0, 1) === '9') {
            // Tiene 9 + 10 dígitos
            return '54' . $limpio;
        }
        
        return $limpio;
    }

    public function enviarCertificadoDesparasitacion(Desparasitacion $desparasitacion, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            $user = $mascota->usuario->user;

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $desparasitacion->procesoMedico->centroVeterinario;

            // Generar PDF
            $pdfInfo = $this->generarPdfDesparasitacion($desparasitacion, $mascota, $tutor, $centroVeterinario);

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarDesparasitacionPorEmail($tutor, $pdfInfo, $mascota, $desparasitacion);
                
                case 'telegram':
                    return $this->enviarDesparasitacionPorTelegram($user, $pdfInfo, $mascota, $desparasitacion);
                
                case 'whatsapp':
                    return $this->enviarDesparasitacionPorWhatsapp($tutor, $pdfInfo, $mascota, $desparasitacion);
                
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

    private function enviarDesparasitacionPorTelegram(User $user, array $pdfInfo, Mascota $mascota)
    {
        if (!$user->telegram_chat_id) {
            throw new \Exception('El usuario no tiene Telegram configurado');
        }

        $caption = "💊 Certificado de Desparasitación\n\n" .
                  "Mascota: {$mascota->nombre}\n" .
                  "Fecha: " . now()->format('d/m/Y') . "\n\n" .
                  "Documento generado automáticamente por el Sistema Veterinario TERE";

        $result = $this->telegramService->sendDocument(
            $user->telegram_chat_id,
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
        try {
            if (!$tutor->telefono) {
                throw new \Exception('El tutor no tiene número de teléfono registrado para WhatsApp');
            }

            $telefono = $this->limpiarNumeroTelefono($tutor->telefono);
            
            Log::info('📱 Enviando WhatsApp - Desparasitación', [
                'original' => $tutor->telefono,
                'limpio' => $telefono,
                'mascota' => $mascota->nombre,
                'archivo' => $pdfInfo['full_path']
            ]);

            // Usando el método con plantilla de WhatsApp
            $result = $this->whatsAppService->sendCertificateWithFallback(
                $telefono,
                $pdfInfo['full_path'],
                'CERTIFICADO DE DESPARASITACIÓN',
                $tutor->nombre ?? 'Tutor',
                $mascota->nombre,
                "certificado_desparasitacion_{$mascota->id}.pdf"
            );

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info('✅ WhatsApp de desparasitación enviado exitosamente', [
                'telefono' => $telefono,
                'mascota' => $mascota->nombre,
                'method' => $result['method'] ?? 'plantilla_whatsapp'
            ]);

            return ['success' => true, 'message' => 'Certificado de desparasitación enviado por WhatsApp'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando WhatsApp de desparasitación: ' . $e->getMessage(), [
                'mascota_id' => $mascota->id ?? null
            ]);
            throw $e;
        }
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

            $user = $mascota->usuario->user; 

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $revision->procesoMedico->centroVeterinario;

            // Generar PDF
            $pdfInfo = $this->generarPdfRevision($revision, $mascota, $tutor, $centroVeterinario);

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarRevisionPorEmail($tutor, $pdfInfo, $mascota, $revision);
                
                case 'telegram':
                    return $this->enviarRevisionPorTelegram($user, $pdfInfo, $mascota, $revision);
                
                case 'whatsapp':
                    return $this->enviarRevisionPorWhatsapp($tutor, $pdfInfo, $mascota, $revision);
                
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

    private function enviarRevisionPorTelegram(User $user, array $pdfInfo, Mascota $mascota, Revision $revision)
    {
        if (!$user->telegram_chat_id) {
            throw new \Exception('El usuario no tiene Telegram configurado');
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
            $user->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Informe de revisión enviado por Telegram'];
    }

    private function enviarRevisionPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Revision $revision = null)
    {
        try {
            if (!$tutor->telefono) {
                throw new \Exception('El tutor no tiene número de teléfono registrado para WhatsApp');
            }

            $telefono = $this->limpiarNumeroTelefono($tutor->telefono);
            
            Log::info('📱 Enviando WhatsApp - Revisión Médica', [
                'original' => $tutor->telefono,
                'limpio' => $telefono,
                'mascota' => $mascota->nombre,
                'revision_id' => $revision->id ?? null,
                'archivo' => $pdfInfo['full_path']
            ]);

            // Usando el método con plantilla de WhatsApp
            $result = $this->whatsAppService->sendCertificateWithFallback(
                $telefono,
                $pdfInfo['full_path'],
                'INFORME DE REVISIÓN MÉDICA',
                $tutor->nombre ?? 'Tutor',
                $mascota->nombre,
                "informe_revision_{$mascota->id}" . ($revision ? "_{$revision->id}" : "") . ".pdf"
            );

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info('✅ WhatsApp de revisión enviado exitosamente', [
                'telefono' => $telefono,
                'mascota' => $mascota->nombre,
                'revision_id' => $revision->id ?? null,
                'method' => $result['method'] ?? 'plantilla_whatsapp'
            ]);

            return ['success' => true, 'message' => 'Informe de revisión enviado por WhatsApp'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando WhatsApp de revisión: ' . $e->getMessage(), [
                'revision_id' => $revision->id ?? null,
                'mascota_id' => $mascota->id ?? null
            ]);
            throw $e;
        }
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

            $user = $mascota->usuario->user; 

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
                    return $this->enviarAlergiaPorTelegram($user, $pdfInfo, $mascota, $alergia);
                
                case 'whatsapp':
                    return $this->enviarAlergiaPorWhatsapp($tutor, $pdfInfo, $mascota, $alergia);
                
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

    private function enviarAlergiaPorTelegram(User $user, array $pdfInfo, Mascota $mascota, Alergia $alergia)
    {
        if (!$user->telegram_chat_id) {
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
            $user->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Registro de alergia enviado por Telegram'];
    }

    private function enviarAlergiaPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Alergia $alergia)
    {
        try {
            if (!$tutor->telefono) {
                throw new \Exception('El tutor no tiene número de teléfono registrado para WhatsApp');
            }

            $telefono = $this->limpiarNumeroTelefono($tutor->telefono);
            
            Log::info('📱 Enviando WhatsApp - Alergia', [
                'original' => $tutor->telefono,
                'limpio' => $telefono,
                'mascota' => $mascota->nombre,
                'alergia_id' => $alergia->id,
                'archivo' => $pdfInfo['full_path']
            ]);

            // Usando el método con plantilla de WhatsApp
            $result = $this->whatsAppService->sendCertificateWithFallback(
                $telefono,
                $pdfInfo['full_path'],
                'REGISTRO DE ALERGIA/SENSIBILIDAD',
                $tutor->nombre ?? 'Tutor',
                $mascota->nombre,
                "registro_alergia_{$mascota->id}_{$alergia->id}.pdf"
            );

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info('✅ WhatsApp de alergia enviado exitosamente', [
                'telefono' => $telefono,
                'mascota' => $mascota->nombre,
                'alergia_id' => $alergia->id,
                'method' => $result['method'] ?? 'plantilla_whatsapp'
            ]);

            return ['success' => true, 'message' => 'Registro de alergia enviado por WhatsApp'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando WhatsApp de alergia: ' . $e->getMessage(), [
                'alergia_id' => $alergia->id ?? null,
                'mascota_id' => $mascota->id ?? null
            ]);
            throw $e;
        }
    }

    // Agregar este método a la clase EnvioDocumentosService
    public function enviarCertificadoDiagnostico(Diagnostico $diagnostico, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $diagnostico->procesoMedico->centroVeterinario ?? null;
                
            $user = $mascota->usuario->user; 

            // Generar PDF
            $pdfInfo = $this->generarPdfDiagnostico($diagnostico, $mascota, $tutor, $centroVeterinario);

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarDiagnosticoPorEmail($tutor, $pdfInfo, $mascota, $diagnostico);
                
                case 'telegram':
                    return $this->enviarDiagnosticoPorTelegram($user, $pdfInfo, $mascota, $diagnostico);
                
                case 'whatsapp':
                    return $this->enviarDiagnosticoPorWhatsapp($tutor, $pdfInfo, $mascota, $diagnostico);
                
                default:
                    throw new \Exception('Medio de envío no soportado: ' . $medioEnvio);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando certificado de diagnóstico: ' . $e->getMessage());
            throw $e;
        }
    }

    private function enviarDiagnosticoPorEmail(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Diagnostico $diagnostico)
    {
        try {
            // Obtener email del tutor
            $emailTutor = $tutor->email;
            
            if (!$emailTutor) {
                throw new \Exception("El tutor no tiene email registrado");
            }

            Log::info('📧 Preparando envío de email - Diagnóstico', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'diagnostico_id' => $diagnostico->id,
                'pdf_path' => $pdfInfo['full_path'] ?? 'No disponible'
            ]);

            // Enviar email
            Mail::to($emailTutor)
                ->send(new CertificadoDiagnosticoMail($pdfInfo['full_path'], $mascota, $diagnostico));

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            Log::info('✅ Email de diagnóstico enviado exitosamente', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'diagnostico_id' => $diagnostico->id
            ]);

            return ['success' => true, 'message' => 'Certificado de diagnóstico enviado por email'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando email de diagnóstico: ' . $e->getMessage());
            throw new \Exception("Error enviando email: " . $e->getMessage());
        }
    }

    private function enviarDiagnosticoPorTelegram(User $user, array $pdfInfo, Mascota $mascota, Diagnostico $diagnostico)
    {
        if (!$user->telegram_chat_id) {
            throw new \Exception('El usuario no tiene Telegram configurado');
        }

        $estadoLabels = [
            'activo' => 'Activo',
            'resuelto' => 'Resuelto',
            'cronico' => 'Crónico',
            'seguimiento' => 'En seguimiento',
            'sospecha' => 'Sospecha'
        ];
        $estado = $estadoLabels[$diagnostico->estado] ?? $diagnostico->estado;

        $caption = "🏥 **DIAGNÓSTICO MÉDICO**\n\n" .
                "🐾 **Mascota:** {$mascota->nombre}\n" .
                "📋 **Diagnóstico:** {$diagnostico->nombre}\n" .
                "📊 **Estado:** $estado\n" .
                "📅 **Fecha:** " . $diagnostico->fecha_diagnostico->format('d/m/Y') . "\n\n" .
                "📝 **Documento generado automáticamente por el Sistema Veterinario TERE**";

        $result = $this->telegramService->sendDocument(
            $user->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Certificado de diagnóstico enviado por Telegram'];
    }

    private function enviarDiagnosticoPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota)
    {
        try {
            if (!$tutor->telefono) {
                throw new \Exception('El tutor no tiene número de teléfono registrado para WhatsApp');
            }

            $telefono = $this->limpiarNumeroTelefono($tutor->telefono);
            
            Log::info('📱 Enviando WhatsApp - Diagnóstico', [
                'original' => $tutor->telefono,
                'limpio' => $telefono,
                'mascota' => $mascota->nombre,
                'archivo' => $pdfInfo['full_path']
            ]);

            // Usando el método con plantilla de WhatsApp (igual que vacunas)
            $result = $this->whatsAppService->sendCertificateWithFallback(
                $telefono,
                $pdfInfo['full_path'],
                'INFORME DE DIAGNÓSTICO',
                $tutor->nombre ?? 'Tutor',
                $mascota->nombre,
                "informe_diagnostico_{$mascota->id}.pdf"
            );

            // Limpiar archivo temporal
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info('✅ Diagnóstico enviado exitosamente', [
                'telefono' => $telefono,
                'mascota' => $mascota->nombre,
                'method' => $result['method'] ?? 'plantilla_whatsapp'
            ]);

            return ['success' => true, 'message' => 'Informe de diagnóstico enviado por WhatsApp'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando diagnóstico: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generarPdfDiagnostico(Diagnostico $diagnostico, Mascota $mascota, ContactoUsuario $tutor, $centroVeterinario = null): array
    {
        // Usar el método del PdfService si existe
        if (method_exists($this->pdfService, 'generarCertificadoDiagnostico')) {
            return $this->pdfService->generarCertificadoDiagnostico($diagnostico, $mascota, $tutor, $centroVeterinario);
        }
        
        $html = view('pdf.certificado-diagnostico', [
            'diagnostico' => $diagnostico,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y'),
            'estadoLabels' => [
                'activo' => 'Activo',
                'resuelto' => 'Resuelto',
                'cronico' => 'Crónico',
                'seguimiento' => 'En seguimiento',
                'sospecha' => 'Sospecha'
            ]
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $fileName = 'diagnostico_' . $diagnostico->id . '_' . uniqid() . '.pdf';
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
     * Enviar receta de fármaco
     */
    public function enviarRecetaFarmaco(Farmaco $farmaco, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $farmaco->procesoMedico->centroVeterinario ?? null;

            $user = $mascota->usuario->user; 

            // Generar PDF
            $pdfInfo = $this->pdfService->generarRecetaFarmaco(
                $farmaco, 
                $mascota, 
                $tutor, 
                $centroVeterinario
            );

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarFarmacoPorEmail($tutor, $pdfInfo, $mascota, $farmaco);
                
                case 'telegram':
                    return $this->enviarFarmacoPorTelegram($user, $pdfInfo, $mascota, $farmaco);
                
                case 'whatsapp':
                    return $this->enviarFarmacoPorWhatsapp($tutor, $pdfInfo, $mascota, $farmaco);
                
                default:
                    throw new \Exception('Medio de envío no soportado: ' . $medioEnvio);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando receta de fármaco: ' . $e->getMessage());
            throw $e;
        }
    }

    private function enviarFarmacoPorEmail(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Farmaco $farmaco)
    {
        try {
            // Obtener email del tutor
            $emailTutor = $tutor->email;
            
            if (!$emailTutor) {
                throw new \Exception("El tutor no tiene email registrado");
            }

            Log::info('📧 Preparando envío de email - Receta de Fármaco', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'farmaco_id' => $farmaco->id,
                'pdf_path' => $pdfInfo['full_path'] ?? 'No disponible'
            ]);

            // Enviar email
            Mail::to($emailTutor)
                ->send(new RecetaFarmacoMail($pdfInfo['full_path'], $mascota, $farmaco));

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            Log::info('✅ Email de receta de fármaco enviado exitosamente', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'farmaco_id' => $farmaco->id
            ]);

            return ['success' => true, 'message' => 'Receta de fármaco enviada por email'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando email de receta de fármaco: ' . $e->getMessage());
            throw new \Exception("Error enviando email: " . $e->getMessage());
        }
    }

    private function enviarFarmacoPorTelegram(User $user, array $pdfInfo, Mascota $mascota, Farmaco $farmaco)
    {
        if (!$user->telegram_chat_id) {
            throw new \Exception('El usuario no tiene Telegram configurado');
        }

        $caption = "💊 **RECETA MÉDICA - TRATAMIENTO FARMACOLÓGICO**\n\n" .
                  "🐾 **Mascota:** {$mascota->nombre}\n" .
                  "💊 **Fármaco:** " . ($farmaco->tipoFarmaco->nombre ?? 'No especificado') . "\n" .
                  "📅 **Fecha de administración:** " . $farmaco->fecha_administracion->format('d/m/Y H:i') . "\n" .
                  "🔄 **Frecuencia:** {$farmaco->frecuencia}\n" .
                  "⏱️ **Duración:** {$farmaco->duracion_tratamiento}\n" .
                  "📏 **Dosis:** {$farmaco->dosis} {$farmaco->unidad_dosis}\n\n" .
                  ($farmaco->proxima_dosis ? "📅 **Próxima dosis:** " . $farmaco->proxima_dosis->format('d/m/Y H:i') . "\n\n" : "") .
                  "📝 **Receta generada automáticamente por el Sistema Veterinario TERE**\n" .
                  "⚠️ **Consulte con su veterinario antes de cualquier modificación**";

        $result = $this->telegramService->sendDocument(
            $user->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Receta de fármaco enviada por Telegram'];
    }

    private function enviarFarmacoPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Farmaco $farmaco)
    {
        try {
            if (!$tutor->telefono) {
                throw new \Exception('El tutor no tiene número de teléfono registrado para WhatsApp');
            }

            $telefono = $this->limpiarNumeroTelefono($tutor->telefono);
            
            Log::info('📱 Enviando WhatsApp - Receta de Fármaco', [
                'original' => $tutor->telefono,
                'limpio' => $telefono,
                'mascota' => $mascota->nombre,
                'farmaco_id' => $farmaco->id,
                'archivo' => $pdfInfo['full_path']
            ]);

            // Usando el método con plantilla de WhatsApp
            $result = $this->whatsAppService->sendCertificateWithFallback(
                $telefono,
                $pdfInfo['full_path'],
                'RECETA DE FÁRMACO',
                $tutor->nombre ?? 'Tutor',
                $mascota->nombre,
                "receta_farmaco_{$mascota->id}_{$farmaco->id}.pdf"
            );

            // Limpiar archivo temporal
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info('✅ Receta de fármaco enviada exitosamente', [
                'telefono' => $telefono,
                'mascota' => $mascota->nombre,
                'farmaco_id' => $farmaco->id,
                'method' => $result['method'] ?? 'plantilla_whatsapp'
            ]);

            return ['success' => true, 'message' => 'Receta de fármaco enviada por WhatsApp'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando receta de fármaco: ' . $e->getMessage(), [
                'farmaco_id' => $farmaco->id ?? null,
                'mascota_id' => $mascota->id ?? null
            ]);
            throw $e;
        }
    }   

    // Agrega este método a la clase:
    public function enviarCertificadoTerapia(Terapia $terapia, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $terapia->procesoMedico->centroVeterinario ?? null;

            $user = $mascota->usuario->user; 

            // Generar PDF
            $pdfInfo = $this->generarPdfTerapia($terapia, $mascota, $tutor, $centroVeterinario);

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarTerapiaPorEmail($tutor, $pdfInfo, $mascota, $terapia);
                
                case 'telegram':
                    return $this->enviarTerapiaPorTelegram($user, $pdfInfo, $mascota, $terapia);
                
                case 'whatsapp':
                    return $this->enviarTerapiaPorWhatsapp($tutor, $pdfInfo, $mascota, $terapia);
                
                default:
                    throw new \Exception('Medio de envío no soportado: ' . $medioEnvio);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando certificado de terapia: ' . $e->getMessage());
            throw $e;
        }
    }

    private function enviarTerapiaPorEmail(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, Terapia $terapia)
    {
        try {
            // Obtener email del tutor
            $emailTutor = $tutor->email;
            
            if (!$emailTutor) {
                throw new \Exception("El tutor no tiene email registrado");
            }

            Log::info('📧 Preparando envío de email - Terapia', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'terapia_id' => $terapia->id,
                'pdf_path' => $pdfInfo['full_path'] ?? 'No disponible'
            ]);

            // Enviar email
            Mail::to($emailTutor)
                ->send(new CertificadoTerapiaMail($pdfInfo['full_path'], $mascota, $terapia));

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            Log::info('✅ Email de terapia enviado exitosamente', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'terapia_id' => $terapia->id
            ]);

            return ['success' => true, 'message' => 'Certificado de terapia enviado por email'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando email de terapia: ' . $e->getMessage());
            throw new \Exception("Error enviando email: " . $e->getMessage());
        }
    }

    private function enviarTerapiaPorTelegram(User $user, array $pdfInfo, Mascota $mascota, Terapia $terapia)
    {
        if (!$user->telegram_chat_id) {
            throw new \Exception('El usuario no tiene Telegram configurado');
        }

        $evolucionLabels = [
            'mejoria' => 'Mejoría',
            'estable' => 'Estable',
            'empeoramiento' => 'Empeoramiento'
        ];
        $evolucion = $evolucionLabels[$terapia->evolucion] ?? $terapia->evolucion ?? 'No especificada';

        $caption = "🏥 **CERTIFICADO DE TERAPIA**\n\n" .
                "🐾 **Mascota:** {$mascota->nombre}\n" .
                "💉 **Tipo:** " . ($terapia->tipoTerapia->nombre ?? 'No especificado') . "\n" .
                "📅 **Inicio:** " . $terapia->fecha_inicio->format('d/m/Y') . "\n" .
                "🔄 **Frecuencia:** " . ucfirst($terapia->frecuencia) . "\n" .
                "⏱️ **Duración:** {$terapia->duracion_tratamiento}\n" .
                "📊 **Evolución:** $evolucion\n" .
                "📈 **Estado:** " . ($terapia->estaActiva() ? 'Activa' : 'Finalizada') . "\n\n" .
                "📝 **Documento generado automáticamente por el Sistema Veterinario TERE**";

        $result = $this->telegramService->sendDocument(
            $user->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Certificado de terapia enviado por Telegram'];
    }

    private function enviarTerapiaPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota)
    {
        try {
            if (!$tutor->telefono) {
                throw new \Exception('El tutor no tiene número de teléfono registrado para WhatsApp');
            }

            $telefono = $this->limpiarNumeroTelefono($tutor->telefono);
            
            Log::info('📱 Enviando WhatsApp - Terapia', [
                'original' => $tutor->telefono,
                'limpio' => $telefono,
                'mascota' => $mascota->nombre,
                'archivo' => $pdfInfo['full_path']
            ]);

            // Usando el método con plantilla de WhatsApp
            $result = $this->whatsAppService->sendCertificateWithFallback(
                $telefono,
                $pdfInfo['full_path'],
                'INFORME DE TERAPIA',
                $tutor->nombre ?? 'Tutor',
                $mascota->nombre,
                "informe_terapia_{$mascota->id}.pdf"
            );

            // Limpiar archivo temporal
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info('✅ Informe de terapia enviado exitosamente', [
                'telefono' => $telefono,
                'mascota' => $mascota->nombre,
                'method' => $result['method'] ?? 'plantilla_whatsapp'
            ]);

            return ['success' => true, 'message' => 'Informe de terapia enviado por WhatsApp'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando informe de terapia: ' . $e->getMessage(), [
                'mascota_id' => $mascota->id ?? null
            ]);
            throw $e;
        }
    }

    private function generarPdfTerapia(Terapia $terapia, Mascota $mascota, ContactoUsuario $tutor, $centroVeterinario = null): array
    {
        // Usar el método del PdfService si existe
        if (method_exists($this->pdfService, 'generarCertificadoTerapia')) {
            return $this->pdfService->generarCertificadoTerapia($terapia, $mascota, $tutor, $centroVeterinario);
        }
        
        $evolucionLabels = [
            'mejoria' => 'Mejoría',
            'estable' => 'Estable',
            'empeoramiento' => 'Empeoramiento'
        ];

        $html = view('pdf.certificado-terapia', [
            'terapia' => $terapia,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y H:i'),
            'evolucionLabels' => $evolucionLabels
        ])->render();

        $pdf = PDF::loadHTML($html)->setPaper('a4', 'portrait');

        $fileName = 'certificado_terapia_' . $terapia->id . '_' . uniqid() . '.pdf';
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
     * Enviar certificado de cirugía
     */
    public function enviarCertificadoCirugia($cirugia, $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $cirugia->procesoMedico->centroVeterinario ?? null;

            $user = $mascota->usuario->user; 

            // Generar PDF
            $pdfInfo = $this->generarPdfCirugia($cirugia, $mascota, $tutor, $centroVeterinario);

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarCirugiaPorEmail($tutor, $pdfInfo, $mascota, $cirugia);
                
                case 'telegram':
                    return $this->enviarCirugiaPorTelegram($user, $pdfInfo, $mascota, $cirugia);
                
                case 'whatsapp':
                    return $this->enviarCirugiaPorWhatsapp($tutor, $pdfInfo, $mascota, $cirugia);
                
                default:
                    throw new \Exception('Medio de envío no soportado: ' . $medioEnvio);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando certificado de cirugía: ' . $e->getMessage());
            throw $e;
        }
    }

    private function enviarCirugiaPorEmail(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, $cirugia)
    {
        try {
            // Obtener email del tutor
            $emailTutor = $tutor->email;
            
            if (!$emailTutor) {
                throw new \Exception("El tutor no tiene email registrado");
            }

            Log::info('📧 Preparando envío de email - Cirugía', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'cirugia_id' => $cirugia->id,
                'pdf_path' => $pdfInfo['full_path'] ?? 'No disponible'
            ]);

            // Enviar email
            Mail::to($emailTutor)
                ->send(new \App\Mail\CertificadoCirugiaMail($pdfInfo['full_path'], $mascota, $cirugia));

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            Log::info('✅ Email de cirugía enviado exitosamente', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'cirugia_id' => $cirugia->id
            ]);

            return ['success' => true, 'message' => 'Certificado de cirugía enviado por email'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando email de cirugía: ' . $e->getMessage());
            throw new \Exception("Error enviando email: " . $e->getMessage());
        }
    }

    private function enviarCirugiaPorTelegram(User $user, array $pdfInfo, Mascota $mascota, $cirugia)
    {
        if (!$user->telegram_chat_id) {
            throw new \Exception('El usuario no tiene Telegram configurado');
        }

        $resultadoLabels = [
            'satisfactorio' => 'Satisfactorio',
            'complicaciones' => 'Complicaciones',
            'estable' => 'Estable',
            'critico' => 'Crítico'
        ];
        
        $estadoLabels = [
            'recuperacion' => 'En recuperación',
            'alta' => 'Alta postoperatoria',
            'seguimiento' => 'Bajo seguimiento',
            'hospitalizado' => 'Hospitalizado'
        ];
        
        $resultado = $resultadoLabels[$cirugia->resultado] ?? $cirugia->resultado;
        $estado = $estadoLabels[$cirugia->estado_actual] ?? $cirugia->estado_actual;

        $caption = "🏥 **INFORME QUIRÚRGICO**\n\n" .
                "🐾 **Mascota:** {$mascota->nombre}\n" .
                "🔪 **Procedimiento:** " . ($cirugia->tipoCirugia->nombre ?? 'No especificado') . "\n" .
                "📅 **Fecha:** " . $cirugia->fecha_cirugia->format('d/m/Y H:i') . "\n" .
                "✅ **Resultado inmediato:** $resultado\n" .
                "📊 **Estado actual:** $estado\n" .
                ($cirugia->fecha_control_estimada ? "📋 **Control estimado:** " . $cirugia->fecha_control_estimada->format('d/m/Y') . "\n" : "") .
                "\n💊 **Fármacos asociados:** " . ($cirugia->farmacosAsociados->count() ? $cirugia->farmacosAsociados->count() . ' fármaco(s)' : 'Ninguno') .
                "\n\n📝 **Documento generado automáticamente por el Sistema Veterinario TERE**";

        $result = $this->telegramService->sendDocument(
            $user->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Certificado de cirugía enviado por Telegram'];
    }

    private function enviarCirugiaPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, $cirugia)
    {
        try {
            if (!$tutor->telefono) {
                throw new \Exception('El tutor no tiene número de teléfono registrado para WhatsApp');
            }

            $telefono = $this->limpiarNumeroTelefono($tutor->telefono);
            
            Log::info('📱 Enviando WhatsApp - Cirugía', [
                'original' => $tutor->telefono,
                'limpio' => $telefono,
                'mascota' => $mascota->nombre,
                'cirugia_id' => $cirugia->id,
                'archivo' => $pdfInfo['full_path']
            ]);

            // Usando el método con plantilla de WhatsApp
            $result = $this->whatsAppService->sendCertificateWithFallback(
                $telefono,
                $pdfInfo['full_path'],
                'INFORME DE CIRUGÍA',
                $tutor->nombre ?? 'Tutor',
                $mascota->nombre,
                "informe_cirugia_{$mascota->id}_{$cirugia->id}.pdf"
            );

            // Limpiar archivo temporal
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info('✅ Informe de cirugía enviado exitosamente', [
                'telefono' => $telefono,
                'mascota' => $mascota->nombre,
                'cirugia_id' => $cirugia->id,
                'method' => $result['method'] ?? 'plantilla_whatsapp'
            ]);

            return ['success' => true, 'message' => 'Informe de cirugía enviado por WhatsApp'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando informe de cirugía: ' . $e->getMessage(), [
                'cirugia_id' => $cirugia->id ?? null,
                'mascota_id' => $mascota->id ?? null
            ]);
            throw $e;
        }
    }

    private function generarPdfCirugia($cirugia, $mascota, $tutor, $centroVeterinario = null): array
    {
        // Usar el método del PdfService si existe
        if (method_exists($this->pdfService, 'generarCertificadoCirugia')) {
            return $this->pdfService->generarCertificadoCirugia($cirugia, $mascota, $tutor, $centroVeterinario);
        }
        
        $resultadoLabels = [
            'satisfactorio' => 'Satisfactorio',
            'complicaciones' => 'Complicaciones',
            'estable' => 'Estable',
            'critico' => 'Crítico'
        ];
        
        $estadoLabels = [
            'recuperacion' => 'En recuperación',
            'alta' => 'Alta postoperatoria',
            'seguimiento' => 'Bajo seguimiento',
            'hospitalizado' => 'Hospitalizado'
        ];
        
        $etapaLabels = [
            'prequirurgica' => 'Prequirúrgica',
            'transquirurgica' => 'Transquirúrgica',
            'postquirurgica_inmediata' => 'Postquirúrgica inmediata',
            'postquirurgica_tardia' => 'Postquirúrgica tardía'
        ];

        $html = view('pdf.certificado-cirugia', [
            'cirugia' => $cirugia,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y H:i'),
            'resultadoLabels' => $resultadoLabels,
            'estadoLabels' => $estadoLabels,
            'etapaLabels' => $etapaLabels
        ])->render();

        $pdf = PDF::loadHTML($html)->setPaper('a4', 'portrait');

        $fileName = 'certificado_cirugia_' . $cirugia->id . '_' . uniqid() . '.pdf';
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
     * Enviar certificado de procedimiento paliativo
     */
    public function enviarCertificadoPaliativo(CuidadoPaliativo $paliativo, Mascota $mascota, string $medioEnvio)
    {
        try {
            // Obtener datos del tutor desde ContactoUsuario
            $tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
            
            if (!$tutor) {
                throw new \Exception('No se encontró información de contacto del tutor');
            }

            // Obtener centro veterinario desde la relación
            $centroVeterinario = $paliativo->procesoMedico->centroVeterinario ?? null;

            $user = $mascota->usuario->user; 

            // Generar PDF
            $pdfInfo = $this->generarPdfPaliativo($paliativo, $mascota, $tutor, $centroVeterinario);

            // Enviar según el medio seleccionado
            switch ($medioEnvio) {
                case 'email':
                    return $this->enviarPaliativoPorEmail($tutor, $pdfInfo, $mascota, $paliativo);
                
                case 'telegram':
                    return $this->enviarPaliativoPorTelegram($user, $pdfInfo, $mascota, $paliativo);
                
                case 'whatsapp':
                    return $this->enviarPaliativoPorWhatsapp($tutor, $pdfInfo, $mascota, $paliativo);
                
                default:
                    throw new \Exception('Medio de envío no soportado: ' . $medioEnvio);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando certificado de procedimiento paliativo: ' . $e->getMessage());
            throw $e;
        }
    }

    private function enviarPaliativoPorEmail(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, CuidadoPaliativo $paliativo)
    {
        try {
            // Obtener email del tutor
            $emailTutor = $tutor->email;
            
            if (!$emailTutor) {
                throw new \Exception("El tutor no tiene email registrado");
            }

            Log::info('📧 Preparando envío de email - Procedimiento Paliativo', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'paliativo_id' => $paliativo->id,
                'pdf_path' => $pdfInfo['full_path'] ?? 'No disponible'
            ]);

            // Enviar email
            Mail::to($emailTutor)
                ->send(new CertificadoPaliativoMail($pdfInfo['full_path'], $mascota, $paliativo));

            // Limpiar archivo temporal después del envío
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            Log::info('✅ Email de procedimiento paliativo enviado exitosamente', [
                'email' => $emailTutor,
                'mascota' => $mascota->nombre,
                'paliativo_id' => $paliativo->id
            ]);

            return ['success' => true, 'message' => 'Certificado de procedimiento paliativo enviado por email'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando email de procedimiento paliativo: ' . $e->getMessage());
            throw new \Exception("Error enviando email: " . $e->getMessage());
        }
    }

    private function enviarPaliativoPorTelegram(User $user, array $pdfInfo, Mascota $mascota, CuidadoPaliativo $paliativo)
    {
        if (!$user->telegram_chat_id) {
            throw new \Exception('El usuario no tiene Telegram configurado');
        }

        $resultadoLabels = [
            'mejoria' => 'Mejoría evidente',
            'alivio' => 'Alivio parcial',
            'estabilizacion' => 'Estabilización',
            'sin_cambio' => 'Sin cambios',
            'empeoramiento' => 'Empeoramiento'
        ];
        
        $estadoLabels = [
            'estable' => 'Estable',
            'dolor_controlado' => 'Con dolor controlado',
            'dolor_parcial' => 'Con dolor parcialmente controlado',
            'deterioro' => 'En deterioro',
            'critico' => 'Crítico'
        ];
        
        $momentoLabels = [
            'inicio' => 'Inicio',
            'mantenimiento' => 'Mantenimiento',
            'rescue' => 'Rescate',
            'final' => 'Final'
        ];
        
        $resultado = $resultadoLabels[$paliativo->resultado] ?? $paliativo->resultado;
        $estado = $estadoLabels[$paliativo->estado_mascota] ?? $paliativo->estado_mascota;

        $caption = "🩺 **PROCEDIMIENTO PALIATIVO**\n\n" .
                "🐾 **Mascota:** {$mascota->nombre}\n" .
                "📋 **Procedimiento:** " . ($paliativo->tipoPaliativo->nombre ?? 'No especificado') . "\n" .
                "📅 **Fecha de inicio:** " . $paliativo->fecha_inicio->format('d/m/Y H:i') . "\n" .
                "✅ **Resultado:** $resultado\n" .
                "📊 **Estado:** $estado\n" .
                ($paliativo->frecuencia_valor ? "🔄 **Frecuencia seguimiento:** {$paliativo->frecuencia_valor} {$paliativo->frecuencia_unidad}\n" : "") .
                ($paliativo->farmacosAsociados->count() ? "💊 **Fármacos asociados:** " . $paliativo->farmacosAsociados->count() . "\n" : "") .
                "\n📝 **Documento generado automáticamente por el Sistema Veterinario TERE**";

        $result = $this->telegramService->sendDocument(
            $user->telegram_chat_id,
            $pdfInfo['full_path'],
            $caption
        );

        if (!$result['ok']) {
            throw new \Exception('Error enviando por Telegram: ' . ($result['description'] ?? 'Error desconocido'));
        }

        return ['success' => true, 'message' => 'Certificado de procedimiento paliativo enviado por Telegram'];
    }

    private function enviarPaliativoPorWhatsapp(ContactoUsuario $tutor, array $pdfInfo, Mascota $mascota, CuidadoPaliativo $paliativo)
    {
        try {
            if (!$tutor->telefono) {
                throw new \Exception('El tutor no tiene número de teléfono registrado para WhatsApp');
            }

            $telefono = $this->limpiarNumeroTelefono($tutor->telefono);
            
            Log::info('📱 Enviando WhatsApp - Procedimiento Paliativo', [
                'original' => $tutor->telefono,
                'limpio' => $telefono,
                'mascota' => $mascota->nombre,
                'paliativo_id' => $paliativo->id,
                'archivo' => $pdfInfo['full_path']
            ]);

            // Usando el método con plantilla de WhatsApp
            $result = $this->whatsAppService->sendCertificateWithFallback(
                $telefono,
                $pdfInfo['full_path'],
                'INFORME DE CUIDADO PALIATIVO',
                $tutor->nombre ?? 'Tutor',
                $mascota->nombre,
                "informe_paliativo_{$mascota->id}_{$paliativo->id}.pdf"
            );

            // Limpiar archivo temporal
            $this->limpiarArchivoTemporal($pdfInfo['full_path']);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info('✅ Informe de cuidado paliativo enviado exitosamente', [
                'telefono' => $telefono,
                'mascota' => $mascota->nombre,
                'paliativo_id' => $paliativo->id,
                'method' => $result['method'] ?? 'plantilla_whatsapp'
            ]);

            return ['success' => true, 'message' => 'Informe de cuidado paliativo enviado por WhatsApp'];

        } catch (\Exception $e) {
            if (isset($pdfInfo['full_path'])) {
                $this->limpiarArchivoTemporal($pdfInfo['full_path']);
            }
            
            Log::error('❌ Error enviando informe de cuidado paliativo: ' . $e->getMessage(), [
                'paliativo_id' => $paliativo->id ?? null,
                'mascota_id' => $mascota->id ?? null
            ]);
            throw $e;
        }
    }

    private function generarPdfPaliativo(CuidadoPaliativo $paliativo, Mascota $mascota, ContactoUsuario $tutor, $centroVeterinario = null): array
    {
        // Usar el método del PdfService si existe
        if (method_exists($this->pdfService, 'generarCertificadoPaliativo')) {
            return $this->pdfService->generarCertificadoPaliativo($paliativo, $mascota, $tutor, $centroVeterinario);
        }
        
        $resultadoLabels = [
            'mejoria' => 'Mejoría evidente',
            'alivio' => 'Alivio parcial',
            'estabilizacion' => 'Estabilización',
            'sin_cambio' => 'Sin cambios',
            'empeoramiento' => 'Empeoramiento'
        ];
        
        $estadoLabels = [
            'estable' => 'Estable',
            'dolor_controlado' => 'Con dolor controlado',
            'dolor_parcial' => 'Con dolor parcialmente controlado',
            'deterioro' => 'En deterioro',
            'critico' => 'Crítico'
        ];
        
        $momentoLabels = [
            'inicio' => 'Inicio',
            'mantenimiento' => 'Mantenimiento',
            'rescue' => 'Rescate',
            'final' => 'Final'
        ];
        
        $frecuenciaUnidadLabels = [
            'horas' => 'horas',
            'dias' => 'días',
            'semanas' => 'semanas',
            'meses' => 'meses'
        ];

        $html = view('pdf.certificado-paliativo', [
            'paliativo' => $paliativo,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y H:i'),
            'resultadoLabels' => $resultadoLabels,
            'estadoLabels' => $estadoLabels,
            'momentoLabels' => $momentoLabels,
            'frecuenciaUnidadLabels' => $frecuenciaUnidadLabels
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $fileName = 'certificado_paliativo_' . $paliativo->id . '_' . uniqid() . '.pdf';
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