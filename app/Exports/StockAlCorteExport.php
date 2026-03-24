<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockAlCorteExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private Collection $rows;

    public function __construct(Collection $reportes)
    {
        $this->rows = $this->buildRowsWithSubtotals($reportes);
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Código',
            'Producto',
            'Empaque',
            'Línea',
            'Stock',
            'Costo Unitario',
            'Valor Total',
        ];
    }

    public function map($r): array
    {
        return [
            $r['codigo'] ?? '',
            $r['producto'] ?? '',
            $r['empaque'] ?? '',
            $r['linea'] ?? '',
            $r['stock'] ?? '',
            $r['costo'] ?? '',
            $r['valor'] ?? '',
        ];
    }

    private function buildRowsWithSubtotals(Collection $reportes): Collection
    {
        $out = collect();

        $lineaActual = null;
        $subStock = 0;
        $subValor = 0;
        $totalStock = 0;
        $totalValor = 0;

        foreach ($reportes as $r) {
            $linea = $r->linea ?? 'SIN LÍNEA';
            $stock = (float)$r->stock;
            $costo = (float)$r->costo_unitario;
            $valor = (float)$r->valor_total;

            // cambio de línea → insertar subtotal
            if ($lineaActual !== null && $linea !== $lineaActual) {
                $out->push([
                    'codigo' => '',
                    'producto' => 'SUBTOTAL '.$lineaActual,
                    'empaque' => '',
                    'linea' => '',
                    'stock' => $subStock,
                    'costo' => '',
                    'valor' => $subValor,
                ]);
                $subStock = 0;
                $subValor = 0;
            }

            if ($lineaActual === null) $lineaActual = $linea;
            if ($linea !== $lineaActual) $lineaActual = $linea;

            $subStock += $stock;
            $subValor += $valor;

            $totalStock += $stock;
            $totalValor += $valor;

            $out->push([
                'codigo' => $r->producto_id,
                'producto' => $r->producto,
                'empaque' => $r->empaque,
                'linea' => $linea,
                'stock' => $stock,
                'costo' => $costo,
                'valor' => $valor,
            ]);
        }

        if ($lineaActual !== null) {
            $out->push([
                'codigo' => '',
                'producto' => 'SUBTOTAL '.$lineaActual,
                'empaque' => '',
                'linea' => '',
                'stock' => $subStock,
                'costo' => '',
                'valor' => $subValor,
            ]);
        }

        $out->push([
            'codigo' => '',
            'producto' => 'TOTAL GENERAL',
            'empaque' => '',
            'linea' => '',
            'stock' => $totalStock,
            'costo' => '',
            'valor' => $totalValor,
        ]);

        return $out;
    }
}