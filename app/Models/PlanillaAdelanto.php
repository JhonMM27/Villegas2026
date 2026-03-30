<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaAdelanto extends Model
{
    protected $table = 'planilla_adelantos';

    protected $fillable = [
        'empleado_id',
        'monto',
        'fecha',
        'planilla_pago_id',
        'observaciones',
        'importe_p',
        'importe_d',
        'importe_c',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function planillaPago(): BelongsTo
    {
        return $this->belongsTo(PlanillaPago::class, 'planilla_pago_id');
    }

    public function scopeDelMes($query, int $mes, int $anio)
    {
        return $query->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio);
    }

    public function scopePorEmpleado($query, int $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId);
    }
}
