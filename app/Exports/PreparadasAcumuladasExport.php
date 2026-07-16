<?php

namespace App\Exports;

use App\Models\Preparada;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;

class PreparadasAcumuladasExport implements FromCollection
{
    protected $fechaInicio;

    protected $fechaFin;

    public function __construct($fechaInicio, $fechaFin)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    public function collection()
    {
        $reportes = Preparada::query()
            ->join('productos', 'preparadas.producto_id', '=', 'productos.id')
            ->join('lineas', 'productos.linea_id', '=', 'lineas.id')
            ->whereBetween('preparadas.fecha', [
                Carbon::parse($this->fechaInicio)->startOfDay(),
                Carbon::parse($this->fechaFin)->endOfDay(),
            ])
            ->where('preparadas.estado', '!=', 'anulada')
            ->select([
                'preparadas.fecha',
                'preparadas.producto_id',
                'preparadas.producto_nombre',
                'preparadas.producto_empaque',
                'lineas.nombre as linea_nombre',
                'preparadas.costo_unitario',
                'preparadas.ingreso_saco',
                'preparadas.ingreso_kg',
                'preparadas.ingreso_soles',
            ])
            ->orderBy('preparadas.fecha')
            ->orderBy('preparadas.producto_nombre')
            ->get();

        $rows = [];

        // FILA 1: título
        $rows[] = [
            'Fechas: del '.
            Carbon::parse($this->fechaInicio)->format('d/m/Y').
            ' al '.
            Carbon::parse($this->fechaFin)->format('d/m/Y'),
            '', '', '', '', '', '', '', '',
            now()->format('d/m/Y H:i:s'),
        ];

        // FILA 2: vacía
        $rows[] = [];

        // FILA 3: encabezados
        $rows[] = [
            'Fecha',
            'Código',
            'Producto',
            'Empaque',
            'Línea',
            'Precio Unitario',
            'Sacos',
            'Kg',
            'Soles',
        ];

        $totalIngresoKg = 0.0;
        $totalIngresoSacos = 0.0;
        $totalIngresoSoles = 0.0;

        foreach ($reportes as $item) {

            $rows[] = [
                Carbon::parse($item->fecha)->format('d/m/Y'),
                $item->producto_id,
                $item->producto_nombre,
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

        // TOTAL GENERAL
        if ($reportes->count() > 0) {
            $rows[] = [
                'TOTAL',
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
