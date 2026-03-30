<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaPrestamo extends Model
{
    protected $table = 'planilla_prestamos';

    protected $fillable = [
        'empleado_id',
        'monto_original',
        'saldo_pendiente',
        'fecha_prestamo',
        'observaciones',
        'estado',
        'importe_p',
        'importe_d',
        'importe_c',
    ];

    protected $casts = [
        'monto_original' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'fecha_prestamo' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PlanillaPrestamoPago::class);
    }

    public function getTotalPagadoAttribute(): float
    {
        return (float) $this->pagos()->sum('monto_pagado');
    }

    public function actualizarSaldo(): void
    {
        $this->saldo_pendiente = (float) $this->monto_original - $this->total_pagado;

        if ($this->saldo_pendiente <= 0) {
            $this->estado = 'pagado';
            $this->saldo_pendiente = 0;
        }

        $this->save();
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorEmpleado($query, int $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId);
    }
}
