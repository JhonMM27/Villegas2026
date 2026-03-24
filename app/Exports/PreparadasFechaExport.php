<?php

namespace App\Exports;

use App\Models\Preparada;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;

class PreparadasFechaExport implements FromCollection
{
    protected string $fechaInicio;
    protected string $fechaFin;

    public function __construct($fechaInicio, $fechaFin)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin    = $fechaFin;
    }

    public function collection()
    {
        $inicio = Carbon::parse($this->fechaInicio)->startOfDay();
        $fin    = Carbon::parse($this->fechaFin)->endOfDay();

        $reportes = Preparada::query()
            ->select([
                'id',
                'fecha',
                'formulacion_id',
                'producto_nombre',
                'producto_empaque',
                'cliente_nombre',
                'ingreso_kg',
                'ingreso_saco',
                'ingreso_soles',
                'items',
            ])
            ->whereBetween('fecha', [$inicio, $fin])
            ->where('estado', '!=', 'anulada')
            ->orderBy('producto_nombre')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        // Definimos el header UNA sola vez para mantener el mismo número de columnas siempre
        $header = [
            'Número',
            'Fecha',
            'Item(s)',
            'Fórmula',
            'Preparada',
            'Empaque',
            'Cliente',
            'Total Kg',
            'Total Sacos',
            'Total Soles',
        ];

        $rows = [];

        // Fila de título (mismo ancho que header)
        $rows[] = [
            'Fechas: del ' . $inicio->format('d/m/Y') . ' al ' . $fin->format('d/m/Y'),
            '', '', '', '', '', '', '', '',
            now()->format('d/m/Y H:i:s'),
        ];

        // Fila vacía
        $rows[] = array_fill(0, count($header), '');

        // Encabezados
        $rows[] = $header;

        $productoActual = null;

        $subIngKg = 0.0;
        $subIngSaco = 0.0;
        $subIngSol = 0.0;

        $totIngKg = 0.0;
        $totIngSaco = 0.0;
        $totIngSol = 0.0;

        foreach ($reportes as $r) {

            // Cambio de producto => imprime subtotal del producto anterior
            if ($productoActual !== null && $productoActual !== $r->producto_nombre) {
                $rows[] = [
                    '', '', '', '',
                    "TOTAL {$productoActual}",
                    '', '',
                    number_format($subIngKg, 2, '.', ''),
                    number_format($subIngSaco, 4, '.', ''),
                    number_format($subIngSol, 4, '.', ''),
                ];

                $subIngKg = $subIngSaco = $subIngSol = 0.0;
            }

            // Fila normal
            $rows[] = [
                $r->id,
                Carbon::parse($r->fecha)->format('d/m/Y H:i:s'), // incluye hora
                $r->items,
                $r->formulacion_id,
                $r->producto_nombre,
                $r->producto_empaque,
                $r->cliente_nombre,
                number_format((float)$r->ingreso_kg, 2, '.', ''),
                number_format((float)$r->ingreso_saco, 4, '.', ''),
                number_format((float)$r->ingreso_soles, 4, '.', ''),
            ];

            // Acumuladores
            $productoActual = $r->producto_nombre;

            $subIngKg   += (float)$r->ingreso_kg;
            $subIngSaco += (float)$r->ingreso_saco;
            $subIngSol  += (float)$r->ingreso_soles;

            $totIngKg   += (float)$r->ingreso_kg;
            $totIngSaco += (float)$r->ingreso_saco;
            $totIngSol  += (float)$r->ingreso_soles;
        }

        // Último subtotal
        if ($productoActual !== null) {
            $rows[] = [
                '', '', '', '',
                "TOTAL {$productoActual}",
                '', '',
                number_format($subIngKg, 2, '.', ''),
                number_format($subIngSaco, 4, '.', ''),
                number_format($subIngSol, 4, '.', ''),
            ];
        }

        // Total general
        $rows[] = [
            '', '', '', '',
            'TOTAL GENERAL',
            '', '',
            number_format($totIngKg, 2, '.', ''),
            number_format($totIngSaco, 4, '.', ''),
            number_format($totIngSol, 4, '.', ''),
        ];

        return collect($rows);
    }
}