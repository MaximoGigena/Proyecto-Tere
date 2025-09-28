<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Mascota extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'especie',
        'edad',
        'unidad_edad',
        'sexo',
        'usuario_id'
    ];

    public function baja(): HasOne
    {
        return $this->hasOne(BajaMascota::class);
    }

    public function usuario(): BelongsTo
    {
         return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function caracteristicas(): HasOne
    {
        return $this->hasOne(CaracteristicasMascota::class);
    }

    public function fotos()
    {
        return $this->hasMany(MascotaFoto::class);
    }

    // Método para dar de baja
    public function darDeBaja(int $motivoBajaId, ?string $observacion = null, int $usuarioId): bool
    {
        return DB::transaction(function () use ($motivoBajaId, $observacion, $usuarioId) {
            // Crear registro de baja
            $baja = BajaMascota::create([
                'mascota_id' => $this->id,
                'motivo_baja_id' => $motivoBajaId,
                'observacion' => $observacion,
                'usuario_id' => $usuarioId
            ]);
            
            // Soft delete de la mascota
            return $this->delete();
        });
    }
    
    // Verificar si está dada de baja
    public function getEstaDadaDeBajaAttribute(): bool
    {
        return $this->baja !== null;
    }

    protected $with = ['caracteristicas', 'fotos'];
}