<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperacionTipo extends Model
{
    use HasFactory;

    protected $table = 'operacion_tipos';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    /**
     * Scope para obtener solo tipos de operación activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener tipos de operación para select
     */
    public static function getForSelect()
    {
        return self::activos()
            ->orderBy('codigo')
            ->pluck('descripcion', 'codigo');
    }
}
