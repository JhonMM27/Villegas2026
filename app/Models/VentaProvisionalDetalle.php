<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaProvisionalDetalle extends Model
{
    use HasFactory;

    protected $table = 'venta_provisional_detalles';

    public $timestamps = false;

    protected $fillable = [
        'venta_provisional_id',
        'venta_id',
        'comprobante_tipo_codigo',
        'serie',
        'correlativo',
        'monto',
        'comentario',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function ventaProvisional()
    {
        return $this->belongsTo(VentaProvisional::class, 'venta_provisional_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES ÚTILES (opcional pero recomendado)
    |--------------------------------------------------------------------------
    */

    public function scopeMonto($query)
    {
        return $query->where('monto', '>', 0);
    }
}
