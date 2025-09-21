<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactoVeterinario extends Model
{
    use HasFactory;

    protected $table = 'contacto_veterinario';

    protected $fillable = [
        'veterinario_id',
        'tipo',
        'valor'
    ];

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Veterinario::class);
    }
}