<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CostosDetalladoExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            'Tipo Costo',
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
            $r['consorc'] ?? '',
        ];
    }

    private function buildRows(Collection $reportes): Collection
    {
        $out = collect();
        $totalPrincipal = 0;
        $totalDeposito = 0;
        $totalConsorc = 0;

        foreach ($reportes as $r) {
            $principal = (float) ($r->importe_p ?? 0);
            $deposito = (float) ($r->importe_d ?? 0);
            $consorc = (float) ($r->importe_c ?? 0);
            $fecha = $r->fecha_costo ? \Carbon\Carbon::parse($r->fecha_costo)->format('d/m/Y') : '';

            $totalPrincipal += $principal;
            $totalDeposito += $deposito;
            $totalConsorc += $consorc;

            $out->push([
                'recibo' => $r->numero_recibo,
                'numero_interno' => $r->numero_interno ?? '',
                'categoria' => $r->categoriaCosto?->nombre ?? '',
                'fecha' => $fecha,
                'tipo' => $r->costoTipo?->nombre ?? '',
                'descripcion' => $r->descripcion ?? '',
                'responsable' => $r->responsable ?? '',
                'principal' => $principal,
                'deposito' => $deposito,
                'consorc' => $consorc,
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
            'consorc' => $totalConsorc,
        ]);

        return $out;
    }
}
