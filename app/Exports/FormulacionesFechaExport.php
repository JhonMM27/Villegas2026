<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class FormulacionesFechaExport implements FromCollection, WithHeadings, WithMapping
{
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($fechaInicio = null, $fechaFin = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    public function collection()
    {
        $query = \DB::table('formulaciones')
            ->leftJoin(
                'formulacion_detalles',
                'formulacion_detalles.formulacion_id',
                '=',
                'formulaciones.id'
            )
            ->selectRaw('
                formulaciones.id,
                formulaciones.fecha,
                formulaciones.producto_nombre,
                formulaciones.producto_empaque,
                formulaciones.cliente_nombre,
                formulaciones.salida_kg,
                formulaciones.activo,
                formulaciones.user_nombre,
                COUNT(formulacion_detalles.id) as total_items
            ');

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('formulaciones.fecha', [
                Carbon::parse($this->fechaInicio)->startOfDay(),
                Carbon::parse($this->fechaFin)->endOfDay()
            ]);
        }

        return $query
            ->groupBy(
                'formulaciones.id',
                'formulaciones.fecha',
                'formulaciones.producto_nombre',
                'formulaciones.producto_empaque',
                'formulaciones.cliente_nombre',
                'formulaciones.salida_kg',
                'formulaciones.activo',
                'formulaciones.user_nombre'
            )
            ->orderBy('formulaciones.id', 'asc')
            ->get();
    }


    // Encabezados del Excel
    public function headings(): array
    {
        return [
            'ID',
            'Fecha',
            'Items',
            'Salida Kg',
            'Producto',
            'Empaque',
            'Cliente'
        ];
    }

    // Mapear cada fila para el Excel
    public function map($item): array
    {
        return [
            $item->id,
            Carbon::parse($item->fecha)->format('Y-m-d'), // Solo fecha
            $item->total_items,
            $item->salida_kg,
            $item->producto_nombre,
            $item->producto_empaque,
            $item->cliente_nombre
        ];
    }
}
