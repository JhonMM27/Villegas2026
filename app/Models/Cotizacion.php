<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'cliente_id',
        'cliente_nombre',
        'items',
        'comprobante_tipo_codigo',
        'comprobante_tipo_nombre',
        'serie',
        'correlativo',
        'fecha_cotizacion',
        'pago_forma_codigo',
        'pago_forma_nombre',
        'moneda',
        'op_gravada',
        'op_exonerada',
        'op_inafecta',
        'impuesto',
        'total',
        'estado',
        'venta_id',
    ];

    protected $casts = [
        'fecha_cotizacion' => 'datetime:Y-m-d H:i:s',
        'op_gravada'        => 'decimal:2',
        'op_exonerada'      => 'decimal:2',
        'op_inafecta'       => 'decimal:2',
        'impuesto'          => 'decimal:2',
        'total'             => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function comprobanteTipo()
    {
        return $this->belongsTo(ComprobanteTipo::class, 'comprobante_tipo_codigo', 'codigo');
    }

    // Forma de pago
    public function pagoForma()
    {
        return $this->belongsTo(
            PagoForma::class,
            'pago_forma_codigo',
            'codigo'
        );
    }

    public function detalles()
    {
        return $this->hasMany(CotizacionDetalle::class, 'cotizacion_id');
    }
}
