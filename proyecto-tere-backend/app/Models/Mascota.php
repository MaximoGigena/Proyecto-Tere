<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Mascota extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'especie',
        'fecha_nacimiento',
        'edad_actual',
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

    public function getEdadFormateadaAttribute()
    {
        return "{$this->edad} años";
    }

    /**
     * Accessor para obtener la foto principal
     */
    public function getFotoPrincipalAttribute()
    {
        if ($this->fotos->isEmpty()) {
            return null;
        }

        $fotoPrincipal = $this->fotos->where('es_principal', true)->first();
        
        if ($fotoPrincipal) {
            return $fotoPrincipal;
        }

        // Si no hay foto principal, devolver la primera
        return $this->fotos->first();
    }

    /**
     * Accessor para obtener la URL de la foto principal
     */
    public function getFotoPrincipalUrlAttribute()
    {
        $foto = $this->foto_principal;
        
        if (!$foto) {
            return null;
        }

        return asset('storage/' . $foto->ruta_foto);
    }

    

    /**
     * Accessor para la edad (usa el campo calculado)
     */
    public function getEdadAttribute()
    {
        return $this->edad_actual;
    }

    /**
     * Método para actualizar la edad actual
     */
    public function actualizarEdad()
    {
        if (!$this->fecha_nacimiento) {
            $this->edad_actual = null;
            return;
        }

        // Solo calcular la edad sin hacer save() para evitar bucles
        $this->edad_actual = $this->calcularEdadDesdeFecha($this->fecha_nacimiento);
    }

    /**
     * Calcula la edad desde la fecha de nacimiento
     */
    private function calcularEdadDesdeFecha($fechaNacimiento)
    {
        $nacimiento = Carbon::parse($fechaNacimiento);
        $hoy = Carbon::now();
        
        // CORREGIDO: Cambiar el orden para que sea positivo
        $diffDias = $nacimiento->diffInDays($hoy); // Ahora es positivo
        
        if ($diffDias < 30) {
            return "{$diffDias} días";
        } else if ($diffDias < 365) {
            $meses = $nacimiento->diffInMonths($hoy); // CORREGIDO: mismo orden
            return "{$meses} " . ($meses === 1 ? 'mes' : 'meses');
        } else {
            $años = $nacimiento->diffInYears($hoy); // CORREGIDO: mismo orden
            $mesesRestantes = $nacimiento->diffInMonths($hoy) % 12; // CORREGIDO: mismo orden
            
            if ($mesesRestantes > 0) {
                return "{$años} " . ($años === 1 ? 'año' : 'años') . " y {$mesesRestantes} " . ($mesesRestantes === 1 ? 'mes' : 'meses');
            }
            
            return "{$años} " . ($años === 1 ? 'año' : 'años');
        }
    }

    protected $with = ['caracteristicas', 'fotos'];
}