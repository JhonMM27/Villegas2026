<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AfectacionTipo extends Model
{
    protected $table = 'afectacion_tipos';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'letra',
        'porcentaje',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'afectacion_tipo_codigo', 'codigo');
    }
}
