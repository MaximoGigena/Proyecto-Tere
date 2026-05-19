<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MascotaFoto extends Model
{
    protected $table = 'mascota_fotos';

    protected $fillable = [
        'mascota_id',
        'ruta_foto', 
        'es_principal',
    ];

    public function mascota()
    {
        return $this->belongsTo(Mascota::class);
    }

    protected $appends = ['url', 'is_external', 'optimized_urls'];

    // Determinar si es una URL externa
    public function getIsExternalAttribute()
    {
        return Str::startsWith($this->ruta_foto, 'http://') || 
               Str::startsWith($this->ruta_foto, 'https://');
    }


    /**
     * Obtener URLs optimizadas por tamaño
     */
    // En app/Models/MascotaFoto.php
    public function getOptimizedUrlsAttribute()
    {
        $cacheKey = "foto_optimized_{$this->id}";
        
        return Cache::remember($cacheKey, 86400, function () {
            $originalPath = $this->ruta_foto;
            $dirname = dirname($originalPath);
            $filename = pathinfo($originalPath, PATHINFO_FILENAME);
            $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
            
            $sizes = ['thumbnail', 'small', 'medium', 'large'];
            $urls = [];
            
            foreach ($sizes as $size) {
                $optimizedPath = "{$dirname}/{$filename}_{$size}.{$extension}";
                
                // ✅ Verificar si existe versión optimizada en storage
                if (Storage::disk('public')->exists($optimizedPath)) {
                    // ✅ Usar URL directa de storage (más confiable)
                    $urls[$size] = asset('storage/' . $optimizedPath);
                } else {
                    // ✅ Usar URL dinámica como fallback
                    $urls[$size] = $this->getDynamicUrl($size);
                }
            }
            
            // Agregar original para compatibilidad
            $urls['original'] = $this->url;
            
            return $urls;
        });
    }
    
    /**
     * Generar URL dinámica para imágenes no optimizadas aún
     */

    protected function getDynamicUrl($size)
    {
        $dimensions = [
            'thumbnail' => '150x150',
            'small' => '400x400',
            'medium' => '800x800',
            'large' => '1200x1200'
        ];
        
        $dimension = $dimensions[$size] ?? '800x800';
        
        // ✅ CORREGIDO: Asegurar que la ruta esté codificada correctamente
        $encodedPath = urlencode($this->ruta_foto);
        
        // ✅ Usar URL completa para evitar problemas
        return url("/image/dynamic/{$encodedPath}/{$dimension}");
    }
    
    /**
     * Para compatibilidad con código existente
     */
    // En app/Models/MascotaFoto.php
    public function getUrlAttribute($value)
    {
        // Si la ruta es externa, devolver directamente
        if ($this->is_external) {
            return $this->ruta_foto;
        }
        
        // Si ya es URL de storage
        if (str_starts_with($this->ruta_foto, '/storage/')) {
            return $this->ruta_foto;
        }
        
        // Si la ruta comienza con 'storage/' (sin slash inicial)
        if (str_starts_with($this->ruta_foto, 'storage/')) {
            return '/' . $this->ruta_foto;
        }
        
        // Construir URL de storage
        return '/storage/' . ltrim($this->ruta_foto, '/');
    }
}