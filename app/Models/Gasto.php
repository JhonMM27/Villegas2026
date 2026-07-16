<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    protected $table = 'gastos';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'fecha_gasto',
        'descripcion',
        'responsable',
        'responsable_dni',
        'empleado_id',
        'categoria_gasto_id',
        'numero_recibo',
        'numero_interno',
        'gasto_tipo_id',
        'monto',
        'importe_p',
        'importe_d',
        'importe_c',
        'planilla_mes',
        'planilla_anio',
    ];

    protected $casts = [
        'fecha_gasto' => 'datetime',
        'monto' => 'decimal:2',
        'importe_p' => 'decimal:2',
        'importe_d' => 'decimal:2',
        'importe_c' => 'decimal:2',
        'planilla_mes' => 'integer',
        'planilla_anio' => 'integer',
        'empleado_id' => 'integer',
    ];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gastoTipo(): BelongsTo
    {
        return $this->belongsTo(GastoTipo::class, 'gasto_tipo_id');
    }

    public function categoriaGasto(): BelongsTo
    {
        return $this->belongsTo(GastoCategoria::class, 'categoria_gasto_id');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
