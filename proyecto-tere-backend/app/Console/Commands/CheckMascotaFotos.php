<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MascotaFoto;

class CheckMascotaFotos extends Command
{
    protected $signature = 'check:mascota-fotos';
    protected $description = 'Verifica el estado de las fotos de mascotas';

    public function handle()
    {
        $fotos = MascotaFoto::all();
        
        $this->info("Total de fotos en BD: " . $fotos->count());
        
        foreach ($fotos as $foto) {
            $this->line("--- Foto ID: {$foto->id} ---");
            $this->line("Ruta: {$foto->ruta_foto}");
            $this->line("URL Accessor: {$foto->url}");
            $this->line("Is External: " . ($foto->is_external ? 'SÍ' : 'NO'));
            $this->line("");
        }
        
        return 0;
    }
}