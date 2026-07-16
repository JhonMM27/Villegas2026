<?php

namespace App\Exports;

use App\Models\VentaDetalle;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VentasAcumuladasProductoExport implements FromCollection, WithHeadings, WithMapping
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
        $query = VentaDetalle::query()
            ->selectRaw('
                venta_detalles.producto_id,
                venta_detalles.producto_nombre,
                venta_detalles.producto_empaque,
                lineas.nombre AS linea,
                SUM(venta_detalles.cantidad) AS cantidad_total,
                AVG(venta_detalles.precio_unitario) AS precio_unitario,
                SUM(venta_detalles.total) AS importe_total,
                SUM(venta_detalles.cantidad * venta_detalles.producto_empaque) AS kg_total
            ')
            ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
            ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
            ->join('lineas', 'productos.linea_id', '=', 'lineas.id');

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('ventas.fecha_venta', [
                Carbon::parse($this->fechaInicio)->startOfDay(),
                Carbon::parse($this->fechaFin)->endOfDay(),
            ]);
        }

        return $query
            ->groupBy(
                'venta_detalles.producto_id',
                'venta_detalles.producto_nombre',
                'venta_detalles.producto_empaque',
                'lineas.nombre'
            )
            ->orderBy('lineas.nombre')
            ->orderBy('venta_detalles.producto_nombre')
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
            'Importe',
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
            number_format((float) $item->kg_total, 2, '.', ''),
            number_format((float) $item->cantidad_total, 2, '.', ''),
            number_format((float) $item->precio_unitario, 4, '.', ''),
            number_format((float) $item->importe_total, 2, '.', ''),
        ];
    }
}
