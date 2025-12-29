<?php
// app/Models/ArchivoCirugia.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoCirugia extends Model
{
    protected $table = 'archivos_cirugias';
    
    protected $fillable = [
        'cirugia_id',
        'nombre_original',
        'ruta',
        'tipo',
        'tamano'
    ];

    public function cirugia(): BelongsTo
    {
        return $this->belongsTo(Cirugia::class);
    }
}