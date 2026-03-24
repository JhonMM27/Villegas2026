<?php

namespace App\Exports;

use App\Models\CompraDetalle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class ComprasAcumuladasProductoExport implements FromCollection, WithHeadings, WithMapping
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
        $query = CompraDetalle::query()
            ->selectRaw('
                compra_detalles.producto_id,
                compra_detalles.producto_nombre,
                compra_detalles.producto_empaque,
                lineas.nombre as linea,
                SUM(compra_detalles.cantidad) as cantidad_total,
                AVG(compra_detalles.costo_unitario) as costo_unitario,
                SUM(compra_detalles.total) as importe_total,
                SUM(compra_detalles.cantidad * compra_detalles.producto_empaque) as kg_total
            ')
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->join('productos', 'compra_detalles.producto_id', '=', 'productos.id')
            ->join('lineas', 'productos.linea_id', '=', 'lineas.id');

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($this->fechaInicio)->startOfDay(),
                Carbon::parse($this->fechaFin)->endOfDay()
            ]);
        }

        return $query
            ->groupBy('producto_id', 'producto_nombre', 'linea', 'producto_empaque')
            ->orderBy('linea')
            ->orderBy('compra_detalles.producto_nombre')
            ->get();
    }

    // Encabezados del Excel
    public function headings(): array
    {
        return [
            'ID',
            'Empaque',
            'Producto',
            'Línea',
            'Kg',
            'Cantidad',
            'Precio',
            'Importe'
        ];
    }

    // Mapear cada fila para el Excel
    public function map($item): array
    {
        return [
            $item->producto_id,
            $item->producto_empaque,
            $item->producto_nombre,
            $item->linea,
            number_format((float)$item->kg_total, 2, '.', ''),
            number_format((float)$item->cantidad_total, 2, '.', ''),
            number_format((float)$item->costo_unitario, 4, '.', ''),
            number_format((float)$item->importe_total, 2, '.', '')
        ];
    }
}
