<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaProvisional extends Model
{
    use HasFactory;

    protected $table = 'venta_provisionales';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'numero_recibo',
        'numero_interno',
        'fecha_provisional',
        'cliente_id',
        'cliente_nombre',
        'monto',
        'libre',
        'tipo',
        'importe_p',
        'importe_d',
        'importe_c',
    ];

    protected $casts = [
        'fecha_provisional' => 'datetime:Y-m-d H:i:s',
        'monto' => 'decimal:2',
        'libre' => 'decimal:2',
        'importe_p' => 'decimal:2',
        'importe_d' => 'decimal:2',
        'importe_c' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(VentaProvisionalDetalle::class, 'venta_provisional_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES ÚTILES (opcional pero recomendado)
    |--------------------------------------------------------------------------
    */

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_provisional', [$desde, $hasta]);
    }

    public function scopeMonto($query)
    {
        return $query->where('monto', '>', 0);
    }
}
