<?php

namespace App\Exports;

use App\Models\CompraDetalle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class ComprasDetalladasFechaExport implements FromCollection, WithHeadings, WithMapping
{
    protected $inicio;
    protected $fin;

    public function __construct($inicio, $fin)
    {
        $this->inicio = $inicio;
        $this->fin = $fin;
    }

    public function collection()
    {
        $query = CompraDetalle::query()
            ->selectRaw('
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

        if ($this->inicio && $this->fin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($this->inicio)->startOfDay(),
                Carbon::parse($this->fin)->endOfDay()
            ]);
        }

        return $query
            ->orderBy('compras.fecha_compra')
            ->orderBy('proveedores.razon_social')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Documento',
            'Serie',
            'Correlativo',
            'Producto',
            'Ing. Kg',
            'Cantidad',
            'P Lista',
            'Importe',
            'Proveedor',
        ];
    }

    public function map($item): array
    {
        return [
            Carbon::parse($item->fecha_compra)->format('d/m/Y'),
            // Documento en formato NC_1_3398
            $item->comprobante_tipo_codigo,
            $item->serie,
            $item->correlativo,
            $item->producto_nombre,
            number_format((float)$item->ing_kg, 2, '.', ''),
            number_format((float)$item->cantidad, 2, '.', ''),
            number_format((float)$item->p_lista, 4, '.', ''),
            number_format((float)$item->importe, 2, '.', ''),
            $item->proveedor_nombre
        ];
    }
}
