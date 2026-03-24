<?php

namespace App\Exports;

use App\Models\CompraDetalle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class ComprasDetalladasProveedorExport implements FromCollection, WithHeadings, WithMapping
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
                proveedores.id as proveedor_id,
                proveedores.razon_social as proveedor_nombre,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compra_detalles.producto_id,
                compra_detalles.producto_nombre,
                (compra_detalles.cantidad * compra_detalles.producto_empaque) as ing_kg,
                compra_detalles.cantidad,
                compra_detalles.costo_unitario as p_lista,
                compra_detalles.total as importe
            ')
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id');

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($this->fechaInicio)->startOfDay(),
                Carbon::parse($this->fechaFin)->endOfDay()
            ]);
        }

        return $query
            ->orderBy('proveedores.razon_social')
            ->orderBy('compras.fecha_compra')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Proveedor ID',
            'Proveedor',
            'Fecha Compra',
            'Documento',
            'Serie',
            'Correlativo',
            'Producto ID',
            'Producto',
            'Kg Ingreso',
            'Cantidad',
            'Precio',
            'Importe'
        ];
    }

    public function map($item): array
    {
        return [
            $item->proveedor_id,
            $item->proveedor_nombre,
            $item->fecha_compra ? Carbon::parse($item->fecha_compra)->format('d/m/Y') : '',
            $item->comprobante_tipo_codigo,
            $item->serie,
            $item->correlativo,
            $item->producto_id,
            $item->producto_nombre,
            number_format((float)$item->ing_kg, 2, '.', ''),
            number_format((float)$item->cantidad, 2, '.', ''),
            number_format((float)$item->p_lista, 4, '.', ''),
            number_format((float)$item->importe, 2, '.', '')
        ];
    }
}