<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CobranzaTipo extends Model
{
    protected $table = 'cobranza_tipos';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
