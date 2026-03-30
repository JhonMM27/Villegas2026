<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaPrestamoPago extends Model
{
    protected $table = 'planilla_prestamo_pagos';

    protected $fillable = [
        'planilla_prestamo_id',
        'monto_pagado',
        'fecha_pago',
        'observaciones',
        'importe_p',
        'importe_d',
        'importe_c',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(PlanillaPrestamo::class, 'planilla_prestamo_id');
    }
}
