<?php

namespace App\Exports;

use App\Models\Formulacion;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;

class FormulacionesRangoExport implements FromArray
{
    protected $numeroInicial;

    protected $numeroFinal;

    public function __construct($numeroInicial, $numeroFinal)
    {
        $this->numeroInicial = $numeroInicial;
        $this->numeroFinal = $numeroFinal;
    }

    public function array(): array
    {
        $rows = [];

        $formulaciones = Formulacion::with([
            'detalles:id,formulacion_id,producto_id,producto_nombre,producto_empaque,salida_kg',
        ])
            ->whereBetween('id', [$this->numeroInicial, $this->numeroFinal])
            ->orderBy('id')
            ->get();

        foreach ($formulaciones as $formulacion) {

            $fecha = $formulacion->fecha
                ? Carbon::parse($formulacion->fecha)->format('d/m/Y')
                : '';

            /* CABECERA */
            $rows[] = ["FORMULACIÓN Nº {$formulacion->id} — {$fecha} — Cliente: {$formulacion->cliente_nombre}"];
            $rows[] = ['Producto', 'Empaque', 'Salida Kg', 'Items'];

            $rows[] = [
                $formulacion->producto_nombre,
                $formulacion->producto_empaque,
                number_format((float) $formulacion->salida_kg, 2, '.', ''),
                $formulacion->detalles->count(),
            ];

            $rows[] = ['']; // línea en blanco

            /* DETALLE */
            $rows[] = ['ID', 'Producto', 'Empaque', 'Salida Kg'];

            $totalKg = 0;

            foreach ($formulacion->detalles as $detalle) {
                $totalKg += (float) $detalle->salida_kg;

                $rows[] = [
                    $detalle->producto_id,
                    $detalle->producto_nombre,
                    $detalle->producto_empaque,
                    number_format((float) $detalle->salida_kg, 2, '.', ''),
                ];
            }

            /* TOTALES */
            $rows[] = ['', 'TOTALES', '', number_format((float) $totalKg, 2, '.', '')];

            $rows[] = [''];
            $rows[] = [''];
        }

        return $rows;
    }
}
