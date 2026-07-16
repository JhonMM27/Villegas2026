<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ComprasFechaExport implements FromCollection, WithHeadings, WithMapping
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
        $query = \DB::table('compras')
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compras.pago_forma_codigo,
                compras.total,
                proveedores.id as proveedor_id,
                proveedores.razon_social,
                COUNT(compra_detalles.id) as total_items
            ')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->join('compra_detalles', 'compra_detalles.compra_id', '=', 'compras.id');

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($this->fechaInicio)->startOfDay(),
                Carbon::parse($this->fechaFin)->endOfDay(),
            ]);
        }

        return $query
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_codigo',
                'compras.total',
                'proveedores.id',
                'proveedores.razon_social'
            )
            ->orderBy('compras.fecha_compra', 'asc')
            ->orderBy('compras.correlativo', 'asc')
            ->get();
    }

    // Encabezados del Excel
    public function headings(): array
    {
        return [
            'Fecha',
            'Documento',
            'Serie',
            'Correlativo',
            'Forma Pago',
            'Ítems',
            'Total',
            'ID Proveedor',
            'Razón Social',
        ];
    }

    // Mapear cada fila para el Excel
    public function map($item): array
    {
        return [
            Carbon::parse($item->fecha_compra)->format('Y-m-d'), // Solo fecha
            $item->comprobante_tipo_codigo,
            $item->serie,
            $item->correlativo,
            $item->pago_forma_codigo,
            $item->total_items,
            number_format((float) $item->total, 2, '.', ''), // Total con 2 decimales
            $item->proveedor_id,
            $item->razon_social,
        ];
    }
}
