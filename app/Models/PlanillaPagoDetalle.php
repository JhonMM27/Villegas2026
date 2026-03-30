<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaPagoDetalle extends Model
{
    protected $table = 'planilla_pago_detalles';

    protected $fillable = [
        'planilla_pago_id',
        'concepto',
        'monto',
        'tipo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function planillaPago(): BelongsTo
    {
        return $this->belongsTo(PlanillaPago::class);
    }

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'ingreso');
    }

    public function scopeDescuentos($query)
    {
        return $query->where('tipo', 'descuento');
    }
}
