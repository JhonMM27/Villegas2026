<?php

declare(strict_types=1);

use App\Models\Compra;
use App\Models\User;
use App\Services\CompraProvisionalService;
use App\Services\ReciboControlService;
use App\Services\SaldoDocumentoService;
use App\Services\VentaProvisionalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$base = $argv[1] ?? '';
if (! preg_match('/^codex_saldos_[a-zA-Z0-9_]+$/D', $base)) {
    throw new RuntimeException('Este ensayo requiere una copia codex_saldos_*. Nunca admite la base operativa.');
}
config()->set('database.default', 'mysql');
config()->set('database.connections.mysql.database', $base);
DB::purge('mysql');
auth()->setUser(User::firstOrFail());

$marker = fn (string $grupo, string $estado) => storage_path('app/private/concurrencia_recibos_'.$grupo.'.'.$estado);
if (($argv[2] ?? '') === '--worker') {
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    if (! Str::isUuid($input['grupo'])) {
        throw new RuntimeException('Grupo de ensayo invalido.');
    }
    if ($input['primero']) {
        $pausado = false;
        DB::listen(function ($query) use (&$pausado, $input, $marker): void {
            if (! $pausado && str_contains($query->sql, 'recibo_contadores') && str_contains(strtolower($query->sql), 'for update')) {
                $pausado = true;
                touch($marker($input['grupo'], 'ready'));
                $limite = microtime(true) + 10;
                while (! is_file($marker($input['grupo'], 'second'))) {
                    if (microtime(true) > $limite) {
                        throw new RuntimeException('El segundo proceso no alcanzo la barrera.');
                    }
                    usleep(20000);
                }
                usleep(200000);
            }
        });
    } else {
        touch($marker($input['grupo'], 'second'));
    }
    $inicio = microtime(true);
    try {
        $service = app($input['tipo'] === 'venta' ? VentaProvisionalService::class : CompraProvisionalService::class);
        $r = $input['operacion'] === 'create'
            ? $service->createProvisional($input['data'])
            : $service->updateProvisional($input['id'], $input['data']);
        echo json_encode(['status' => 200, 'id' => $r->id, 'numero' => $r->numero_recibo, 'ms' => round((microtime(true) - $inicio) * 1000)], JSON_THROW_ON_ERROR);
    } catch (ValidationException $e) {
        echo json_encode(['status' => 422, 'errors' => $e->errors(), 'ms' => round((microtime(true) - $inicio) * 1000)], JSON_THROW_ON_ERROR);
    }
    exit;
}

