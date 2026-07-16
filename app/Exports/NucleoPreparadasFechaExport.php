<?php

namespace App\Exports;

use App\Models\NucleoPreparada;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;

class NucleoPreparadasFechaExport implements FromCollection
{
    protected string $fechaInicio;

    protected string $fechaFin;

    public function __construct($fechaInicio, $fechaFin)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    /**
     * Retorna la colección de datos detallada por fecha formateada para el Excel.
     */
    public function collection()
    {
        $inicio = Carbon::parse($this->fechaInicio)->startOfDay();
        $fin = Carbon::parse($this->fechaFin)->endOfDay();

        $reportes = NucleoPreparada::query()
            ->select([
                'id',
                'fecha',
                'items',
                'nucleo_id',
                'nucleo_nombre',
                'producto_empaque',
                'ingreso_kg',
                'ingreso_saco',
                'ingreso_soles',
            ])
            ->whereBetween('fecha', [$inicio, $fin])
            ->where('estado', '!=', 'anulada')
            ->orderBy('nucleo_nombre')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $header = [
            'ID Registro',
            'Fecha y Hora',
            'Item(s)',
            'ID Núcleo',
            'Nombre del Núcleo',
            'Empaque',
            'Total Kg',
            'Total Sacos',
            'Total Soles',
        ];

        $rows = [];

        // Fila de título y Fecha del reporte
        $rows[] = [
            'Reporte de Núcleos Preparados por Fecha',
            '', '', '', '', '', '', '',
            now()->format('d/m/Y H:i:s'),
        ];
        $rows[] = [
            'Rango seleccionado: del '.$inicio->format('d/m/Y').' al '.$fin->format('d/m/Y'),
        ];

        // Fila vacía
        $rows[] = array_fill(0, count($header), '');

        // Encabezados
        $rows[] = $header;

        $nucleoActual = null;
        $subIngKg = 0.0;
        $subIngSaco = 0.0;
        $subIngSol = 0.0;

        $totIngKg = 0.0;
        $totIngSaco = 0.0;
        $totIngSol = 0.0;

        foreach ($reportes as $r) {
            // Subtotales por Núcleo al cambiar de tipo de núcleo
            if ($nucleoActual !== null && $nucleoActual !== $r->nucleo_nombre) {
                $rows[] = [
                    '', '', '', '',
                    "SUBTOTAL {$nucleoActual}",
                    '',
                    number_format($subIngKg, 4, '.', ''),
                    number_format($subIngSaco, 4, '.', ''),
                    number_format($subIngSol, 4, '.', ''),
                ];
                $subIngKg = $subIngSaco = $subIngSol = 0.0;
            }

            // Fila de datos
            $rows[] = [
                $r->id,
                Carbon::parse($r->fecha)->format('d/m/Y H:i:s'),
                $r->items,
                $r->nucleo_id,
                $r->nucleo_nombre,
                $r->producto_empaque,
                number_format((float) $r->ingreso_kg, 4, '.', ''),
                number_format((float) $r->ingreso_saco, 4, '.', ''),
                number_format((float) $r->ingreso_soles, 4, '.', ''),
            ];

            $nucleoActual = $r->nucleo_nombre;
            $subIngKg += (float) $r->ingreso_kg;
            $subIngSaco += (float) $r->ingreso_saco;
            $subIngSol += (float) $r->ingreso_soles;

            $totIngKg += (float) $r->ingreso_kg;
            $totIngSaco += (float) $r->ingreso_saco;
            $totIngSol += (float) $r->ingreso_soles;
        }

        // Último subtotal
        if ($nucleoActual !== null) {
            $rows[] = [
                '', '', '', '',
                "SUBTOTAL {$nucleoActual}",
                '',
                number_format($subIngKg, 4, '.', ''),
                number_format($subIngSaco, 4, '.', ''),
                number_format($subIngSol, 4, '.', ''),
            ];
        }

        // Fila vacía antes del total general
        $rows[] = array_fill(0, count($header), '');

        // Total general final
        $rows[] = [
            '', '', '', '',
            'TOTAL GENERAL',
            '',
            number_format($totIngKg, 4, '.', ''),
            number_format($totIngSaco, 4, '.', ''),
            number_format($totIngSol, 4, '.', ''),
        ];

        return collect($rows);
    }
}
