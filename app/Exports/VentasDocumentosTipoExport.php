<?php

namespace App\Exports;

use App\Models\Venta;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VentasDocumentosTipoExport implements FromCollection, WithHeadings, WithMapping
{
    protected $fechaInicio;

    protected $fechaFin;

    protected $tipoDoc;

    public function __construct($fechaInicio = null, $fechaFin = null, $tipoDoc = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->tipoDoc = $tipoDoc;
    }

    public function collection()
    {
        $query = Venta::query()
            ->select(
                'ventas.fecha_venta',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.total',
                'ventas.acuenta',
                'ventas.items',
                'ventas.pago_forma_nombre',
                'ventas.cliente_id',
                'ventas.cliente_nombre'
            );

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('ventas.fecha_venta', [
                Carbon::parse($this->fechaInicio)->startOfDay(),
                Carbon::parse($this->fechaFin)->endOfDay(),
            ]);
        }
        if (! empty($tipoDoc)) {
            $query->where('ventas.comprobante_tipo_codigo', $tipoDoc);
        }

        return $query
            ->orderBy('ventas.correlativo', 'asc')
            ->orderBy('ventas.fecha_venta', 'asc')
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
            'Total',
            'A cuenta',
            'Ítems',
            'Forma Pago',
            'ID Cliente',
            'Razón Social',
        ];
    }

    // Mapear cada fila para el Excel
    public function map($item): array
    {
        return [
            Carbon::parse($item->fecha_venta)->format('Y-m-d'),
            $item->comprobante_tipo_codigo,
            $item->serie,
            $item->correlativo,
            number_format((float) $item->total, 2, '.', ''),
            number_format((float) $item->acuenta, 2, '.', ''),
            $item->items,
            $item->pago_forma_nombre,
            $item->cliente_id,
            $item->cliente_nombre,
        ];
    }
}