$check = function (bool $ok, string $mensaje): void {
    if (! $ok) {
        throw new RuntimeException($mensaje);
    }
};
$pair = function (string $tipo, array $a, array $b, string $operacion = 'create', ?int $id = null) use ($base, $marker, $check): array {
    $grupo = (string) Str::uuid();
    $procesos = [];
    try {
        foreach ([$a, $b] as $i => $data) {
            $payload = compact('grupo', 'tipo', 'operacion', 'id', 'data') + ['primero' => $i === 0];
            $p = new Process([PHP_BINARY, __FILE__, $base, '--worker'], base_path(), null, json_encode($payload, JSON_THROW_ON_ERROR), 20);
            $procesos[] = $p;
            $p->start();
            if ($i === 0) {
                $limite = microtime(true) + 8;
                while (! is_file($marker($grupo, 'ready'))) {
                    $check($p->isRunning() && microtime(true) < $limite, 'Primer proceso fallo: '.$p->getOutput().$p->getErrorOutput());
                    usleep(20000);
                }
            }
        }
        $respuestas = [];
        foreach ($procesos as $p) {
            $p->wait();
            $check($p->isSuccessful(), 'Proceso fallo: '.$p->getOutput().$p->getErrorOutput());
            $respuestas[] = json_decode($p->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        }
        $check($respuestas[1]['ms'] >= 150, 'No se comprobo espera real por el bloqueo MySQL.');

        return $respuestas;
    } finally {
        foreach ($procesos as $p) {
            if ($p->isRunning()) {
                $p->stop();
            }
        }
        foreach (['ready', 'second'] as $estado) {
            if (is_file($marker($grupo, $estado))) {
                unlink($marker($grupo, $estado));
            }
        }
    }
};

$saldos = app(SaldoDocumentoService::class);
$compra = Compra::where('saldo', '>=', 76)->where('estado', '!=', 'anulada')->orderBy('id')->get()->first(function ($c) use ($saldos): bool {
    try {
        DB::transaction(fn () => $saldos->validar($c));

        return true;
    } catch (ValidationException) {
        return false;
    }
});
$check($compra !== null, 'No existe una compra consistente apta para el ensayo.');
$informe = [];
foreach (['venta' => App\Models\Venta::findOrFail(90582), 'compra' => $compra] as $p => $doc) {
    $owner = $p === 'venta' ? 'cliente_id' : 'proveedor_id';
    $service = app($p === 'venta' ? VentaProvisionalService::class : CompraProvisionalService::class);
    $modulo = $p.'_provisionales';
    $original = $saldos->centavos($doc->saldo);
    $saldo = fn () => $saldos->centavos(DB::table($p.'s')->where('id', $doc->id)->value('saldo'));
    $datos = fn (float $monto, ?string $clave = null) => [$owner => $doc->{$owner}, 'numero_interno' => null,
        'fecha_provisional' => '2026-10-05 08:00:00', 'total_cobranza' => $monto, 'principal' => $monto,
        'idempotencia_clave' => $clave ?? (string) Str::uuid(), $p.'s' => [[$p.'_id' => $doc->id,
            'monto' => $monto, 'comprobante_tipo_codigo' => $doc->comprobante_tipo_codigo, 'serie' => $doc->serie, 'correlativo' => $doc->correlativo]]];
    $creados = [];
    try {
        $antes = DB::table($modulo)->count();
        $data = $datos(20);
        $r = $pair($p, $data, $data);
        foreach ($r as $respuesta) {
            if (isset($respuesta['id'])) {
                $creados[$respuesta['id']] = $respuesta['id'];
            }
        }
        $check($r[0]['status'] === 200 && $r[1]['status'] === 200 && $r[0]['id'] === $r[1]['id'], 'Reintento concurrente duplicado.');
        $check(DB::table($modulo)->count() === $antes + 1 && $saldo() === $original - 2000, 'Reintento altero recibos o saldo.');
        $informe[] = ['tipo' => $p, 'caso' => 'reintento', 'respuestas' => $r];
        $service->deleteProvisional($r[0]['id']);
        unset($creados[$r[0]['id']]);

        $monto = ceil($original * 0.6) / 100;
        $r = $pair($p, $datos($monto), $datos($monto));
        foreach ($r as $respuesta) {
            if (isset($respuesta['id'])) {
                $creados[$respuesta['id']] = $respuesta['id'];
            }
        }
        $check($r[0]['status'] === 200 && $r[1]['status'] === 422, 'Dos pagos excedieron el saldo disponible.');
        $check($saldo() === $original - $saldos->centavos($monto), 'Saldo concurrente incorrecto.');
        $informe[] = ['tipo' => $p, 'caso' => 'sobrepago', 'respuestas' => $r];
        $service->deleteProvisional($r[0]['id']);
        unset($creados[$r[0]['id']]);

        $a = $datos(1);
        $b = $datos(2);
        $a[$p.'s'] = $b[$p.'s'] = [];
        $r = $pair($p, $a, $b);
        foreach ($r as $respuesta) {
            if (isset($respuesta['id'])) {
                $creados[$respuesta['id']] = $respuesta['id'];
            }
        }
        $check($r[0]['status'] === 200 && $r[1]['status'] === 200 && (int) $r[1]['numero'] === (int) $r[0]['numero'] + 1, 'Numeracion concurrente incorrecta.');
        $informe[] = ['tipo' => $p, 'caso' => 'numeracion', 'respuestas' => $r];
        foreach (array_keys($creados) as $id) {
            $service->deleteProvisional($id);
            unset($creados[$id]);
        }

        $recibo = $service->createProvisional($datos(10));
        $creados[$recibo->id] = $recibo->id;
        $version = app(ReciboControlService::class)->version($recibo);
        $r = $pair($p, $datos(20) + ['version_recibo' => $version], $datos(30) + ['version_recibo' => $version], 'update', $recibo->id);
        $check($r[0]['status'] === 200 && $r[1]['status'] === 422 && isset($r[1]['errors']['version_recibo']), 'Una edicion obsoleta sobrescribio el recibo.');
        $check($saldo() === $original - 2000, 'Saldo incorrecto despues de editar.');
        $informe[] = ['tipo' => $p, 'caso' => 'edicion', 'respuestas' => $r];
    } finally {
        foreach ($creados as $id) {
            $service->deleteProvisional($id);
        }
    }
    $check($saldo() === $original && DB::table($modulo)->count() === $antes, 'El ensayo no restauro los importes originales.');
}
$path = storage_path('app/private/concurrencia_recibos_'.date('Ymd_His').'.json');
file_put_contents($path, json_encode(['base' => $base, 'casos' => $informe, 'saldos_restaurados' => true], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo 'OK: 8 escenarios, dos procesos y conexiones MySQL reales. Saldos y recibos restaurados.'.PHP_EOL.$path.PHP_EOL;
