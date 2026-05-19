<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CaracteristicasMascota;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use App\Traits\Auditable;

class Mascota extends Model
{
    use SoftDeletes, auditable;

    protected $fillable = [
        'nombre',
        'especie',
        'fecha_nacimiento', // Ahora es string dd/mm/yyyy
        'sexo',
        'castrado',
        'usuario_id',
        'edad_dias',
        'edad_meses',
        'edad_años',
        'edad_formateada',
        'ultima_actualizacion_edad',
    ];

    protected $casts = [
        'castrado' => 'boolean',
        'ultima_actualizacion_edad' => 'datetime',
        'fecha_nacimiento' => 'date',
    ];

    protected $updatingAge = false;

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

            // CALCULOS CORREGIDOS - usando intval para obtener números enteros
            $dias = $nacimiento->diffInDays($hoy);
            $años = $nacimiento->diffInYears($hoy); // Esto ya devuelve un entero
            $mesesTotales = $nacimiento->diffInMonths($hoy); // Esto ya devuelve un entero
            
            // Calcular meses restantes correctamente
            $mesesRestantes = $mesesTotales - ($años * 12);
            
            // Formatear según la edad
            if ($dias < 30) {
                return "{$dias} " . ($dias === 1 ? 'día' : 'días');
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
     * Accessor para obtener la foto principal con URLs optimizadas
     */
    public function getFotoPrincipalOptimizadaAttribute()
    {
        if ($this->fotos->isEmpty()) {
            return null;
        }

        $fotoPrincipal = $this->fotos->where('es_principal', true)->first();
        
        if (!$fotoPrincipal) {
            $fotoPrincipal = $this->fotos->first();
        }
        
        if (!$fotoPrincipal) {
            return null;
        }
        
        // Devolver la foto con sus URLs optimizadas
        return [
            'id' => $fotoPrincipal->id,
            'ruta_foto' => $fotoPrincipal->ruta_foto,
            'url' => $fotoPrincipal->url,
            'optimized_urls' => $fotoPrincipal->optimized_urls, // ✅ URLs optimizadas
            'es_principal' => $fotoPrincipal->es_principal
        ];
    }


    /**
     * Accessor para obtener todas las fotos con URLs optimizadas
     */
    public function getFotosConOptimizacionAttribute()
    {
        return $this->fotos->map(function($foto) {
            return [
                'id' => $foto->id,
                'ruta_foto' => $foto->ruta_foto,
                'url' => $foto->url,
                'optimized_urls' => $foto->optimized_urls, // ✅ URLs optimizadas
                'es_principal' => (bool) $foto->es_principal
            ];
        });
    }

    /**
     * Accessor para obtener la URL de la foto principal
     */
    // En app/Models/Mascota.php
    public function getFotoPrincipalUrlAttribute()
    {
        $foto = $this->foto_principal_optimizada;
        
        if (!$foto) {
            return null;
        }
        
        // 🔥 CAMBIO IMPORTANTE: Priorizar URL directa de storage primero
        if (isset($foto['url']) && $foto['url']) {
            // Asegurar que la URL sea completa
            $url = $foto['url'];
            if (!str_starts_with($url, 'http')) {
                $url = asset($url);
            }
            return $url;
        }
        
        // Si no hay URL, intentar construir desde ruta_foto
        if (isset($foto['ruta_foto']) && $foto['ruta_foto']) {
            $ruta = $foto['ruta_foto'];
            // Limpiar la ruta
            $ruta = ltrim($ruta, '/');
            if (!str_starts_with($ruta, 'storage/')) {
                $ruta = 'storage/' . $ruta;
            }
            return asset($ruta);
        }
        
        return null;
    }

    /**
     * Boot del modelo para actualizar edad automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        // SOLO un evento, y con protección contra recursión
        static::saved(function ($mascota) {
            // Solo actualizar si cambió la fecha Y no estamos ya actualizando
            if ($mascota->isDirty('fecha_nacimiento') && !$mascota->updatingAge) {
                $mascota->updatingAge = true;
                $mascota->actualizarEdad();
                $mascota->updatingAge = false;
            }
        });
        
        // NO necesitas 'created' porque 'saved' también se dispara al crear
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

    public function ofertasAdopcion()
    {
        return $this->hasMany(OfertaAdopcion::class, 'id_mascota');
    }

    // También puedes añadir un helper para verificar si está en adopción
    public function estaEnAdopcion()
    {
        return $this->ofertasAdopcion()
            ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
            ->exists();
    }

    /**
     * Obtener todos los tutores históricos
     */
    public function getTutoresHistoricosAttribute()
    {
        $transferencias = $this->transferencias;
        $tutoresIds = collect();
        
        foreach ($transferencias as $transferencia) {
            $tutoresIds->push($transferencia->tutor_anterior_id);
            $tutoresIds->push($transferencia->tutor_nuevo_id);
        }
        
        // Agregar tutor actual
        $tutoresIds->push($this->usuario_id);
        
        return Usuario::whereIn('id', $tutoresIds->unique())->get();
    }

    /**
     * Obtener tutor actual
     */
    public function tutorActual()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Verificar si ha sido adoptada
     */
    public function getHaSidoAdoptadaAttribute(): bool
    {
        return $this->transferencias()
            ->where('motivo', 'adopcion')
            ->exists();
    }

    /**
     * Obtener fecha de última adopción
     */
    public function getFechaUltimaAdopcionAttribute()
    {
        $ultimaTransferencia = $this->transferencias()
            ->where('motivo', 'adopcion')
            ->latest('fecha_transferencia')
            ->first();
        
        return $ultimaTransferencia->fecha_transferencia ?? null;
    }

    public function getCastradoAttribute($value)
    {
        return $value === null ? null : (bool) $value;
    }

    public function ubicacion()
    {
        return $this->hasOne(UbicacionUsuario::class, 'mascota_id');
    }

    // En App\Models\Mascota
    public function ubicacionUsuario()
    {
        // Obtener la ubicación a través del usuario responsable
        return $this->hasOneThrough(
            UbicacionUsuario::class,
            Usuario::class,
            'id', // Foreign key en Usuario
            'user_id', // Foreign key en UbicacionUsuario
            'usuario_id', // Local key en Mascota
            'id' // Local key en Usuario
        );
    }

    /**
     * Obtener la ubicación del tutor actual
     */
    public function getUbicacionTutorAttribute()
    {
        if (!$this->usuario || !$this->usuario->user) {
            return null;
        }
        
        return $this->usuario->user->ubicacionActual;
    }

    /**
     * Obtener coordenadas del tutor
     */
    public function getCoordenadasTutorAttribute()
    {
        $ubicacion = $this->ubicacion_tutor;
        
        if ($ubicacion && $ubicacion->latitude && $ubicacion->longitude) {
            return [
                'lat' => $ubicacion->latitude,
                'lon' => $ubicacion->longitude,
                'city' => $ubicacion->city,
                'state' => $ubicacion->state,
            ];
        }
        
        return null;
    }

    // Accessor para obtener ubicación formateada
    public function getUbicacionTextoAttribute()
    {
        if ($this->usuario && $this->usuario->ubicacionActual) {
            $ubicacion = $this->usuario->ubicacionActual;
            $parts = [];
            
            if ($ubicacion->city) $parts[] = $ubicacion->city;
            if ($ubicacion->state && $ubicacion->state !== $ubicacion->city) {
                $parts[] = $ubicacion->state;
            }
            if ($ubicacion->country) $parts[] = $ubicacion->country;
            
            return implode(', ', $parts);
        }
        
        return 'Ubicación no disponible';
    }

    public function user()
    {
        // Si usuario_id es ID de Usuario, NO de User
        // Necesitas obtener el User a través de Usuario
        return $this->belongsTo(User::class, 'usuario_id', 'id')
            ->where('userable_type', 'App\Models\Usuario');
    }

    /**
     * Obtener tutor en una fecha específica
     */
    public function tutorEnFecha($fecha)
    {
        $fechaCarbon = Carbon::parse($fecha);
        
        // Buscar transferencias después de esta fecha
        $transferenciaPosterior = $this->transferencias()
            ->where('fecha_transferencia', '>', $fechaCarbon)
            ->orderBy('fecha_transferencia', 'asc')
            ->first();
        
        if ($transferenciaPosterior) {
            // El tutor antes de esta transferencia es el tutor_anterior
            return Usuario::find($transferenciaPosterior->tutor_anterior_id);
        }
        
        // Si no hay transferencias posteriores, el tutor actual es el dueño
        return $this->usuario;
    }


    /**
     * Relación con el historial de transferencias
     */
    public function transferencias()
    {
        return $this->hasMany(HistorialTransferenciaMascota::class, 'mascota_id')
                    ->with(['tutorAnterior', 'tutorNuevo'])
                    ->orderBy('fecha_transferencia', 'desc');
    }

    /**
     * Obtener historial completo de tutores con formato para el frontend
     */
    public function getHistorialTutoresAttribute()
    {
        return $this->historialTutoresCompleto();
    }

    /**
     * Obtener el historial completo de tutores con fechas precisas
     * (Este método ya existe en tu modelo, solo asegúrate de que esté correcto)
     */
    public function historialTutoresCompleto()
    {
        // Obtener todas las transferencias ordenadas por fecha
        $transferencias = $this->transferencias()
            ->with(['tutorAnterior', 'tutorNuevo'])
            ->orderBy('fecha_transferencia', 'asc')
            ->get();
        
        $historial = collect();
        
        // Si no hay transferencias, solo está el tutor actual
        if ($transferencias->isEmpty()) {
            $historial->push([
                'id' => uniqid(), // ID temporal para el frontend
                'usuario_id' => $this->usuario_id,
                'nombre' => $this->usuario->nombre ?? 'Usuario desconocido',
                'foto' => $this->usuario->foto_principal_url ?? null,
                'adopcion' => $this->created_at->format('d/m/Y'),
                'desligo' => 'Presente',
                'es_actual' => true,
                'es_primer_tutor' => true,
                'motivo' => null,
                'contactable' => false,
                'medios_contacto' => []
            ]);
            
            return $historial;
        }
        
        // Procesar la primera transferencia para obtener el tutor original
        $primeraTransferencia = $transferencias->first();
        
        // Tutor original (anterior a la primera transferencia)
        $historial->push([
            'id' => uniqid() . '_1',
            'usuario_id' => $primeraTransferencia->tutor_anterior_id,
            'nombre' => $primeraTransferencia->tutorAnterior->nombre ?? 'Usuario desconocido',
            'foto' => $primeraTransferencia->tutorAnterior->foto_principal_url ?? null,
            'adopcion' => $this->created_at->format('d/m/Y'),
            'desligo' => $primeraTransferencia->fecha_transferencia->format('d/m/Y'),
            'es_actual' => false,
            'es_primer_tutor' => true,
            'motivo' => $primeraTransferencia->motivo,
            'contactable' => false,
            'medios_contacto' => []
        ]);
        
        // Procesar transferencias intermedias
        foreach ($transferencias as $index => $transferencia) {
            $siguienteTransferencia = $transferencias->get($index + 1);
            
            $esActual = $transferencia->tutor_nuevo_id == $this->usuario_id;
            
            $historial->push([
                'id' => uniqid() . '_' . ($index + 2),
                'usuario_id' => $transferencia->tutor_nuevo_id,
                'nombre' => $transferencia->tutorNuevo->nombre ?? 'Usuario desconocido',
                'foto' => $transferencia->tutorNuevo->foto_principal_url ?? null,
                'adopcion' => $transferencia->fecha_transferencia->format('d/m/Y'),
                'desligo' => $siguienteTransferencia 
                    ? $siguienteTransferencia->fecha_transferencia->format('d/m/Y')
                    : ($esActual ? 'Presente' : 'Desconocida'),
                'es_actual' => $esActual,
                'es_primer_tutor' => false,
                'motivo' => $transferencia->motivo,
                'contactable' => false,
                'medios_contacto' => []
            ]);
        }
        
        return $historial;
    }
    
    /**
     * Actualizar la edad basada en la fecha de nacimiento
     */
    /**
     * Actualizar la edad basada en la fecha de nacimiento - CORREGIDO
     */
    public function actualizarEdad(): void
    {
        if (!$this->fecha_nacimiento) {
            $this->updateQuietly([  // 👈 CAMBIAR update() por updateQuietly()
                'edad_dias' => null,
                'edad_meses' => null,
                'edad_años' => null,
                'edad_formateada' => null,
                'ultima_actualizacion_edad' => now()
            ]);
            return;
        }

        try {
            // Manejar el formato de fecha correctamente
            $fechaStr = $this->fecha_nacimiento;
            
            // Si es string en formato d/m/Y, convertirlo
            if (is_string($fechaStr) && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fechaStr)) {
                $partes = explode('/', $fechaStr);
                $fechaStr = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
            }
            
            $nacimiento = Carbon::parse($fechaStr);
            
            if ($nacimiento->isFuture()) {
                $this->updateQuietly([
                    'edad_dias' => null,
                    'edad_meses' => null,
                    'edad_años' => null,
                    'edad_formateada' => 'Fecha futura',
                    'ultima_actualizacion_edad' => now()
                ]);
                return;
            }

            $hoy = Carbon::now();
            
            // Calcular edades
            $dias = (int) $nacimiento->diffInDays($hoy);
            $años = (int) $nacimiento->diffInYears($hoy);
            $mesesTotales = (int) $nacimiento->diffInMonths($hoy);
            $mesesRestantes = $mesesTotales - ($años * 12);

            $edadFormateada = $this->formatearEdad($dias, $mesesTotales, $años, $mesesRestantes);

            // 👈 USAR updateQuietly() para NO disparar eventos
            $this->updateQuietly([
                'edad_dias' => $dias,
                'edad_meses' => $mesesTotales,
                'edad_años' => $años,
                'edad_formateada' => $edadFormateada,
                'ultima_actualizacion_edad' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculando edad mascota:', [
                'mascota_id' => $this->id,
                'fecha_nacimiento' => $this->fecha_nacimiento,
                'error' => $e->getMessage()
            ]);
            
            $this->updateQuietly([
                'edad_dias' => null,
                'edad_meses' => null,
                'edad_años' => null,
                'edad_formateada' => 'Error calculando edad',
                'ultima_actualizacion_edad' => now()
            ]);
        }
    }

