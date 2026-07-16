<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CostosExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            'Recibo',
            'Nro. Interno',
            'Tipo',
            'Fecha',
            'Descripción',
            'Responsable',
            'DNI',
            'Principal',
            'Depósito',
            'Consorcio',
        ];
    }

    public function map($r): array
    {
        return [
            $r['recibo'] ?? '',
            $r['numero_interno'] ?? '',
            $r['tipo'] ?? '',
            $r['fecha'] ?? '',
            $r['descripcion'] ?? '',
            $r['responsable'] ?? '',
            $r['responsable_dni'] ?? '',
            $r['principal'] ?? '',
            $r['deposito'] ?? '',
            $r['consorc'] ?? '',
        ];
    }

    private function buildRowsWithSubtotals(Collection $reportes): Collection
    {
        $out = collect();

        $tipoActual = null;
        $subPrincipal = 0;
        $subDeposito = 0;
        $subConsorc = 0;
        $totalPrincipal = 0;
        $totalDeposito = 0;
        $totalConsorc = 0;

        foreach ($reportes as $r) {
            $tipo = $r->costoTipo?->nombre ?? 'SIN TIPO';
            $principal = (float) ($r->importe_p ?? 0);
            $deposito = (float) ($r->importe_d ?? 0);
            $consorc = (float) ($r->importe_c ?? 0);
            $fecha = $r->fecha_costo ? \Carbon\Carbon::parse($r->fecha_costo)->format('d/m/Y H:i') : '';

            if ($tipoActual !== null && $tipo !== $tipoActual) {
                $out->push([
                    'recibo' => '',
                    'numero_interno' => '',
                    'tipo' => 'SUBTOTAL '.$tipoActual,
                    'fecha' => '',
                    'descripcion' => '',
                    'responsable' => '',
                    'responsable_dni' => '',
                    'principal' => $subPrincipal,
                    'deposito' => $subDeposito,
                    'consorc' => $subConsorc,
                ]);
                $subPrincipal = 0;
                $subDeposito = 0;
                $subConsorc = 0;
            }

            if ($tipoActual === null) {
                $tipoActual = $tipo;
            }
            if ($tipo !== $tipoActual) {
                $tipoActual = $tipo;
            }

            $subPrincipal += $principal;
            $subDeposito += $deposito;
            $subConsorc += $consorc;

            $totalPrincipal += $principal;
            $totalDeposito += $deposito;
            $totalConsorc += $consorc;

            $out->push([
                'recibo' => $r->numero_recibo,
                'numero_interno' => $r->numero_interno,
                'tipo' => $tipo,
                'fecha' => $fecha,
                'descripcion' => $r->descripcion,
                'responsable' => $r->responsable,
                'responsable_dni' => $r->responsable_dni ?? '',
                'principal' => $principal,
                'deposito' => $deposito,
                'consorc' => $consorc,
            ]);
        }

        if ($tipoActual !== null) {
            $out->push([
                'recibo' => '',
                'numero_interno' => '',
                'tipo' => 'SUBTOTAL '.$tipoActual,
                'fecha' => '',
                'descripcion' => '',
                'responsable' => '',
                'responsable_dni' => '',
                'principal' => $subPrincipal,
                'deposito' => $subDeposito,
                'consorc' => $subConsorc,
            ]);
        }

        $out->push([
            'recibo' => '',
            'numero_interno' => '',
            'tipo' => 'TOTAL GENERAL',
            'fecha' => '',
            'descripcion' => '',
            'responsable' => '',
            'responsable_dni' => '',
            'principal' => $totalPrincipal,
            'deposito' => $totalDeposito,
            'consorc' => $totalConsorc,
        ]);

        return $out;
    }
}
