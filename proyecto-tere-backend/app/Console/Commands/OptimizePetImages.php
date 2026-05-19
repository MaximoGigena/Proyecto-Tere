<?php
// php artisan make:command OptimizePetImages

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mascota;
use App\Services\ImageOptimizationService;
use Illuminate\Support\Facades\Storage;

class OptimizePetImages extends Command
{
    protected $signature = 'images:optimize-pets';
    protected $description = 'Optimiza todas las imágenes de mascotas existentes';
    
    public function handle(ImageOptimizationService $optimizer)
    {
        $mascotas = Mascota::with('fotos')->get();
        
        $bar = $this->output->createProgressBar($mascotas->count());
        $bar->start();
        
        foreach ($mascotas as $mascota) {
            foreach ($mascota->fotos as $foto) {
                $originalPath = $foto->ruta_foto;
                
                if (Storage::disk('public')->exists($originalPath)) {
                    $optimizer->optimizeAndResize($originalPath, $mascota->id);
                }
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\n✅ Imágenes optimizadas correctamente!");
    }
}