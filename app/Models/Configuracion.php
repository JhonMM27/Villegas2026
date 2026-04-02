<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];

    public static function get(string $clave, $default = null)
    {
        $registro = static::where('clave', $clave)->first();

        return $registro ? $registro->valor : $default;
    }

    public static function set(string $clave, string $valor): bool
    {
        $registro = static::where('clave', $clave)->first();
        if ($registro) {
            $registro->update(['valor' => $valor]);

            return true;
        }

        return false;
    }

    public static function allAsArray(): array
    {
        return static::all()->pluck('valor', 'clave')->toArray();
    }
}
