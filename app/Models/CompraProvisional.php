<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraProvisional extends Model
{
    use HasFactory;

    protected $table = 'compra_provisionales';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'numero_recibo',
        'numero_interno',
        'fecha_provisional',
        'proveedor_id',
        'proveedor_nombre',
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

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles()
    {
        return $this->hasMany(CompraProvisionalDetalle::class, 'compra_provisional_id');
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
