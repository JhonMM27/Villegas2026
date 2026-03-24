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
        'estado'
    ];
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
