<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprobanteTipo extends Model
{
    protected $table = 'comprobante_tipos';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'modulo',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'comprobate_tipo_codigo', 'codigo');
    }
}
