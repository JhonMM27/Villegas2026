<?php

namespace App\Exports;

use App\Models\CompraDetalle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class ComprasDetalladasProductoExport implements FromCollection, WithHeadings, WithMapping
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
                (compra_detalles.cantidad * compra_detalles.producto_empaque) as kg_total,
                compra_detalles.cantidad,
                compra_detalles.costo_unitario as precio,
                compra_detalles.total,
                compras.comprobante_tipo_codigo as documento,
                compras.serie,
                compras.correlativo,
                proveedores.razon_social as proveedor
            ')
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->join('productos', 'compra_detalles.producto_id', '=', 'productos.id')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id');

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($this->fechaInicio)->startOfDay(),
                Carbon::parse($this->fechaFin)->endOfDay()
            ]);
        }

        return $query
            ->orderBy('compra_detalles.producto_nombre')
            ->orderBy('compras.fecha_compra')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Producto ID',
            'Producto',
            'Empaque',
            'Fecha',
            'Kg',
            'Cantidad',
            'Precio',
            'Total',
            'Documento',
            'Serie',
            'Correlativo',
            'Proveedor'
        ];
    }

    public function map($item): array
    {
        return [
            $item->producto_id,
            $item->producto_nombre,
            $item->producto_empaque,
            $item->fecha_compra ?? '', // si agregas fecha desde la relación compras
            number_format((float)$item->kg_total, 2, '.', ''),
            number_format((float)$item->cantidad, 2, '.', ''),
            number_format((float)$item->precio, 4, '.', ''),
            number_format((float)$item->total, 2, '.', ''),
            $item->documento,
            $item->serie,
            $item->correlativo,
            $item->proveedor
        ];
    }
}