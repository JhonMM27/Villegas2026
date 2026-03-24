<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'documento_tipo_codigo',
        'documento_numero',
        'razon_social',
        'direccion',
        'telefono',
        'email',
        'representante',
        'representante_telefono',
        'cuenta_bancaria',
    ];

    public function documentoTipo()
    {
        return $this->belongsTo(DocumentoTipo::class, 'documento_tipo_codigo', 'codigo');
    }
}
