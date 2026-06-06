<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GastosDetalladoExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private Collection $rows;

    public function __construct(Collection $reportes)
    {
        $this->rows = $this->buildRows($reportes);
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Recibo',
            'Nro. Interno',
            'Categoria',
            'Fecha',
            'Tipo Gasto',
            'Descripcion',
            'Responsable',
            'Principal',
            'Deposito',
            'Consorcio',
        ];
    }

    public function map($r): array
    {
        return [
            $r['recibo'] ?? '',
            $r['numero_interno'] ?? '',
            $r['categoria'] ?? '',
            $r['fecha'] ?? '',
            $r['tipo'] ?? '',
            $r['descripcion'] ?? '',
            $r['responsable'] ?? '',
            $r['principal'] ?? '',
            $r['deposito'] ?? '',
            $r['consorcio'] ?? '',
        ];
    }

    private function buildRows(Collection $reportes): Collection
    {
        $out = collect();
        $totalPrincipal = 0;
        $totalDeposito = 0;
        $totalConsorcio = 0;

        foreach ($reportes as $r) {
            $principal = (float) ($r->importe_p ?? 0);
            $deposito = (float) ($r->importe_d ?? 0);
            $consorcio = (float) ($r->importe_c ?? 0);
            $fecha = $r->fecha_gasto ? \Carbon\Carbon::parse($r->fecha_gasto)->format('d/m/Y') : '';

            $totalPrincipal += $principal;
            $totalDeposito += $deposito;
            $totalConsorcio += $consorcio;

            $out->push([
                'recibo' => $r->numero_recibo,
                'numero_interno' => $r->numero_interno ?? '',
                'categoria' => $r->categoriaGasto?->nombre ?? '',
                'fecha' => $fecha,
                'tipo' => $r->gastoTipo?->nombre ?? '',
                'descripcion' => $r->descripcion ?? '',
                'responsable' => $r->responsable ?? '',
                'principal' => $principal,
                'deposito' => $deposito,
                'consorcio' => $consorcio,
            ]);
        }

        $out->push([
            'recibo' => '',
            'numero_interno' => '',
            'categoria' => '',
            'fecha' => '',
            'tipo' => '',
            'descripcion' => 'TOTALES',
            'responsable' => '',
            'principal' => $totalPrincipal,
            'deposito' => $totalDeposito,
            'consorcio' => $totalConsorcio,
        ]);

        return $out;
    }
}
