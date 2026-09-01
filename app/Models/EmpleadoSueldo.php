<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoSueldo extends Model
{
    protected $table = 'empleado_sueldos';

    protected $fillable = [
        'empleado_id',
        'sueldo_base',
        'sueldo_real',
        'sueldo_planilla',
        'vigente_desde',
        'vigente_hasta',
        'motivo',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'sueldo_base' => 'decimal:2',
        'sueldo_real' => 'decimal:2',
        'sueldo_planilla' => 'decimal:2',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getEmpleadoNombreAttribute(): string
    {
        return (string) ($this->empleado?->nombre ?? 'Empleado '.$this->empleado_id);
    }
}
