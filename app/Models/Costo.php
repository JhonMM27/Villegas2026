<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Costo extends Model
{
    protected $table = 'costos';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'fecha_costo',
        'descripcion',
        'responsable',
        'responsable_dni',
        'numero_recibo',
        'numero_interno',
        'categoria_costo_id',
        'costo_tipo_id',
        'monto',
        'importe_p',
        'importe_d',
        'importe_c',
    ];

    protected $casts = [
        'fecha_costo' => 'datetime',
        'monto' => 'decimal:2',
        'importe_p' => 'decimal:2',
        'importe_d' => 'decimal:2',
        'importe_c' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function costoTipo()
    {
        return $this->belongsTo(CostoTipo::class, 'costo_tipo_id');
    }

    public function categoriaCosto()
    {
        return $this->belongsTo(CostoCategoria::class, 'categoria_costo_id');
    }
}
