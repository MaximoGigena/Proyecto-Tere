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
        'fecha_nacimiento', // Ahora es string dd/mm/yyyy
        'sexo',
        'usuario_id'
    ];

    protected $casts = [
        // Remover el casting date para fecha_nacimiento
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

    // RELACIÓN PRINCIPAL - usar este nombre para evitar conflictos
    public function edadRelacion(): HasOne
    {
        return $this->hasOne(EdadMascota::class);
    }

    // ALIAS para compatibilidad (opcional) - PERO CUIDADO CON LOS ACCESSORS
    public function edad(): HasOne
    {
        return $this->hasOne(EdadMascota::class);
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

    /**
     * Accessor para obtener la edad formateada
     */
    public function getEdadFormateadaAttribute()
    {
        if ($this->relationLoaded('edadRelacion') && $this->edadRelacion) {
            return $this->edadRelacion->edad_formateada ?? 'Edad no disponible';
        }
        
        // Si no está cargada la relación, calcular directamente desde string dd/mm/yyyy
        if ($this->fecha_nacimiento) {
            return $this->calcularEdadDirectamente($this->fecha_nacimiento);
        }
        
        return 'Edad no disponible';
    }

    /**
     * ELIMINAR ESTE ACCESSOR - CAUSA CONFLICTO
     * Comenta o elimina este método para evitar el bucle infinito
     */
    /*
    public function getEdadAttribute()
    {
        return $this->edad_formateada;
    }
    */

    /**
     * Método para actualizar la edad
     */
    public function actualizarEdad(): void
    {
        $relacionEdad = $this->edadRelacion()->first();
        
        if (!$relacionEdad) {
            // Crear registro de edad si no existe
            $relacionEdad = EdadMascota::create(['mascota_id' => $this->id]);
        }

        if ($relacionEdad->necesitaActualizacion()) {
            $relacionEdad->actualizarDesdeFechaNacimiento($this->fecha_nacimiento);
        }
    }
    
    /**
     * Calcular edad directamente desde string dd/mm/yyyy - CORREGIDO
     */
    private function calcularEdadDirectamente($fechaNacimiento): string
    {
        if (!$fechaNacimiento) {
            return 'Edad no disponible';
        }

        try {
            // Usar el mismo método de parseo que en EdadMascota
            $partes = [];
            
            // Intentar formato DD/MM/YYYY
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fechaNacimiento, $partes)) {
                $dia = (int) $partes[1];
                $mes = (int) $partes[2];
                $anio = (int) $partes[3];
            }
            // Intentar formato YYYY-MM-DD
            else if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $fechaNacimiento, $partes)) {
                $anio = (int) $partes[1];
                $mes = (int) $partes[2];
                $dia = (int) $partes[3];
            }
            else {
                return 'Formato de fecha inválido';
            }

            // Validar fecha
            if (!checkdate($mes, $dia, $anio)) {
                return 'Fecha inválida';
            }

            $nacimiento = Carbon::createFromDate($anio, $mes, $dia);
            $hoy = Carbon::now();
            
            if ($nacimiento->isFuture()) {
                return 'Fecha futura';
            }

            $dias = $nacimiento->diffInDays($hoy);
            $años = $nacimiento->diffInYears($hoy);
            $mesesTotales = $nacimiento->diffInMonths($hoy);
            $mesesRestantes = $mesesTotales - ($años * 12);
            
            if ($dias < 30) {
                return "{$dias} días";
            } else if ($dias < 365) {
                return "{$mesesTotales} " . ($mesesTotales === 1 ? 'mes' : 'meses');
            } else {
                if ($mesesRestantes > 0) {
                    return "{$años} " . ($años === 1 ? 'año' : 'años') . " y {$mesesRestantes} " . ($mesesRestantes === 1 ? 'mes' : 'meses');
                }
                return "{$años} " . ($años === 1 ? 'año' : 'años');
            }
        } catch (\Exception $e) {
            return 'Error calculando edad';
        }
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
     * Boot del modelo para actualizar edad automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        // Actualizar edad cuando se crea o actualiza la fecha de nacimiento
        static::saved(function ($mascota) {
            if ($mascota->isDirty('fecha_nacimiento')) {
                $mascota->actualizarEdad();
            }
        });

        // Crear registro de edad cuando se crea la mascota
        static::created(function ($mascota) {
            if ($mascota->fecha_nacimiento) {
                $mascota->actualizarEdad();
            }
        });
    }

    public function procesosMedicos()
    {
        return $this->hasMany(ProcesoMedico::class);
    }

    // También sería útil un método para obtener procesos por categoría
    public function procesosPreventivos()
    {
        return $this->hasMany(ProcesoMedico::class)->where('categoria', 'preventivo');
    }

    public function procesosClinicos()
    {
        return $this->hasMany(ProcesoMedico::class)->where('categoria', 'clinico');
    }

    // NO cargar automáticamente la relación de edad para evitar conflictos
    // protected $with = ['caracteristicas', 'fotos'];
}