<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaEntrega extends Model
{
    use HasFactory;

    protected $table = 'venta_entregas';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'venta_id',
        'numero_recibo',
        'fecha_entrega',
        'estado',
        'comentario',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    // La entrega pertenece a una venta
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    // Usuario que registra la entrega
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Una entrega tiene muchos detalles
    public function detalles()
    {
        return $this->hasMany(VentaEntregaDetalle::class, 'venta_entrega_id');
    }
}