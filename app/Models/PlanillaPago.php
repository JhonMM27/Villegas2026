<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaPago extends Model
{
    protected $table = 'planilla_pagos';

    protected $fillable = [
        'empleado_id',
        'mes',
        'anio',
        'sueldo_base',
        'horas_extras',
        'adelantos',
        'dias_faltados',
        'descuento_faltas',
        'total_pagar',
        'fecha_pago',
        'estado',
        'observaciones',
        'importe_p',
        'importe_d',
        'importe_c',
    ];

    protected $casts = [
        'sueldo_base' => 'decimal:2',
        'horas_extras' => 'decimal:2',
        'adelantos' => 'decimal:2',
        'dias_faltados' => 'decimal:2',
        'descuento_faltas' => 'decimal:2',
        'total_pagar' => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PlanillaPagoDetalle::class);
    }

    public function adelantosRecords(): HasMany
    {
        return $this->hasMany(PlanillaAdelanto::class);
    }

    public function scopeDelMes($query, int $mes, int $anio)
    {
        return $query->where('mes', $mes)->where('anio', $anio);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function marcarComoPagado(): void
    {
        $this->estado = 'pagado';
        $this->fecha_pago = now()->toDateString();
        $this->save();
    }
}
