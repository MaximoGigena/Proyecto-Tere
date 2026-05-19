<?php
// app/Services/WhatsAppService.php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $twilioClient;
    protected $whatsappNumber;
    protected $isTrial;

    public function __construct()
    {
        $this->twilioClient = new Client(
            env('TWILIO_ACCOUNT_SID'),
            env('TWILIO_AUTH_TOKEN')
        );
        $this->whatsappNumber = env('TWILIO_WHATSAPP_NUMBER');
        $this->isTrial = env('TWILIO_TRIAL', true);
    }

    /**
     * Enviar documento USANDO PLANTILLA (recomendado)
     * Esta es la solución para el error 63016
     */
    public function sendDocumentWithTemplate(
        string $to, 
        string $pdfPath, 
        string $tipoProcedimiento,
        string $nombreTutor,
        string $nombreMascota,
        ?string $filename = null
    ): array {
        try {
            if (!file_exists($pdfPath)) {
                throw new \Exception("El archivo PDF no existe: {$pdfPath}");
            }

            $to = $this->formatPhoneNumberForTwilio($to);
            
            // Primero, subir el archivo a Twilio Media
            $mediaUrl = $this->uploadMediaToTwilio($pdfPath, $filename);
            
            // Obtener SID de la plantilla (debes crearla en Twilio Console)
            $templateSid = env('TWILIO_TEMPLATE_CERTIFICADO_SID');
            if (!$templateSid) {
                throw new \Exception('No se ha configurado el SID de la plantilla de WhatsApp');
            }
            
            Log::info('📤 Enviando documento usando plantilla de WhatsApp', [
                'to' => $to,
                'template_sid' => $templateSid,
                'filename' => $filename,
                'tipo_procedimiento' => $tipoProcedimiento
            ]);

            // Enviar mensaje usando Content Template
            $message = $this->twilioClient->messages->create(
                $to,
                [
                    'from' => $this->whatsappNumber,
                    'contentSid' => $templateSid,
                    'contentVariables' => json_encode([
                        '1' => $tipoProcedimiento,      // Tipo de certificado
                        '2' => $nombreTutor,            // Nombre del tutor
                        '3' => $nombreMascota,          // Nombre de la mascota
                        '4' => $filename ?? 'documento.pdf'  // Nombre del archivo
                    ]),
                    'mediaUrl' => [$mediaUrl]  // Adjuntar el documento
                ]
            );

            Log::info('✅ Documento enviado exitosamente con plantilla', [
                'to' => $to,
                'message_sid' => $message->sid,
                'status' => $message->status
            ]);

            return [
                'success' => true,
                'message' => 'Documento enviado correctamente con plantilla',
                'message_id' => $message->sid,
                'status' => $message->status
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error enviando documento con plantilla', [
                'error' => $e->getMessage(),
                'to' => $to ?? null
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Subir archivo a Twilio Media para usar como attachment
     */
    private function uploadMediaToTwilio(string $pdfPath, ?string $filename = null): string
    {
        // Leer el archivo
        $pdfContent = file_get_contents($pdfPath);
        $base64Content = base64_encode($pdfContent);
        
        $filename = $filename ?? 'certificado_' . time() . '.pdf';
        
        // Subir a Twilio Media Service
        $media = $this->twilioClient->media->v1->media->create([
            'contentType' => 'application/pdf',
            'friendlyName' => $filename,
            'media' => $base64Content
        ]);
        
        Log::info('📎 Archivo subido a Twilio Media', [
            'media_sid' => $media->sid,
            'filename' => $filename
        ]);
        
        // Retornar la URL del media en Twilio
        return "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioClient->accountSid}/Messages/Media/{$media->sid}";
    }

    /**
     * Método alternativo: Enviar documento con URL pública usando plantilla
     * (Si la plantilla ya incluye la URL como variable)
     */
    public function sendDocumentWithTemplateAndUrl(
        string $to,
        string $publicUrl,
        string $tipoProcedimiento,
        string $nombreTutor,
        string $nombreMascota
    ): array {
        try {
            $to = $this->formatPhoneNumberForTwilio($to);
            $templateSid = env('TWILIO_TEMPLATE_CERTIFICADO_SID');
            
            if (!$templateSid) {
                throw new \Exception('No se ha configurado el SID de la plantilla de WhatsApp');
            }

            $message = $this->twilioClient->messages->create(
                $to,
                [
                    'from' => $this->whatsappNumber,
                    'contentSid' => $templateSid,
                    'contentVariables' => json_encode([
                        '1' => $tipoProcedimiento,
                        '2' => $nombreTutor,
                        '3' => $nombreMascota,
                        '4' => $publicUrl  // La URL pública de tu PDF
                    ])
                ]
            );

            return [
                'success' => true,
                'message' => 'Documento enviado correctamente con plantilla',
                'message_id' => $message->sid
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error enviando documento con plantilla y URL', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar mensaje de texto usando plantilla (para mensajes fuera de ventana)
     */
    public function sendTextMessageWithTemplate(string $to, string $templateSid, array $variables): array
    {
        try {
            $to = $this->formatPhoneNumberForTwilio($to);

            $message = $this->twilioClient->messages->create(
                $to,
                [
                    'from' => $this->whatsappNumber,
                    'contentSid' => $templateSid,
                    'contentVariables' => json_encode($variables)
                ]
            );

            return [
                'success' => true,
                'message_id' => $message->sid
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error enviando mensaje con plantilla: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar documento (FALLBACK: solo funciona dentro de ventana de 24h)
     */
    public function sendDocument(string $to, string $pdfPath, ?string $caption = null, ?string $filename = null): array
    {
        try {
            if (!file_exists($pdfPath)) {
                throw new \Exception("El archivo PDF no existe en la ruta: {$pdfPath}");
            }

            $to = $this->formatPhoneNumberForTwilio($to);
            
            // Obtener URL pública
            $mediaUrl = $this->getPublicUrl($pdfPath, $filename ?? 'documento.pdf');

            Log::info('📤 Enviando documento por Twilio WhatsApp (intentando método estándar)', [
                'to' => $to,
                'from' => $this->whatsappNumber,
                'url' => $mediaUrl
            ]);

            $message = $this->twilioClient->messages->create(
                $to,
                [
                    'from' => $this->whatsappNumber,
                    'body' => $caption ?? '📄 Documento generado por Sistema Veterinario TERE',
                    'mediaUrl' => [$mediaUrl]
                ]
            );

            Log::info('✅ Documento enviado exitosamente por Twilio', [
                'to' => $to,
                'message_sid' => $message->sid,
                'status' => $message->status
            ]);

            return [
                'success' => true,
                'message' => 'Documento enviado correctamente',
                'message_id' => $message->sid,
                'status' => $message->status
            ];

        } catch (\Twilio\Exceptions\RestException $e) {
            // Si el error es 63016, intentar con plantilla
            if ($e->getCode() == 63016) {
                Log::warning('⚠️ Error 63016: Fora de ventana de 24h, intentando con plantilla...');
                
                // Intentar con plantilla (necesitas los datos del tutor)
                return [
                    'success' => false,
                    'message' => 'Se requiere usar plantilla de WhatsApp para este envío',
                    'error_code' => 63016,
                    'needs_template' => true
                ];
            }
            
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ Excepción al enviar documento por WhatsApp', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Método principal que decide qué estrategia usar
     */
    public function sendCertificateWithFallback(
        string $to,
        string $pdfPath,
        string $tipoProcedimiento,
        string $nombreTutor,
        string $nombreMascota,
        ?string $filename = null
    ): array {
        // Primero intentar con el método estándar (si está dentro de la ventana)
        $result = $this->sendDocument($to, $pdfPath, null, $filename);
        
        // Si falla por ventana de tiempo, usar plantilla
        if (isset($result['needs_template']) && $result['needs_template'] === true) {
            Log::info('🔄 Cambiando a método con plantilla por ventana de tiempo cerrada');
            
            return $this->sendDocumentWithTemplate(
                $to,
                $pdfPath,
                $tipoProcedimiento,
                $nombreTutor,
                $nombreMascota,
                $filename
            );
        }
        
        return $result;
    }

    private function formatPhoneNumberForTwilio(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) === 12 && str_starts_with($phone, '54')) {
            $phone = '549' . substr($phone, 2);
        } elseif (strlen($phone) === 13 && str_starts_with($phone, '549')) {
            // Ya está bien
        } elseif (strlen($phone) === 10) {
            $phone = '549' . $phone;
        } elseif (strlen($phone) === 11 && str_starts_with($phone, '9')) {
            $phone = '54' . $phone;
        }
        
        return 'whatsapp:+' . $phone;
    }

    private function getPublicUrl(string $localPath, string $filename): string
    {
        $publicDir = public_path('temp_whatsapp');
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0777, true);
        }

        $publicPath = $publicDir . '/' . $filename;
        copy($localPath, $publicPath);
        chmod($publicPath, 0644);
        
        $baseUrl = env('APP_URL_BACKEND', env('APP_URL', 'http://localhost:8000'));
        return rtrim($baseUrl, '/') . '/temp_whatsapp/' . $filename;
    }

    private function isVerifiedNumber(string $to): bool
    {
        $verifiedNumbers = explode(',', env('TWILIO_VERIFIED_NUMBERS', ''));
        
        foreach ($verifiedNumbers as $verified) {
            $verified = 'whatsapp:+' . preg_replace('/[^0-9]/', '', $verified);
            if ($to === $verified) {
                return true;
            }
        }
        
        return false;
    }
}