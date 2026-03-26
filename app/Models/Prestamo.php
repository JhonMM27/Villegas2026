<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    protected $table = 'prestamos';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'movimiento_tipo',
        'cliente_origen_id',
        'cliente_destino_id',
        'prestamo_referencia_id',
        'comprobante_tipo_codigo',
        'comprobante_tipo_nombre',
        'serie',
        'correlativo',
        'fecha_prestamo',
        'total',
        'estado',
        'rectificacion_count',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    // Quién entrega
    public function clienteOrigen()
    {
        return $this->belongsTo(Cliente::class, 'cliente_origen_id');
    }

    // Quién recibe
    public function clienteDestino()
    {
        return $this->belongsTo(Cliente::class, 'cliente_destino_id');
    }

    // Tipo de comprobante (SP, DP, IP, SD)
    public function comprobanteTipo()
    {
        return $this->belongsTo(
            ComprobanteTipo::class,
            'comprobante_tipo_codigo',
            'codigo'
        );
    }

    // Detalles del préstamo
    public function detalles()
    {
        return $this->hasMany(PrestamoDetalle::class, 'prestamo_id');
    }

    // Préstamo original (cuando es devolución)
    public function prestamoReferencia()
    {
        return $this->belongsTo(self::class, 'prestamo_referencia_id');
    }

    // Devoluciones asociadas a este préstamo
    public function devoluciones()
    {
        return $this->hasMany(self::class, 'prestamo_referencia_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes útiles
    |--------------------------------------------------------------------------
    */

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['registrada']);
    }

    public function scopePrestamos($query)
    {
        return $query->where('movimiento_tipo', 'PRESTAMO');
    }

    public function scopeDevoluciones($query)
    {
        return $query->where('movimiento_tipo', 'DEVOLUCION');
    }
}
