<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprobanteSerie extends Model
{
    protected $table = 'comprobante_series';

    protected $fillable = [
        'comprobante_tipo_codigo',
        'serie',
        'correlativo'
    ];

    public function tipo()
    {
        return $this->belongsTo(ComprobanteTipo::class, 'comprobante_tipo_codigo', 'codigo');
    }
}
