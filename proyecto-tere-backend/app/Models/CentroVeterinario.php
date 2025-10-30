<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentroVeterinario extends Model
{
    use HasFactory, softDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'centros_veterinarios';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'identificacion',
        'direccion',
        'telefono',
        'email',
        'especialidades_medicas',
        'logo_path',
        'matricula_establecimiento',
        'horarios_atencion',
        'web_redes_sociales',
        'estado',
        'fecha_aprobacion',
        'observaciones_aprobacion',
        'veterinario_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_aprobacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_aprobacion' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function veterinario()
    {
        return $this->belongsTo(Veterinario::class, 'veterinario_id');
    }

    /**
     * Scope para centros aprobados
     */
    public function scopeAprobados($query)
    {
        return $query->where('estado', 'aprobado');
    }

    /**
     * Scope para centros pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para centros rechazados
     */
    public function scopeRechazados($query)
    {
        return $query->where('estado', 'rechazado');
    }

    /**
     * Scope para búsqueda por nombre o identificación
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('identificacion', 'like', "%{$termino}%")
                    ->orWhere('email', 'like', "%{$termino}%");
    }

    /**
     * Scope para búsqueda por especialidad
     */
    public function scopePorEspecialidad($query, $especialidad)
    {
        return $query->where('especialidades_medicas', 'like', "%{$especialidad}%");
    }

    /**
     * Check if centro está aprobado
     */
    public function getEstaAprobadoAttribute(): bool
    {
        return $this->estado === 'aprobado';
    }

    /**
     * Check if centro está pendiente
     */
    public function getEstaPendienteAttribute(): bool
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Check if centro está rechazado
     */
    public function getEstaRechazadoAttribute(): bool
    {
        return $this->estado === 'rechazado';
    }

    /**
     * Get estado en texto legible
     */
    public function getEstadoTextoAttribute(): string
    {
        return match($this->estado) {
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            default => $this->estado,
        };
    }

    /**
     * Get URL del logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    /**
     * Get especialidades como array
     */
    public function getEspecialidadesArrayAttribute(): array
    {
        if (empty($this->especialidades_medicas)) {
            return [];
        }

        return array_map('trim', explode(',', $this->especialidades_medicas));
    }

    /**
     * Aprobar centro veterinario
     */
    public function aprobar(?string $observaciones = null): bool
    {
        $this->estado = 'aprobado';
        $this->fecha_aprobacion = now();
        $this->observaciones_aprobacion = $observaciones;
        
        return $this->save();
    }

    /**
     * Rechazar centro veterinario
     */
    public function rechazar(?string $observaciones = null): bool
    {
        $this->estado = 'rechazado';
        $this->fecha_aprobacion = null;
        $this->observaciones_aprobacion = $observaciones;
        
        return $this->save();
    }

    /**
     * Poner centro en estado pendiente
     */
    public function ponerPendiente(): bool
    {
        $this->estado = 'pendiente';
        $this->fecha_aprobacion = null;
        $this->observaciones_aprobacion = null;
        
        return $this->save();
    }

    /**
     * Validación para registrar un nuevo centro veterinario
     */
    public static function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'identificacion' => 'required|string|max:20|unique:centros_veterinarios,identificacion',
            'direccion' => 'required|string|max:500',
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:centros_veterinarios,email',
            'especialidades_medicas' => 'required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'matricula_establecimiento' => 'nullable|string|max:100',
            'horarios_atencion' => 'nullable|string|max:100',
            'web_redes_sociales' => 'nullable|string|max:255',
        ];
    }

    /**
     * Mensajes de validación
     */
    public static function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del centro es obligatorio.',
            'identificacion.required' => 'La identificación (CUIT/CUIL/DNI) es obligatoria.',
            'identificacion.unique' => 'Ya existe un centro registrado con esta identificación.',
            'direccion.required' => 'La dirección completa es obligatoria.',
            'telefono.required' => 'El teléfono de contacto es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Ya existe un centro registrado con este correo electrónico.',
            'especialidades_medicas.required' => 'Las especialidades médicas son obligatorias.',
            'logo.image' => 'El archivo debe ser una imagen válida.',
            'logo.max' => 'La imagen no debe pesar más de 2MB.',
        ];
    }

    /**
     * Get información de contacto completa
     */
    public function getInformacionContactoAttribute(): string
    {
        $info = "{$this->nombre}\n";
        $info .= "Tel: {$this->telefono}\n";
        $info .= "Email: {$this->email}\n";
        $info .= "Dirección: {$this->direccion}";
        
        if ($this->horarios_atencion) {
            $info .= "\nHorarios: {$this->horarios_atencion}";
        }
        
        if ($this->web_redes_sociales) {
            $info .= "\nWeb/Redes: {$this->web_redes_sociales}";
        }
        
        return $info;
    }

    /**
     * Check if tiene logo
     */
    public function getTieneLogoAttribute(): bool
    {
        return !empty($this->logo_path);
    }

    /**
     * Check if tiene matrícula
     */
    public function getTieneMatriculaAttribute(): bool
    {
        return !empty($this->matricula_establecimiento);
    }

    /**
     * Check if tiene horarios definidos
     */
    public function getTieneHorariosAttribute(): bool
    {
        return !empty($this->horarios_atencion);
    }

    /**
     * Check if tiene web/redes sociales
     */
    public function getTieneWebAttribute(): bool
    {
        return !empty($this->web_redes_sociales);
    }
}