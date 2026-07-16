<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formulacion extends Model
{
    // use HasFactory;

    protected $table = 'formulaciones';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'fecha',
        'producto_id',
        'producto_nombre',
        'producto_empaque',
        'cliente_id',
        'cliente_nombre',
        'salida_kg',
        'activo',
    ];

    protected $casts = [
        // 'fecha' => 'datetime',
        'activo' => 'boolean',
        'salida_kg' => 'decimal:2',
    ];

    // Relaciones

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function detalles()
    {
        return $this->hasMany(FormulacionDetalle::class, 'formulacion_id');
    }
}
