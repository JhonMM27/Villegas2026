<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SaldoDocumentoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SaldosAuditarCommand extends Command
{
    protected $signature = 'saldos:auditar {--tipo= : ventas o compras; ambos por defecto} {--titular= : ID de cliente o proveedor} {--json : Informe estructurado completo}';

    protected $description = 'Audita saldos y recibos sin repararlos; devuelve 1 ante incidencias, incluso historicos pendientes.';

    public function handle(SaldoDocumentoService $saldos): int
    {
        $tipo = $this->option('tipo');
        $titular = $this->option('titular');
        if (($tipo && ! in_array($tipo, ['ventas', 'compras'], true)) || ($titular && (! $tipo || ! ctype_digit((string) $titular) || (int) $titular < 1))) {
            $this->error('Use --tipo=ventas|compras y un --titular entero positivo con tipo explicito.');

            return self::INVALID;
        }

        $incidencias = DB::transaction(function () use ($tipo, $titular, $saldos): array {
            $hallazgos = [];
            $anotar = function (string $tabla, int $id, string $codigo, string $nivel = 'error') use (&$hallazgos): void {
                $hallazgos[] = compact('tabla', 'id', 'codigo', 'nivel');
            };
            foreach ($tipo ? [$tipo] : ['ventas', 'compras'] as $tabla) {
                $p = $tabla === 'ventas' ? 'venta' : 'compra';
                $owner = $p === 'venta' ? 'cliente_id' : 'proveedor_id';
                $sumas = DB::table($p.'_provisional_detalles')->select($p.'_id')->selectRaw('SUM(monto) as aplicado')->groupBy($p.'_id');
                $docs = DB::table($tabla.' as d')->leftJoinSub($sumas, 'a', 'a.'.$p.'_id', '=', 'd.id')
                    ->when($titular, fn ($q) => $q->where('d.'.$owner, $titular))
                    ->select('d.*', 'a.aplicado')->orderBy('d.id')->get();
                foreach ($docs as $d) {
                    $aplicado = $saldos->centavos($d->aplicado ?? 0);
                    $abonos = $saldos->centavos($d->abonos);
                    $saldo = $saldos->centavos($d->saldo);
                    $historico = $saldos->centavos($d->abonos_migrados ?? 0);
                    if (strtolower(trim((string) $d->estado)) === 'anulada') {
                        if ($abonos !== 0 || $aplicado !== 0 || $historico !== 0) {
                            $anotar($tabla, $d->id, 'anulado_con_abonos');
                        }

                        continue;
                    }
                    if ($d->abonos_migrados === null && $abonos > $aplicado) {
                        $anotar($tabla, $d->id, 'historico_sin_conciliar', 'pendiente');
                    } elseif ($abonos !== $historico + $aplicado) {
                        $anotar($tabla, $d->id, 'abonos_no_coinciden');
                    }
                    if ($saldo !== $saldos->centavos($d->total) - $saldos->centavos($d->acuenta) - $abonos) {
                        $anotar($tabla, $d->id, 'saldo_no_coincide');
                    }
                    if ($saldo < 0 || $abonos < 0 || $historico < 0) {
                        $anotar($tabla, $d->id, 'importe_negativo');
                    }
                }

                $distribuciones = DB::table($p.'_provisional_detalles')->select($p.'_provisional_id')->selectRaw('SUM(monto) as aplicado')->groupBy($p.'_provisional_id');
                $recibos = DB::table($p.'_provisionales as r')->leftJoinSub($distribuciones, 'a', 'a.'.$p.'_provisional_id', '=', 'r.id')
                    ->when($titular, fn ($q) => $q->where('r.'.$owner, $titular))->select('r.*', 'a.aplicado')->orderBy('r.id')->get();
                foreach ($recibos as $r) {
                    $monto = $saldos->centavos($r->monto);
                    $aplicado = $saldos->centavos($r->aplicado ?? 0);
                    $libre = $saldos->centavos($r->libre);
                    if ($aplicado > $monto || $monto < 0 || $libre < 0) {
                        $anotar($p.'_provisionales', $r->id, 'recibo_sobreaplicado_o_negativo');
                    }
                    if ($libre !== $monto - $aplicado) {
                        $anotar($p.'_provisionales', $r->id, 'libre_no_coincide');
                    }
                    if ($monto !== $saldos->centavos($r->importe_p) + $saldos->centavos($r->importe_d) + $saldos->centavos($r->importe_c)) {
                        $anotar($p.'_provisionales', $r->id, 'medios_no_coinciden');
                    }
                }
                foreach ($recibos->groupBy('numero_recibo')->filter(fn ($grupo) => $grupo->count() > 1) as $grupo) {
                    foreach ($grupo as $r) {
                        $anotar($p.'_provisionales', $r->id, 'numero_recibo_repetido');
                    }
                }

                $detalles = DB::table($p.'_provisional_detalles as a')
                    ->leftJoin($tabla.' as d', 'd.id', '=', 'a.'.$p.'_id')
                    ->leftJoin($p.'_provisionales as r', 'r.id', '=', 'a.'.$p.'_provisional_id')
                    ->when($titular, fn ($q) => $q->where(fn ($q) => $q->where('d.'.$owner, $titular)->orWhere('r.'.$owner, $titular)))
                    ->select('a.*', 'd.'.$owner.' as titular_doc', 'r.'.$owner.' as titular_recibo', 'r.id as recibo_existe', 'd.id as doc_existe')->orderBy('a.id')->get();
                foreach ($detalles as $a) {
                    if (! $a->recibo_existe || ($a->{$p.'_id'} !== null && ! $a->doc_existe)) {
                        $anotar($p.'_provisional_detalles', $a->id, 'referencia_inexistente');
                    } elseif ($a->doc_existe && (int) $a->titular_doc !== (int) $a->titular_recibo) {
                        $anotar($p.'_provisional_detalles', $a->id, 'titular_no_coincide');
                    }
                    if ($saldos->centavos($a->monto) < 0) {
                        $anotar($p.'_provisional_detalles', $a->id, 'aplicacion_negativa');
                    }
                }
            }

            return $hallazgos;
        });

        $resumen = collect($incidencias)->countBy('codigo')->all();
        if ($this->option('json')) {
            $this->line(json_encode(['base' => DB::connection()->getDatabaseName(), 'solo_lectura' => true,
                'resumen' => $resumen, 'incidencias' => $incidencias], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Incidencia', 'Cantidad'], collect($resumen)->map(fn ($n, $codigo) => [$codigo, $n])->values()->all());
            $this->table(['Tabla', 'ID', 'Codigo', 'Nivel'], array_slice($incidencias, 0, 20));
            $this->info('Solo lectura. Se muestran hasta 20 incidencias; --json incluye todas. No detecta por si solo historicos ya perdidos que cuadren aritmeticamente.');
        }

        return $incidencias ? self::FAILURE : self::SUCCESS;
    }
}
