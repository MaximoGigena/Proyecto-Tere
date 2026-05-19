<?php
// app/Services/ImageOptimizationService.php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // 👈 IMPORTAR EL DRIVER
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageOptimizationService
{
    protected ImageManager $manager; // 👈 TIPO EXPLÍCITO
    
    // Tamaños predefinidos
    protected array $sizes = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'small' => ['width' => 400, 'height' => 400],
        'medium' => ['width' => 800, 'height' => 800],
        'large' => ['width' => 1200, 'height' => 1200],
    ];
    
    public function __construct()
    {
        // ✅ VERSIÓN 3.x - Pasar el driver como argumento
        $this->manager = new ImageManager(new Driver());
    }
    
    public function optimizeAndResize(string $imagePath, int $mascotaId): ?array
    {
        $originalPath = Storage::disk('public')->path($imagePath);
        
        if (!file_exists($originalPath)) {
            return null;
        }
        
        $optimizedPaths = [];
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $filename = pathinfo($originalPath, PATHINFO_FILENAME);
        
        // Crear directorio optimizado
        $optimizedDir = "mascotas/optimized/{$mascotaId}";
        Storage::disk('public')->makeDirectory($optimizedDir);
        
        foreach ($this->sizes as $sizeName => $dimensions) {
            try {
                // ✅ VERSIÓN 3.x - Usar read() en lugar de make()
                $image = $this->manager->read($originalPath);
                
                // Redimensionar manteniendo aspect ratio
                $image->scale(width: $dimensions['width'], height: $dimensions['height']);
                
                // Optimizar calidad
                $quality = $sizeName === 'thumbnail' ? 70 : 85;
                
                // Guardar versión optimizada
                $optimizedPath = "{$optimizedDir}/{$filename}_{$sizeName}.{$extension}";
                
                // ✅ VERSIÓN 3.x - encode() devuelve un objeto, toFile() guarda
                $image->toJpeg($quality)->toFile(Storage::disk('public')->path($optimizedPath));
                
                $optimizedPaths[$sizeName] = Storage::url($optimizedPath);
                
            } catch (\Exception $e) {
                Log::error("Error optimizando imagen {$sizeName}: " . $e->getMessage());
                continue;
            }
        }
        
        return $optimizedPaths;
    }
}