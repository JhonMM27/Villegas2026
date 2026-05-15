<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoVacacion extends Model
{
    protected $table = 'empleado_vacaciones';

    protected $fillable = [
        'empleado_id',
        'anio_generado',
        'dias_generados',
        'dias_tomados',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
    ];

    protected $casts = [
        'anio_generado' => 'integer',
        'dias_generados' => 'integer',
        'dias_tomados' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    protected $attributes = [
        'dias_generados' => 15,
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function getDiasPendientesAttribute(): int
    {
        return $this->dias_generados - $this->dias_tomados;
    }

    public function getEstaEnUsoAttribute(): bool
    {
        return $this->fecha_inicio !== null && $this->fecha_fin !== null;
    }

    public function scopePorEmpleado($query, int $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId);
    }

    public function scopeDelAnio($query, int $anio)
    {
        return $query->where('anio_generado', $anio);
    }
}