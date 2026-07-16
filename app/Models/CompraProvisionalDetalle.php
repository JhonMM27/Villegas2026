<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraProvisionalDetalle extends Model
{
    use HasFactory;

    protected $table = 'compra_provisional_detalles';

    public $timestamps = false;

    protected $fillable = [
        'compra_provisional_id',
        'compra_id',
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

    public function compraProvisional()
    {
        return $this->belongsTo(CompraProvisional::class, 'compra_provisional_id');
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
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
