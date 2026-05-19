<?php

namespace App\Http\Controllers;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function dynamic(Request $request, string $path, string $size)
    {
        try {
            Log::info('ImageController@dynamic llamado', [
                'path' => $path,
                'size' => $size
            ]);
            
            // Decodificar la ruta
            $decodedPath = urldecode($path);
            
            // ✅ CORRECCIÓN: Verificar si el archivo existe en storage
            // Storage::disk('public')->exists() es más confiable que file_exists()
            if (!Storage::disk('public')->exists($decodedPath)) {
                Log::error('Archivo no encontrado en storage:', ['path' => $decodedPath]);
                
                // Intentar buscar sin el primer segmento si es necesario
                $alternativePath = ltrim($decodedPath, '/');
                if (Storage::disk('public')->exists($alternativePath)) {
                    $decodedPath = $alternativePath;
                    Log::info('Archivo encontrado con ruta alternativa:', ['path' => $decodedPath]);
                } else {
                    return $this->getDefaultImage($size);
                }
            }
            
            // Obtener el archivo usando Storage
            $fileContent = Storage::disk('public')->get($decodedPath);
            
            // Parsear dimensiones
            list($width, $height) = explode('x', $size);
            
            // Procesar imagen con Intervention
            $manager = new ImageManager(new Driver());
            $image = $manager->read($fileContent);
            
            // Redimensionar manteniendo proporción
            $image->scale(width: (int)$width, height: (int)$height);
            
            // Determinar el formato original
            $extension = pathinfo($decodedPath, PATHINFO_EXTENSION);
            $format = in_array(strtolower($extension), ['png', 'gif', 'webp']) ? strtolower($extension) : 'jpeg';
            
            // Convertir con calidad según formato
            if ($format === 'png') {
                $blob = (string) $image->toPng()->toBlob();
                $contentType = 'image/png';
            } elseif ($format === 'gif') {
                $blob = (string) $image->toGif()->toBlob();
                $contentType = 'image/gif';
            } elseif ($format === 'webp') {
                $blob = (string) $image->toWebp(85)->toBlob();
                $contentType = 'image/webp';
            } else {
                $blob = (string) $image->toJpeg(85)->toBlob();
                $contentType = 'image/jpeg';
            }
            
            return response($blob, 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=31536000, immutable');
                
        } catch (\Exception $e) {
            Log::error('Error en ImageController: ' . $e->getMessage(), [
                'path' => $path,
                'size' => $size,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getDefaultImage($size);
        }
    }
    
    private function getDefaultImage($size)
    {
        try {
            list($width, $height) = explode('x', $size);
            
            // Crear imagen de placeholder
            $manager = new ImageManager(new Driver());
            $image = $manager->create((int)$width, (int)$height);
            $image->fill('#e2e8f0'); // Color gris claro más agradable
            
            // Agregar texto
            $image->text('🐾 Imagen no disponible', (int)$width/2, (int)$height/2, function($font) {
                $font->size(min(20, (int)$width/15));
                $font->color('#64748b');
                $font->align('center');
                $font->valign('middle');
            });
            
            $blob = (string) $image->toJpeg(80)->toBlob();
            
            return response($blob, 200)
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=3600');
        } catch (\Exception $e) {
            // Fallback extremo - devolver un SVG simple
            return response('<svg width="400" height="400" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" font-family="Arial" font-size="20" fill="#64748b" text-anchor="middle" dy=".3em">🐾 Imagen no disponible</text></svg>', 200)
                ->header('Content-Type', 'image/svg+xml');
        }
    }
}