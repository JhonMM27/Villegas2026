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

class KardexFechasProductosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'Operación',
            'ID',
            'Producto',
            'Empaque',
            'Línea',
            'Unidad',
            'Documento',
            'Entrada Und',
            'Salida Und',
            'Precio Unitario',
            'Stock',
            'Referencia',
        ];
    }

    public function map($r): array
    {
        $fecha = !empty($r->fecha) ? Carbon::parse($r->fecha)->format('d/m/Y H:i') : '';

        return [
            $fecha,
            $r->operacion ?? '',
            $r->id ?? '',
            $r->producto ?? '',
            (float)($r->empaque ?? 0),
            $r->linea ?? '',
            $r->unidad ?? '',
            $r->documento ?? '',
            (float)($r->entrada_und ?? 0),
            (float)($r->salida_und ?? 0),
            (float)($r->precio_unitario ?? 0),
            (float)($r->stock_und ?? $r->stock ?? 0), // ✅ por si tu campo se llama stock_und
            $r->referencia ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado en negrita
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);

        // Formatos numéricos
        $sheet->getStyle('E:E')->getNumberFormat()->setFormatCode('0.00');   // Empaque
        $sheet->getStyle('I:I')->getNumberFormat()->setFormatCode('0.00');   // Entrada
        $sheet->getStyle('J:J')->getNumberFormat()->setFormatCode('0.00');   // Salida
        $sheet->getStyle('K:K')->getNumberFormat()->setFormatCode('0.0000'); // Precio
        $sheet->getStyle('L:L')->getNumberFormat()->setFormatCode('0.00');   // Stock

        return [];
    }
}