     /**
     * Formatear la edad para mostrar
     */
    private function formatearEdad(int $dias, int $mesesTotales, int $años, int $mesesRestantes): string
    {
        if ($dias < 30) {
            return "{$dias} " . ($dias === 1 ? 'día' : 'días');
        } else if ($dias < 365) {
            return "{$mesesTotales} " . ($mesesTotales === 1 ? 'mes' : 'meses');
        } else {
            if ($mesesRestantes > 0) {
                return "{$años} " . ($años === 1 ? 'año' : 'años') . " y {$mesesRestantes} " . ($mesesRestantes === 1 ? 'mes' : 'meses');
            }
            return "{$años} " . ($años === 1 ? 'año' : 'años');
        }
    }

    /**
     * Verificar si necesita actualización (cada 30 días)
     */
    public function necesitaActualizacionEdad(): bool
    {
        return !$this->ultima_actualizacion_edad || 
               $this->ultima_actualizacion_edad->diffInDays(now()) >= 30;
    }

    /**
     * Accessor para obtener la edad formateada
     */
    public function getEdadFormateadaAttribute($value)
    {
        // Si ya hay valor y no necesita actualización, devolverlo
        if ($value && !$this->necesitaActualizacionEdad()) {
            return $value;
        }
        
        // Si necesita actualización y no estamos ya actualizando
        if ($this->fecha_nacimiento && !$this->updatingAge) {
            $this->updatingAge = true;
            $this->actualizarEdad();
            $this->refresh(); // Recargar el modelo
            $this->updatingAge = false;
            return $this->edad_formateada;
        }
        
        return $value ?? 'Edad no disponible';
    }

