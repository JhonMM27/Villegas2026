<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Compra;
use App\Models\Venta;
use App\Services\SaldoDocumentoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class SaldosConciliarHistoricoCommand extends Command
{
    protected $signature = 'saldos:conciliar-historico {archivo : Manifiesto JSON revisado}
        {--apply : Aplicar todo el lote en una transaccion}
        {--ensayo : Permitir solo una base de prueba codex_saldos_*}';

    protected $description = 'Reconcilia abonos historicos contra un respaldo y precondiciones explicitas; simula por defecto.';

    public function handle(SaldoDocumentoService $saldos): int
    {
        $nivelInicial = DB::transactionLevel();
        try {
            $path = (string) $this->argument('archivo');
            if (! is_file($path)) {
                throw new RuntimeException('No existe el manifiesto.');
            }
            $contenido = file_get_contents($path);
            $plan = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
            Validator::make($plan, [
                'base_destino' => ['required', 'string', 'max:100'],
                'base_origen' => ['required', 'regex:/^[a-zA-Z0-9_\/-]+$/D', 'max:100'],
                'fecha_corte' => ['required', 'date_format:Y-m-d'],
                'lote' => ['required', 'string', 'max:100'],
                'documentos' => ['required', 'array', 'min:1'],
                'documentos.*.tipo' => ['required', 'in:ventas,compras'],
                'documentos.*.id' => ['required', 'integer', 'min:1'],
                'documentos.*.titular_id' => ['required', 'integer', 'min:1'],
                'documentos.*.total' => ['required', 'numeric', 'min:0'],
                'documentos.*.acuenta' => ['required', 'numeric', 'min:0'],
                'documentos.*.abonos_antes' => ['required', 'numeric', 'min:0'],
                'documentos.*.saldo_antes' => ['required', 'numeric', 'min:0'],
                'documentos.*.historico' => ['required', 'numeric', 'min:0'],
                'documentos.*.saldo_despues' => ['required', 'numeric', 'min:0'],
            ])->validate();

            $base = DB::connection()->getDatabaseName();
            $ensayo = (bool) $this->option('ensayo');
            if (($ensayo && ! str_starts_with($base, 'codex_saldos_')) || (! $ensayo && $base !== $plan['base_destino']) || $base === $plan['base_origen']) {
                throw new RuntimeException('La base conectada no corresponde al destino autorizado del manifiesto.');
            }

            $huella = hash('sha256', $contenido);
            $claves = collect($plan['documentos'])->map(fn ($d) => $d['tipo'].':'.$d['id']);
            if ($claves->unique()->count() !== $claves->count()) {
                throw new RuntimeException('El manifiesto repite documentos.');
            }

            DB::beginTransaction();
            $filas = [];
            foreach (collect($plan['documentos'])->sortBy(fn ($d) => sprintf('%s:%012d', $d['tipo'], $d['id'])) as $entrada) {
                $tipo = $entrada['tipo'];
                $esVenta = $tipo === 'ventas';
                $prefijo = $esVenta ? 'venta' : 'compra';
                $titular = $esVenta ? 'cliente_id' : 'proveedor_id';
                $fecha = $esVenta ? 'fecha_venta' : 'fecha_compra';
                $modelo = $esVenta ? Venta::class : Compra::class;
                $doc = $modelo::whereKey($entrada['id'])->lockForUpdate()->firstOrFail();
                $registro = DB::table('saldo_conciliaciones')->where('tipo', $tipo)
                    ->where('documento_id', $doc->id)->where('lote', $plan['lote'])->first();

                if ($registro) {
                    if ($registro->huella !== $huella || $saldos->centavos($doc->abonos_migrados) !== $saldos->centavos($entrada['historico'])) {
                        throw new RuntimeException("Documento {$doc->id}: el lote previo no coincide.");
                    }
                    $saldos->validar($doc);
                    $filas[] = [$tipo, $doc->id, $doc->saldo, $doc->abonos_migrados, $doc->saldo, 'YA APLICADO'];

                    continue;
                }

                if ($doc->abonos_migrados !== null) {
                    throw new RuntimeException("Documento {$doc->id}: ya tiene una base historica; no se sobrescribira.");
                }
                $origen = DB::table($plan['base_origen'].'.'.$tipo)->where('id', $doc->id)->first();
                if (! $origen || (int) $doc->{$titular} !== (int) $entrada['titular_id']) {
                    throw new RuntimeException("Documento {$doc->id}: titular u origen no coincide.");
                }
                foreach ([$titular, 'comprobante_tipo_codigo', 'serie', 'correlativo', $fecha, 'estado', 'total', 'acuenta'] as $campo) {
                    if ((string) $doc->getRawOriginal($campo) !== (string) $origen->{$campo}) {
                        throw new RuntimeException("Documento {$doc->id}: cambio de {$campo} respecto al respaldo.");
                    }
                }
                if (strtolower(trim((string) $doc->estado)) === 'anulada' || substr((string) $origen->{$fecha}, 0, 10) > $plan['fecha_corte']) {
                    throw new RuntimeException("Documento {$doc->id}: estado o fecha incompatible.");
                }
                foreach (['total' => 'total', 'acuenta' => 'acuenta', 'abonos' => 'abonos_antes', 'saldo' => 'saldo_antes'] as $campo => $esperado) {
                    if ($saldos->centavos($doc->{$campo}) !== $saldos->centavos($entrada[$esperado])) {
                        throw new RuntimeException("Documento {$doc->id}: {$campo} cambio desde la revision; no se aplico el lote.");
                    }
                }

                $detallesOrigen = DB::table($plan['base_origen'].'.'.$prefijo.'_provisional_detalles')
                    ->where($prefijo.'_id', $doc->id)->orderBy('id')->get();
                $sumaOrigen = 0;
                foreach ($detallesOrigen as $detalle) {
                    $actual = DB::table($prefijo.'_provisional_detalles')->where('id', $detalle->id)->lockForUpdate()->first();
                    if (! $actual || $saldos->centavos($detalle->monto) < 0) {
                        throw new RuntimeException("Documento {$doc->id}: aplicaciones historicas incompletas o negativas.");
                    }
                    foreach ([$prefijo.'_id', $prefijo.'_provisional_id', 'monto'] as $campo) {
                        if ((string) $actual->{$campo} !== (string) $detalle->{$campo}) {
                            throw new RuntimeException("Documento {$doc->id}: una aplicacion del respaldo fue modificada.");
                        }
                    }
                    $sumaOrigen += $saldos->centavos($detalle->monto);
                }
                $historico = $saldos->centavos($origen->abonos) - $sumaOrigen;
                if ($historico !== $saldos->centavos($entrada['historico']) || $historico < 0
                    || $saldos->centavos($origen->saldo) !== $saldos->centavos($origen->total) - $saldos->centavos($origen->acuenta) - $saldos->centavos($origen->abonos)) {
                    throw new RuntimeException("Documento {$doc->id}: el historico no coincide con la evidencia.");
                }

                $antes = $doc->only(['total', 'acuenta', 'abonos', 'saldo', 'abonos_migrados', 'updated_at']);
                $doc->abonos_migrados = $historico / 100;
                $doc->abonos = ($historico + $saldos->aplicado($doc)) / 100;
                $doc->saldo = ($saldos->centavos($doc->total) - $saldos->centavos($doc->acuenta) - $saldos->centavos($doc->abonos)) / 100;
                $saldos->validar($doc);
                if ($saldos->centavos($doc->saldo) !== $saldos->centavos($entrada['saldo_despues'])) {
                    throw new RuntimeException("Documento {$doc->id}: la proyeccion no coincide con el saldo aprobado.");
                }

                $filas[] = [$tipo, $doc->id, $antes['saldo'], $doc->abonos_migrados, $doc->saldo, 'CONCILIAR'];
                if ($this->option('apply')) {
                    $doc->save();
                    DB::table('saldo_conciliaciones')->insert([
                        'tipo' => $tipo, 'documento_id' => $doc->id, 'lote' => $plan['lote'],
                        'base_origen' => $plan['base_origen'], 'fecha_corte' => $plan['fecha_corte'],
                        'huella' => $huella, 'anteriores' => json_encode($antes, JSON_THROW_ON_ERROR),
                        'nuevos' => json_encode($doc->only(['total', 'acuenta', 'abonos', 'saldo', 'abonos_migrados']), JSON_THROW_ON_ERROR),
                        'evidencia' => json_encode(['origen' => $origen, 'detalles_origen' => $detallesOrigen], JSON_THROW_ON_ERROR),
                        'actor' => auth()->user()?->name ?? 'CLI', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
            if ($this->option('apply')) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            $this->info('Base: '.$base.'; lote: '.$plan['lote']);
            $this->table(['Tipo', 'ID', 'Saldo anterior', 'Historico', 'Saldo final', 'Accion'], $filas);
            $this->info($this->option('apply') ? 'Lote verificado y confirmado.' : 'SIMULACION: no se modificaron datos.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > $nivelInicial) {
                DB::rollBack();
            }
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
