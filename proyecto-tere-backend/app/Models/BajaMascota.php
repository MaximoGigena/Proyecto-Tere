<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BajaMascota extends Model
{
    protected $table = 'bajas_mascotas';
    
    protected $fillable = [
        'mascota_id',
        'motivo_baja_id',
        'usuario_id',
        'observacion'
    ];
    
    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class);
    }
    
    public function motivo(): BelongsTo
    {
        return $this->belongsTo(MotivoBaja::class, 'motivo_baja_id');
    }
    
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}