    public function setFechaNacimientoAttribute($value)
    {
        // Si ya está en formato Y-m-d, guardar así
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->attributes['fecha_nacimiento'] = $value;
            return;
        }
        
        // Convertir de d/m/Y a Y-m-d
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            $dia = $matches[1];
            $mes = $matches[2];
            $anio = $matches[3];
            
            // Validar fecha
            if (checkdate($mes, $dia, $anio)) {
                $this->attributes['fecha_nacimiento'] = "{$anio}-{$mes}-{$dia}";
                return;
            }
        }
        
        // Si no se pudo convertir, guardar null
        Log::warning('Formato de fecha no válido para mascota:', ['fecha' => $value]);
        $this->attributes['fecha_nacimiento'] = null;
    }

    public function getFechaNacimientoAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        // Devolver en formato d/m/Y para el frontend
        return Carbon::parse($value)->format('d/m/Y');
    }

    public function fichaMedica(): HasOne
    {
        return $this->hasOne(FichaMedica::class);
    }

    // Método helper para obtener la ficha médica completa
    public function getFichaMedicaCompletaAttribute()
    {
        if (!$this->fichaMedica) {
            // Crear ficha médica por defecto si no existe
            $this->fichaMedica()->create();
            $this->load('fichaMedica');
        }
        
        return $this->fichaMedica;
    }
}