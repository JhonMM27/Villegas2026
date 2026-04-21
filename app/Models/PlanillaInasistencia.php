<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaInasistencia extends Model
{
    protected $table = 'planilla_inasistencias';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'medio_dia',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'medio_dia' => 'boolean',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function scopePorEmpleado($query, int $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId);
    }

    public function scopeDelMes($query, int $mes, int $anio)
    {
        return $query->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio);
    }

    public function scopeDelRango($query, string $fechaInicio, string $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }
}
