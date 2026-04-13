<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    protected $table = 'gastos';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'fecha_gasto',
        'descripcion',
        'responsable',
        'numero_recibo',
        'numero_interno',
        'tipo',
        'monto',
        'importe_p',
        'importe_d',
        'importe_c',
    ];

    protected $casts = [
        'fecha_gasto' => 'datetime',
        'monto'       => 'decimal:2',
        'importe_p'   => 'decimal:2',
        'importe_d'   => 'decimal:2',
        'importe_c'   => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
