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
        'empleado_sueldo_id',
        'mes',
        'anio',
        'sueldo_base',
        'horas_extras',
        'adelantos',
        'dias_faltados',
        'descuento_faltas',
        'cts_planilla',
        'cts_sueldo_real',
        'total_pagar',
        'fecha_pago',
        'fecha_pago_original',
        'estado',
        'anulado_at',
        'anulado_por',
        'motivo_anulacion',
        'observaciones',
        'importe_p',
        'importe_d',
        'importe_c',
    ];

    protected $casts = [
        'mes' => 'integer',
        'anio' => 'integer',
        'sueldo_base' => 'decimal:2',
        'horas_extras' => 'decimal:2',
        'adelantos' => 'decimal:2',
        'dias_faltados' => 'decimal:2',
        'descuento_faltas' => 'decimal:2',
        'total_pagar' => 'decimal:2',
        'fecha_pago' => 'date',
        'fecha_pago_original' => 'date',
        'anulado_at' => 'datetime',
        'anulado_por' => 'integer',
    ];

    public function getSueldoBaseContractualAttribute(): string
    {
        return $this->sueldoAplicado?->sueldo_base ?? '0.00';
    }

    public function getSueldoRealHistoricoAttribute(): string
    {
        return $this->sueldoAplicado?->sueldo_real ?? '0.00';
    }

    public function getSueldoPlanillaHistoricoAttribute(): string
    {
        return $this->sueldoAplicado?->sueldo_planilla ?? '0.00';
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function sueldoAplicado(): BelongsTo
    {
        return $this->belongsTo(EmpleadoSueldo::class, 'empleado_sueldo_id');
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

    public function scopeNoAnulados($query)
    {
        return $query->where('estado', '!=', 'anulado');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(PlanillaPagoMovimiento::class);
    }
}
