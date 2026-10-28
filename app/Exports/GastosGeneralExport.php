<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GastosGeneralExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            $categoria = $r->categoriaGasto?->nombre ?? 'SIN CATEGORIA';
            $tipo = $r->gastoTipo?->nombre ?? 'SIN TIPO';
            $total = (float) ($r->monto ?? 0);

            $key = $categoria.'|'.$tipo;
            $item = $agrupado->get($key);
            if ($item === null) {
                $item = [
                    'categoria' => $categoria,
                    'tipo' => $tipo,
                    'total' => 0,
                ];
            }
            $item['total'] += $total;
            $agrupado->put($key, $item);
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

        $granTotal = $reportes->sum(fn ($r): float => (float) ($r->monto ?? 0));

        $out->push([
            'categoria' => '',
            'tipo' => 'TOTAL GENERAL',
            'total' => round($granTotal, 2),
        ]);

        return $out;
    }
}
