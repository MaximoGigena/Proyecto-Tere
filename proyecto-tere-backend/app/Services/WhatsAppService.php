<?php
// app/Services/WhatsAppService.php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
     * Enviar documento usando Base64 (más confiable que URL)
     */
    public function sendDocumentAsBase64(string $to, string $pdfPath, ?string $caption = null, ?string $filename = null): array
    {
        try {
            if (!file_exists($pdfPath)) {
                throw new \Exception("El archivo PDF no existe: {$pdfPath}");
            }

            $to = $this->formatPhoneNumberForTwilio($to);
            
            // Leer el PDF y convertirlo a Base64
            $pdfContent = file_get_contents($pdfPath);
            $base64Content = base64_encode($pdfContent);
            
            // Crear URI data
            $mediaUrl = 'data:application/pdf;base64,' . $base64Content;
            
            Log::info('📤 Enviando documento como Base64', [
                'to' => $to,
                'filename' => $filename,
                'size' => strlen($pdfContent)
            ]);

            $message = $this->twilioClient->messages->create(
                $to,
                [
                    'from' => $this->whatsappNumber,
                    'body' => $caption ?? '📄 Documento generado por Sistema Veterinario TERE',
                    'mediaUrl' => [$mediaUrl]
                ]
            );

            return [
                'success' => true,
                'message' => 'Documento enviado correctamente',
                'message_id' => $message->sid
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error enviando documento Base64', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar un documento PDF por WhatsApp
     */
    public function sendDocument(string $to, string $pdfPath, ?string $caption = null, ?string $filename = null): array
    {
        try {
            // Verificar que el archivo existe
            if (!file_exists($pdfPath)) {
                throw new \Exception("El archivo PDF no existe en la ruta: {$pdfPath}");
            }

            // Formatear número para Twilio (formato: whatsapp:+549XXXXXXXXXX)
            $to = $this->formatPhoneNumberForTwilio($to);
            
            // Verificar si el número está verificado en modo trial
            if ($this->isTrial && !$this->isVerifiedNumber($to)) {
                $errorMsg = "⚠️ Modo trial: El número {$to} no está verificado. Debes verificarlo en Twilio Console.";
                Log::warning($errorMsg);
                
                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'needs_verification' => true
                ];
            }

            // Preparar el nombre del archivo
            if (!$filename) {
                $filename = 'documento_' . time() . '.pdf';
            } elseif (!str_ends_with($filename, '.pdf')) {
                $filename .= '.pdf';
            }

            // Obtener URL pública del archivo
            $mediaUrl = $this->getPublicUrl($pdfPath, $filename);

            Log::info('📤 Enviando documento por Twilio WhatsApp', [
                'to' => $to,
                'from' => $this->whatsappNumber,
                'filename' => $filename,
                'url' => $mediaUrl
            ]);

            // Enviar mensaje con documento usando Twilio
            $message = $this->twilioClient->messages->create(
                $to, // Número destino (formato whatsapp:+...)
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
            Log::error('❌ Error de Twilio REST API', [
                'to' => $to ?? null,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'more_info' => $e->getMoreInfo()
            ]);

            return [
                'success' => false,
                'message' => 'Error Twilio: ' . $e->getMessage(),
                'code' => $e->getCode()
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Excepción al enviar documento por WhatsApp', [
                'to' => $to ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar mensaje de texto simple
     */
    public function sendTextMessage(string $to, string $message): array
    {
        try {
            $to = $this->formatPhoneNumberForTwilio($to);
            
            // Verificar si el número está verificado en modo trial
            if ($this->isTrial && !$this->isVerifiedNumber($to)) {
                $errorMsg = "⚠️ Modo trial: El número {$to} no está verificado.";
                Log::warning($errorMsg);
                
                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'needs_verification' => true
                ];
            }

            $twilioMessage = $this->twilioClient->messages->create(
                $to,
                [
                    'from' => $this->whatsappNumber,
                    'body' => $message
                ]
            );

            Log::info('✅ Mensaje de texto enviado por Twilio', [
                'to' => $to,
                'message_sid' => $twilioMessage->sid
            ]);

            return [
                'success' => true,
                'message' => 'Mensaje enviado correctamente',
                'message_id' => $twilioMessage->sid
            ];

        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de texto: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Formatear número para Twilio (formato E.164 con prefijo whatsapp:)
     * Ejemplo: 5493758526513 -> whatsapp:+5493758526513
     */
    private function formatPhoneNumberForTwilio(string $phone): string
    {
        // Limpiar número (solo dígitos)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Para Argentina, SIEMPRE debe tener el 9 después del 54
        // 543758526513 -> 5493758526513
        
        // CASO 1: Viene con 54 (12 dígitos) - Ej: 543758526513
        if (strlen($phone) === 12 && str_starts_with($phone, '54')) {
            // Agregar el 9 después del 54
            $phone = '549' . substr($phone, 2); // 549 + 3758526513
        }
        // CASO 2: Viene con 549 (13 dígitos) - Ej: 5493758526513
        elseif (strlen($phone) === 13 && str_starts_with($phone, '549')) {
            // Ya está bien, no hacer nada
        }
        // CASO 3: Viene sin código de país (10 dígitos) - Ej: 3758526513
        elseif (strlen($phone) === 10) {
            $phone = '549' . $phone;
        }
        // CASO 4: Viene con 9 pero sin 54 (11 dígitos) - Ej: 93758526513
        elseif (strlen($phone) === 11 && str_starts_with($phone, '9')) {
            $phone = '54' . $phone;
        }
        
        // Formato final: whatsapp:+5493758526513
        $formatted = 'whatsapp:+' . $phone;
        
        Log::info('📞 Número formateado para Twilio', [
            'original_input' => $phone,
            'formatted' => $formatted,
            'length' => strlen($formatted)
        ]);
        
        return $formatted;
    }
    /**
     * Obtener URL pública del archivo (mismo método que tenías)
     */
    /**
     * Obtener URL pública del archivo
     */
    private function getPublicUrl(string $localPath, string $filename): string
    {
        // Crear directorio público si no existe
        $publicDir = public_path('temp_whatsapp');
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0777, true);
        }

        // Copiar archivo al directorio público
        $publicPath = $publicDir . '/' . $filename;
        copy($localPath, $publicPath);
        
        // Asegurar permisos correctos
        chmod($publicPath, 0644);
        
        // Verificar que el archivo se copió correctamente
        if (!file_exists($publicPath)) {
            throw new \Exception("No se pudo copiar el archivo al directorio público: {$publicPath}");
        }
        
        // Construir URL PÚBLICA accesible externamente
        // Usar la URL del BACKEND (ngrok) no la de localhost
        $baseUrl = env('APP_URL_BACKEND', env('APP_URL', 'http://localhost:8000'));
        $publicUrl = rtrim($baseUrl, '/') . '/temp_whatsapp/' . $filename;
        
        Log::info('🔗 URL pública generada', [
            'local_path' => $localPath,
            'public_path' => $publicPath,
            'public_url' => $publicUrl,
            'file_exists' => file_exists($publicPath),
            'file_size' => file_exists($publicPath) ? filesize($publicPath) : 0
        ]);
        
        // Programar eliminación después de 5 minutos
        $this->scheduleFileDeletion($publicPath, 300);
        
        return $publicUrl;
    }

    /**
     * Programar eliminación de archivo temporal (igual)
     */
    private function scheduleFileDeletion(string $filePath, int $delaySeconds = 300): void
    {
        if (function_exists('fastcgi_finish_request')) {
            register_shutdown_function(function() use ($filePath, $delaySeconds) {
                sleep($delaySeconds);
                if (file_exists($filePath)) {
                    unlink($filePath);
                    Log::info('🗑️ Archivo temporal eliminado: ' . $filePath);
                }
            });
        } else {
            $cleanupLog = storage_path('logs/whatsapp_files_to_cleanup.log');
            $expirationTime = time() + $delaySeconds;
            file_put_contents($cleanupLog, "{$filePath}|{$expirationTime}\n", FILE_APPEND);
        }
    }

    /**
     * Verificar si un número está en la lista de números verificados (modo trial)
     */
    private function isVerifiedNumber(string $to): bool
    {
        // En modo trial, Twilio solo permite enviar a números verificados
        // Puedes obtener la lista de la API o mantener una lista local
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