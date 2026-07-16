<?php

namespace App\Exports;

use App\Models\NucleoPreparada;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;

class NucleoPreparadasAcumuladasExport implements FromCollection
{
    protected $fechaInicio;

    protected $fechaFin;

    public function __construct($fechaInicio, $fechaFin)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    /**
     * Retorna la colección de datos formateada para el Excel.
     */
    public function collection()
    {
        $reportes = NucleoPreparada::query()
            ->join('productos', 'nucleo_preparadas.nucleo_id', '=', 'productos.id')
            ->join('lineas', 'productos.linea_id', '=', 'lineas.id')
            ->whereBetween('nucleo_preparadas.fecha', [
                Carbon::parse($this->fechaInicio)->startOfDay(),
                Carbon::parse($this->fechaFin)->endOfDay(),
            ])
            ->where('nucleo_preparadas.estado', '!=', 'anulada')
            ->select([
                'nucleo_preparadas.fecha',
                'nucleo_preparadas.nucleo_id',
                'nucleo_preparadas.nucleo_nombre',
                'nucleo_preparadas.producto_empaque',
                'lineas.nombre as linea_nombre',
                'nucleo_preparadas.costo_unitario',
                'nucleo_preparadas.ingreso_saco',
                'nucleo_preparadas.ingreso_kg',
                'nucleo_preparadas.ingreso_soles',
            ])
            ->orderBy('nucleo_preparadas.fecha')
            ->orderBy('nucleo_preparadas.nucleo_nombre')
            ->get();

        $rows = [];

        // FILA 1: Título y Fechas
        $rows[] = [
            'Reporte de Núcleos Preparados (Acumulado)',
            '', '', '', '', '', '', '', '',
            now()->format('d/m/Y H:i:s'),
        ];
        $rows[] = [
            'Fechas: del '.
            Carbon::parse($this->fechaInicio)->format('d/m/Y').
            ' al '.
            Carbon::parse($this->fechaFin)->format('d/m/Y'),
        ];

        // FILA 3: Vacía
        $rows[] = [];

        // FILA 4: Encabezados
        $rows[] = [
            'Fecha',
            'ID Núcleo',
            'Nombre Núcleo',
            'Empaque',
            'Línea',
            'Costo Unitario',
            'Sacos',
            'Kg Total',
            'Soles Total',
        ];

        $totalIngresoKg = 0.0;
        $totalIngresoSacos = 0.0;
        $totalIngresoSoles = 0.0;

        foreach ($reportes as $item) {
            $rows[] = [
                Carbon::parse($item->fecha)->format('d/m/Y'),
                $item->nucleo_id,
                $item->nucleo_nombre,
                $item->producto_empaque,
                $item->linea_nombre,
                number_format((float) $item->costo_unitario, 4, '.', ''),
                number_format((float) $item->ingreso_saco, 4, '.', ''),
                number_format((float) $item->ingreso_kg, 4, '.', ''),
                number_format((float) $item->ingreso_soles, 4, '.', ''),
            ];

            $totalIngresoKg += (float) $item->ingreso_kg;
            $totalIngresoSacos += (float) $item->ingreso_saco;
            $totalIngresoSoles += (float) $item->ingreso_soles;
        }

        // Totales Finales
        if ($reportes->count() > 0) {
            $rows[] = [];
            $rows[] = [
                'TOTAL GENERAL',
                '',
                '',
                '',
                '',
                '',
                number_format($totalIngresoSacos, 4, '.', ''),
                number_format($totalIngresoKg, 4, '.', ''),
                number_format($totalIngresoSoles, 4, '.', ''),
            ];
        }

        return collect($rows);
    }
}
