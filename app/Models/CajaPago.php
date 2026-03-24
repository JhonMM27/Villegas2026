<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaPago extends Model
{
    protected $table = 'caja_pagos';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'movimiento_tipo',
        'numero_recibo',
        'numero_interno',
        'fecha_caja',
        'situacion',
        'entrada',
        'salida',
        'saldo_anterior',
        'saldo',
        'acuenta',
        'contado',
        'comentario',
        'pago_forma_codigo',
        'pago_forma_nombre',
        'persona_tipo',
        'persona_id',
        'persona_nombre',
        'comprobante_tipo_codigo',
        'comprobante_tipo_nombre',
        'serie',
        'correlativo',
        'cobranza_tipo_id',
        'cobranza_tipo_nombre',
        'provisional',
        'diferencia',
        'importe1',
        'importe2',
        'importe3',
        'total',
    ];

    protected $casts = [
        'fecha_caja' => 'datetime',

        'entrada' => 'decimal:2',
        'salida' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo' => 'decimal:2',
        'acuenta' => 'decimal:2',
        'contado' => 'decimal:2',
        'provisional' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'importe1' => 'decimal:2',
        'importe2' => 'decimal:2',
        'importe3' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cobranzaTipo()
    {
        return $this->belongsTo(CobranzaTipo::class);
    }
}
