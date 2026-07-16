<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'proveedor_id',
        'proveedor_nombre',
        'comprobante_tipo_codigo',
        'comprobante_tipo_nombre',
        'pago_forma_codigo',
        'pago_forma_nombre',
        'serie',
        'correlativo',
        'fecha_compra',
        'fecha_vencimiento',
        'moneda',
        'op_gravada',
        'op_exonerada',
        'op_inafecta',
        'impuesto',
        'total',
        'importe_p',
        'importe_d',
        'importe_c',
        'acuenta',
        'abonos',
        'saldo',
        'estado',
        'rectificacion_count',
    ];

    protected $casts = [
        'fecha_compra' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'op_gravada' => 'decimal:2',
        'op_exonerada' => 'decimal:2',
        'op_inafecta' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'importe_p' => 'decimal:2',
        'importe_d' => 'decimal:2',
        'importe_c' => 'decimal:2',
        'acuenta' => 'decimal:2',
        'abonos' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /*
    protected $casts = [
        'fecha_venta' => 'datetime:Y-m-d H:i:s',
        'fecha_vencimiento' => 'datetime:Y-m-d H:i:s',
    ];
    */
    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function comprobanteTipo()
    {
        return $this->belongsTo(ComprobanteTipo::class, 'comprobante_tipo_codigo', 'codigo');
    }

    public function pagoForma()
    {
        return $this->belongsTo(PagoForma::class, 'pago_forma_codigo', 'codigo');
    }

    public function pagoMedio()
    {
        return $this->belongsTo(PagoMedio::class, 'pago_medio_id');
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class, 'compra_id');
    }

    public function pagosProvisionales()
    {
        return $this->hasMany(CompraProvisionalDetalle::class, 'compra_id');
    }
}
