<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MovimientosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        private Collection $reportes
    ) {}

    public function collection(): Collection
    {
        return $this->reportes;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Tipo',
            'Transacción',
            'Producto',
            'Empaque',
            'Unidad',
            'Cant.',
            'Cant. kg',
            'Entrada',
            'Salida',
            'Costo Unit.',
            'Costo Total',
            'Stock Anterior',
            'Costo Actual',
            'Valor Anterior',
            'Stock Nuevo',
            'Costo Nuevo',
            'Valor Nuevo',
            'Comentario',
        ];
    }

    public function map($r): array
    {
        return [
            Carbon::parse($r->fecha)->format('d/m/Y H:i'),
            $r->tipo,
            $r->transaccion_tipo . ' #' . $r->transaccion_id,
            $r->producto_nombre,
            (float)$r->empaque,
            $r->unidad_codigo,
            (float)$r->cantidad,
            (float)$r->cantidad_kg,
            (float)$r->entrada,
            (float)$r->salida,
            (float)$r->costo_unitario,
            (float)$r->costo_total,
            (float)$r->stock_anterior,
            (float)$r->costo_actual,
            (float)$r->valor_anterior,
            (float)$r->stock_nuevo,
            (float)$r->costo_nuevo,
            (float)$r->valor_nuevo,
            $r->comentario,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:S1')->getFont()->setBold(true);

        // Formatos numéricos
        $sheet->getStyle('E:E')->getNumberFormat()->setFormatCode('0.00'); // Empaque
        $sheet->getStyle('G:R')->getNumberFormat()->setFormatCode('0.0000'); // Cantidades y Costos

        return [];
    }
}
