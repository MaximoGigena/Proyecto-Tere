<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Denuncia extends Model
{
    use HasFactory;

    protected $table = 'denuncias';

    protected $fillable = [
        'usuario_id',
        'mascota_id',
        'usuario_denunciado_id',
        'id_oferta',
        'categoria',
        'subcategoria',
        'descripcion',
        'estado',
        'notas_admin',
        'resuelta_en'
    ];

    protected $casts = [
        'resuelta_en' => 'datetime',
    ];

    // Especificar la llave primaria personalizada si es necesario
    protected $primaryKey = 'id';
    

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function usuarioDenunciado()
    {
        return $this->belongsTo(User::class, 'usuario_denunciado_id');
    }

    public function mascota()
    {
        return $this->belongsTo(Mascota::class);
    }

    public function oferta()
    {
        // Especificar la columna foránea si es diferente
        return $this->belongsTo(OfertaAdopcion::class, 'id_oferta', 'id_oferta');
    }

    // Constantes para categorías
    const CATEGORIAS = [
        'Maltrato Animal',
        'Perfil falso',
        'Contenido inapropiado',
        'Estafa o uso comercial',
        'Mascota ilegal'
    ];

    const SUBCATEGORIAS = [
        'Maltrato Animal' => [
            'Heridas visibles',
            'Condiciones insalubres',
            'Violencia en fotos/videos',
            'Negligencia',
            'Abandono',
            'Explotación',
            'Otro'
        ],
        'Perfil falso' => [
            'Fotos robadas',
            'Fotos/Videos generadas por IA',
            'Información falsa',
            'Oferta sospechosa',
            'Otro'
        ],
        'Contenido inapropiado' => [
            'Lenguaje ofensivo',
            'Contenido sexual',
            'Violencia explícita',
            'Discriminación',
            'Otro'
        ],
        'Estafa o uso comercial' => [
            'Venta encubierta',
            'Publicidad engañosa',
            'Cobro por servicios falsos',
            'Intento de fraude',
            'Otro'
        ],
        'Mascota ilegal' => [
            'Especie prohibida',
            'Falta de permisos',
            'Tráfico ilegal',
            'Otro'
        ]
    ];
}