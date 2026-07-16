<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoTipo extends Model
{
    protected $table = 'documento_tipos';

    protected $primaryKey = 'codigo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'documento_tipo_codigo', 'codigo');
    }
}
