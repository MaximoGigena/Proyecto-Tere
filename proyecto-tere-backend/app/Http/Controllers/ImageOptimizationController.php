<?php
// app/Http/Controllers/ImageOptimizationController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImageOptimizationController extends Controller
{
    protected ImageManager $manager;
    
    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }
    
    public function getOptimizedImage(Request $request, string $path)
    {
        // 🔥 LOG de entrada
        Log::info('=== INICIO getOptimizedImage ===');
        Log::info('Path recibido: ' . $path);
        Log::info('Size: ' . $request->get('size', 'medium'));
        
        $size = $request->get('size', 'medium');
        
        $dimensions = [
            'thumbnail' => ['w' => 150, 'h' => 150, 'q' => 70],
            'small' => ['w' => 400, 'h' => 400, 'q' => 80],
            'medium' => ['w' => 800, 'h' => 800, 'q' => 85],
            'large' => ['w' => 1200, 'h' => 1200, 'q' => 90],
        ];
        
        $dim = $dimensions[$size] ?? $dimensions['medium'];
        
        // Decodificar la ruta
        $decodedPath = urldecode($path);
        Log::info('Decoded path: ' . $decodedPath);
        
        // Limpiar la ruta
        $cleanPath = $decodedPath;
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
            Log::info('Removido storage/, nueva ruta: ' . $cleanPath);
        }
        
        // 🔥 Buscar la imagen
        $fullPath = null;
        $pathsToTry = [
            Storage::disk('public')->path($cleanPath),
            storage_path("app/public/{$cleanPath}"),
            public_path("storage/{$cleanPath}"),
        ];
        
        Log::info('Intentando rutas:');
        foreach ($pathsToTry as $tryPath) {
            Log::info('- ' . $tryPath . ' => ' . (file_exists($tryPath) ? 'EXISTE' : 'NO EXISTE'));
            if (file_exists($tryPath)) {
                $fullPath = $tryPath;
                break;
            }
        }
        
        if (!$fullPath) {
            // Búsqueda recursiva
            Log::info('Buscando recursivamente archivos que coincidan con: ' . basename($cleanPath));
            $allFiles = Storage::disk('public')->allFiles();
            $basename = basename($cleanPath);
            
            $matchingFile = collect($allFiles)->first(function ($file) use ($basename) {
                return str_ends_with($file, $basename);
            });
            
            if ($matchingFile) {
                $fullPath = Storage::disk('public')->path($matchingFile);
                Log::info('✅ Encontrado por búsqueda: ' . $matchingFile);
            } else {
                Log::error('❌ No se encontró ninguna imagen');
                
                // Devolver error detallado en lugar de imagen por defecto
                return response()->json([
                    'error' => 'Image not found',
                    'debug' => [
                        'received_path' => $path,
                        'decoded_path' => $decodedPath,
                        'clean_path' => $cleanPath,
                        'basename' => basename($cleanPath),
                        'tried_paths' => $pathsToTry,
                        'all_files_count' => count($allFiles),
                        'sample_files' => array_slice($allFiles, 0, 10)
                    ]
                ], 404);
            }
        }
        
        try {
            Log::info('Procesando imagen: ' . $fullPath);
            $image = $this->manager->read($fullPath);
            $image->scale(width: $dim['w'], height: $dim['h']);
            $blob = (string) $image->toJpeg($dim['q'])->toBlob();
            
            Log::info('✅ Imagen procesada exitosamente');
            
            return response($blob, 200)
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=31536000, immutable');
                
        } catch (\Exception $e) {
            Log::error('Error procesando: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to process image',
                'message' => $e->getMessage(),
                'file' => $fullPath
            ], 500);
        }
    }
    
    /**
     * Retorna una imagen por defecto cuando no se encuentra la original
     */
    private function getDefaultImage(int $width, int $height)
    {
        try {
            // Crear una imagen gris con texto "Imagen no disponible"
            $img = $this->manager->create($width, $height);
            $img->fill('#cccccc');
            $img->text('Imagen no disponible', $width/2, $height/2, function($font) {
                $font->size(16);
                $font->color('#666666');
                $font->align('center');
                $font->valign('middle');
            });
            
            $blob = (string) $img->toJpeg(80)->toBlob();
            
            return response($blob, 200)
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=3600');
                
        } catch (\Exception $e) {
            // Fallback final: redirigir a placeholder externo
            return redirect("https://picsum.photos/id/100/{$width}/{$height}");
        }
    }
}