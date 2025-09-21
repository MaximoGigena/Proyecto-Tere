<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotivoBaja extends Model
{
    use SoftDeletes;
    
    protected $table = 'motivos_baja';
    
    protected $fillable = [
        'descripcion',
        'activo'
    ];
    
    protected $casts = [
        'activo' => 'boolean'
    ];
    
    public function bajas()
    {
        return $this->hasMany(BajaMascota::class);
    }
}