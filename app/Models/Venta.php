<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

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
        'docpagoi',
        'fecha_venta',
        'pago_forma_codigo',
        'pago_forma_nombre',
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
        'rentabilidad',
        'estado',
        'rectificacion_count',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime:Y-m-d H:i:s',
        'fecha_vencimiento' => 'datetime:Y-m-d',
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
        'rentabilidad' => 'decimal:2',
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
        return $this->hasMany(VentaDetalle::class, 'venta_id');
    }

    public function pagosProvisionales()
    {
        return $this->hasMany(VentaProvisionalDetalle::class, 'venta_id');
    }

    public function entregas()
    {
        return $this->hasMany(VentaEntrega::class, 'venta_id');
    }
}
