<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivosTerapia extends Model
{
    protected $fillable = ['nombre', 'ruta', 'tipo', 'archivable_id', 'archivable_type'];

    public function archivable()
    {
        return $this->morphTo();
    }
}