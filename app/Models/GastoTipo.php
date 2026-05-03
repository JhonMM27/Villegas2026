<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastoTipo extends Model
{
    protected $table = 'gasto_tipos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function gastos()
    {
        return $this->hasMany(Gasto::class, 'gasto_tipo_id');
    }
}