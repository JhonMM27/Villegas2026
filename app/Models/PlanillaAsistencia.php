<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaAsistencia extends Model
{
    protected $table = 'planilla_asistencias';

    protected $fillable = [
        'empleado_id',
        'anio',
        'mes',
        'dias_faltados',
    ];

    protected $casts = [
        'dias_faltados' => 'decimal:2',
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
        return $query->where('mes', $mes)->where('anio', $anio);
    }
}
