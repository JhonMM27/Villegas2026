<?php

namespace App\Exports;

use App\Models\FormulaAlimento;
use App\Services\FormulaAlimentoService;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FormulaAlimentoExport implements WithEvents
{
    protected FormulaAlimento $formula;

    protected array $data;

    public function __construct(int $formulaId)
    {
        $formula = FormulaAlimento::with('detalles.ingrediente')->findOrFail($formulaId);
        $this->formula = $formula;
        $this->data = app(FormulaAlimentoService::class)->calcular($formula);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => $this->buildSheet($event->sheet->getDelegate()),
        ];
    }

    private function buildSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $row = 1;
        $formula = $this->data['formula'];
        $ingredientes = $this->data['ingredientes'];
        $costos = $this->data['resumen_costos'];
        $nutricion = $this->data['resumen_nutricional'];

        $sheet->setCellValue("A{$row}", 'FÓRMULA DE ALIMENTO — VACUNOS');
        $sheet->mergeCells("A{$row}:I{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Nombre:');
        $sheet->setCellValue("B{$row}", $formula['nombre']);
        $row++;
        $sheet->setCellValue("A{$row}", 'Descripción:');
        $sheet->setCellValue("B{$row}", $formula['descripcion'] ?? '—');
        $row++;
        $sheet->setCellValue("A{$row}", 'Fecha:');
        $sheet->setCellValue("B{$row}", $formula['fecha'] ?? '—');
        $row++;
        $sheet->setCellValue("A{$row}", 'Estado:');
        $sheet->setCellValue("B{$row}", $formula['activo'] ? 'Activa' : 'Inactiva');
        $row += 2;

        $sheet->setCellValue("A{$row}", 'INGREDIENTES');
        $sheet->mergeCells("A{$row}:I{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2');
        $row++;

        $headersIng = ['#', 'Ingrediente', 'Clasificación', 'Procedencia', 'Nutriente', 'Aporte', 'S/ Kg', 'Cantidad (kg)', 'Costo'];
        $col = 'A';
        foreach ($headersIng as $h) {
            $sheet->setCellValue("{$col}{$row}", $h);
            $col++;
        }
        $lastCol = chr(ord('A') + count($headersIng) - 1);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $headerRow = $row;
        $row++;

        foreach ($ingredientes as $idx => $ing) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $ing['ingrediente']);
            $sheet->setCellValue("C{$row}", $ing['clasificacion'] ?? '');
            $sheet->setCellValue("D{$row}", $ing['procedencia'] ?? '');
            $sheet->setCellValue("E{$row}", $ing['nutriente'] ?? '');
            $sheet->setCellValue("F{$row}", (float) ($ing['aporte'] ?? 0));
            $sheet->setCellValue("G{$row}", (float) $ing['precio_kg']);
            $sheet->setCellValue("H{$row}", (float) $ing['cantidad_kg']);
            $sheet->setCellValue("I{$row}", (float) $ing['costo']);
            $row++;
        }
        $sheet->getStyle("A".($headerRow + 1).":{$lastCol}".($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue("G{$row}", 'TOTAL');
        $sheet->setCellValue("H{$row}", array_sum(array_column($ingredientes, 'cantidad_kg')));
        $sheet->setCellValue("I{$row}", array_sum(array_column($ingredientes, 'costo')));
        $sheet->getStyle("G{$row}:I{$row}")->getFont()->setBold(true);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'RESUMEN DE COSTOS');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2');
        $row++;
        $costosRows = [
            ['S/ Tonelada', $costos['costo_tonelada']],
            ['S/ Kg', $costos['costo_kg']],
            ['+ Saco vacío', $formula['saco_vacio']],
            ['+ Mano de obra', $formula['mano_obra']],
            ['+ Energía', $formula['energia']],
            ['+ Merma', $formula['merma']],
            ['Costo / Saco', $costos['costo_saco']],
            ['Precio Venta', $costos['precio_venta']],
            ['Ganancia / Saco', $costos['ganancia_saco']],
            ['Margen %', $costos['margen_porcentaje']],
        ];
        foreach ($costosRows as $cr) {
            $sheet->setCellValue("A{$row}", $cr[0]);
            $sheet->setCellValue("C{$row}", is_numeric($cr[1]) ? (float) $cr[1] : $cr[1]);
            if ($cr[0] === 'Costo / Saco' || $cr[0] === 'Ganancia / Saco' || $cr[0] === 'Margen %') {
                $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
            }
            $row++;
        }
        $row += 1;

        $sheet->setCellValue("A{$row}", 'APORTE NUTRICIONAL (BASE 1000 KG)');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2');
        $row++;
        $nutRows = [
            ['Materia Seca', $nutricion['materia_seca']],
            ['Proteína Cruda', $nutricion['proteina_cruda']],
            ['ENL (Mcal/kg)', $nutricion['enl']],
            ['FDN', $nutricion['fdn']],
            ['Grasa', $nutricion['grasa']],
            ['Almidón', $nutricion['almidon']],
            ['Azúcar', $nutricion['azucar']],
        ];
        foreach ($nutRows as $nr) {
            $sheet->setCellValue("A{$row}", $nr[0]);
            $sheet->setCellValue("C{$row}", (float) $nr[1]);
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
