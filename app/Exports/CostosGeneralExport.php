<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CostosGeneralExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private Collection $rows;

    public function __construct(Collection $reportes)
    {
        $this->rows = $this->buildAgrupado($reportes);
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Categoria', 'Tipo', 'Total'];
    }

    public function map($r): array
    {
        return [
            $r['categoria'] ?? '',
            $r['tipo'] ?? '',
            $r['total'] ?? '',
        ];
    }

    private function buildAgrupado(Collection $reportes): Collection
    {
        $agrupado = collect();

        foreach ($reportes as $r) {
            $categoria = $r->categoriaCosto?->nombre ?? 'SIN CATEGORIA';
            $tipo = $r->costoTipo?->nombre ?? 'SIN TIPO';
            $total = (float) ($r->importe_p ?? 0) + (float) ($r->importe_d ?? 0) + (float) ($r->importe_c ?? 0);

            $key = $categoria.'|'.$tipo;
            if (! isset($agrupado[$key])) {
                $agrupado[$key] = [
                    'categoria' => $categoria,
                    'tipo' => $tipo,
                    'total' => 0,
                ];
            }
            $agrupado[$key]['total'] += $total;
        }

        $out = collect();
        $categoriaActual = null;
        $subtotalCat = 0;

        $sorted = $agrupado->values()->sortBy(function ($item) {
            return $item['categoria'].'|'.$item['tipo'];
        });

        foreach ($sorted as $item) {
            if ($categoriaActual !== null && $categoriaActual !== $item['categoria']) {
                $out->push([
                    'categoria' => '',
                    'tipo' => 'Subtotal '.$categoriaActual,
                    'total' => $subtotalCat,
                ]);
                $subtotalCat = 0;
            }

            $out->push([
                'categoria' => $item['categoria'],
                'tipo' => $item['tipo'],
                'total' => round($item['total'], 2),
            ]);

            $subtotalCat += $item['total'];
            $categoriaActual = $item['categoria'];
        }

        if ($categoriaActual !== null) {
            $out->push([
                'categoria' => '',
                'tipo' => 'Subtotal '.$categoriaActual,
                'total' => round($subtotalCat, 2),
            ]);
        }

        $granTotal = $reportes->sum(function ($r) {
            return (float) ($r->importe_p ?? 0) + (float) ($r->importe_d ?? 0) + (float) ($r->importe_c ?? 0);
        });

        $out->push([
            'categoria' => '',
            'tipo' => 'TOTAL GENERAL',
            'total' => round($granTotal, 2),
        ]);

        return $out;
    }
}
