<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'nombre',
        'dni',
        'telefono',
        'correo',
        'sueldo_planilla',
        'sueldo_real',
        'observaciones',
        'estado',
        'fecha_ingreso',
        'fecha_salida',
    ];

    protected $casts = [
        'sueldo_planilla' => 'decimal:2',
        'sueldo_real' => 'decimal:2',
        'fecha_ingreso' => 'date',
        'fecha_salida' => 'date',
    ];

    public function getEstaActivoAttribute(): bool
    {
        return is_null($this->fecha_salida) && $this->estado === 'activo';
    }

    public function adelantos(): HasMany
    {
        return $this->hasMany(PlanillaAdelanto::class);
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(PlanillaPrestamo::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PlanillaPago::class);
    }

    public function vacaciones(): HasMany
    {
        return $this->hasMany(\App\Models\EmpleadoVacacion::class);
    }

    public function getAnosServicioAttribute(): int
    {
        if (! $this->fecha_ingreso) {
            return 0;
        }

        return (int) $this->fecha_ingreso->diffInYears(now());
    }

    public function getEsElegibleVacacionesAttribute(): bool
    {
        return $this->anos_servicio >= 1;
    }

    public function getDisponibleAdelantoAttribute(): float
    {
        $totalAdelantado = $this->adelantos()
            ->whereYear('fecha', now()->year)
            ->whereMonth('fecha', now()->month)
            ->sum('monto');

        return (float) $this->sueldo_real - (float) $this->sueldo_planilla - (float) $totalAdelantado;
    }

    public function getSaldoDisponibleAttribute(): float
    {
        return max(0, $this->disponible_adelanto);
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
            ->orWhere('dni', 'like', "%{$termino}%");
    }
}
