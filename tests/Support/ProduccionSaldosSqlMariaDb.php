<?php

declare(strict_types=1);

/** Run only against the restored, disposable MariaDB container for this delivery. */
require dirname(__DIR__, 2).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

$base = 'codex_saldos_prod_20261021';
$container = 'codex-saldos-mariadb-20261021';
$pdo = new PDO("mysql:host=127.0.0.1;port=33319;dbname={$base};charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== $base || ! str_contains((string) $pdo->query('SELECT VERSION()')->fetchColumn(), '10.11.19-MariaDB')) {
    throw new RuntimeException('Wrong test database or engine.');
}
$sql = file_get_contents(dirname(__DIR__, 2).'/database/sql/20261021_produccion_saldos_completo.sql');
$sql = str_replace("SET @saldos_base_autorizada = 'ene15cod_dbconsorcio26v2-16';", "SET @saldos_base_autorizada = '{$base}';", $sql);
$aplicar = str_replace(['SET @saldos_aplicar = 0;', 'SET @saldos_escrituras_pausadas = 0;'], ['SET @saldos_aplicar = 1;', 'SET @saldos_escrituras_pausadas = 1;'], $sql);
$checks = [];
$assert = function (bool $condition, string $name) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($name);
    }
    $checks[] = $name;
    echo "OK {$name}\n";
};
$run = function (string $input, bool $expectedSuccess = true) use ($container, $base): string {
    $process = new Process(['docker', 'exec', '-i', $container, 'mariadb', '-uroot', '--batch', '--raw', $base]);
    $process->setInput($input);
    $process->setTimeout(120);
    $process->run();
    if ($process->isSuccessful() !== $expectedSuccess) {
        throw new RuntimeException($process->getErrorOutput().$process->getOutput());
    }

    return $process->getOutput().$process->getErrorOutput();
};
$exists = fn (string $table): bool => (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=".$pdo->quote($table))->fetchColumn();
$hashes = function () use ($pdo): array {
    $result = [];
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $row = $pdo->query("CHECKSUM TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $result[$table] = $row['Checksum'];
    }

    return $result;
};
$pilot = fn (): array => $pdo->query('SELECT id, abonos, saldo FROM ventas WHERE id IN (90582,118388,118981) ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$columns = $pdo->query('SHOW COLUMNS FROM ventas')->fetchAll(PDO::FETCH_COLUMN);
$columnsSql = implode(',', array_map(fn ($column) => '`'.$column.'`', $columns));
$otherSales = function () use ($pdo, $columnsSql): string {
    $ctx = hash_init('sha256');
    $query = $pdo->query("SELECT {$columnsSql} FROM ventas WHERE id NOT IN (90582,118388,118981) ORDER BY id");
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        hash_update($ctx, json_encode($row, JSON_THROW_ON_ERROR)."\n");
    }

    return hash_final($ctx);
};
if ($exists('saldo_conciliaciones')) {
    throw new RuntimeException('Restore the original dump into the disposable container before running this harness.');
}
$before = $hashes();
$otherBefore = $otherSales();
$pilotBefore = $pilot();
$assert(str_contains($run($sql), 'SIMULACION OK'), 'simulation succeeds');
$assert($before === $hashes() && ! $exists('saldo_conciliaciones'), 'simulation preserves every table and installs no schema');
$assert(str_contains($run(str_replace("= '{$base}';", "= 'incorrecta';", $aplicar), false), 'BD principal autorizada'), 'wrong destination rejected');
$assert(str_contains($run(str_replace('SET @saldos_aplicar = 0;', 'SET @saldos_aplicar = 1;', $sql), false), 'Pause escrituras'), 'unpaused deployment rejected');
$pdo->exec('UPDATE ventas SET saldo=6113.00 WHERE id=118981');
$assert(str_contains($run($aplicar, false), 'Hubo pagos o cambios'), 'changed balances rejected before DDL');
$assert(! $exists('saldo_conciliaciones'), 'failed preflight installs no schema');
$pdo->exec('UPDATE ventas SET saldo=6114.00 WHERE id=118981');

$pdo->exec("CREATE TRIGGER codex_test_saldo_fail BEFORE UPDATE ON ventas FOR EACH ROW BEGIN IF NEW.id=118981 AND NEW.abonos=6437.00 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='TEST fallo ultima venta'; END IF; END");
try {
    $assert(str_contains($run($aplicar, false), 'TEST fallo ultima venta'), 'failure on final sale propagates');
    $assert($pilotBefore === $pilot(), 'all three sale balances rolled back');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM ventas WHERE id IN (90582,118388,118981) AND abonos_migrados IS NOT NULL')->fetchColumn() === 0, 'all historical baselines rolled back');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM saldo_conciliaciones')->fetchColumn() === 0, 'all audit evidence rolled back');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM recibo_contadores')->fetchColumn() === 0, 'counter inserts rolled back');
    $assert((int) $pdo->query("SELECT COUNT(*) FROM migrations WHERE migration IN ('2026_10_04_000001_add_historico_to_saldos','2026_10_05_000001_protect_provisional_receipts')")->fetchColumn() === 0, 'migrations not marked on failure');
} finally {
    $pdo->exec('DROP TRIGGER codex_test_saldo_fail');
}
$assert(str_contains($run($aplicar), 'DESPLIEGUE OK'), 'apply succeeds after partial DDL');
$actual = $pdo->query('SELECT id, abonos_migrados, saldo FROM ventas WHERE id IN (90582,118388,118981) ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$assert($actual === [
    ['id' => 90582, 'abonos_migrados' => '103.00', 'saldo' => '76.00'],
    ['id' => 118388, 'abonos_migrados' => '1180.00', 'saldo' => '0.00'],
    ['id' => 118981, 'abonos_migrados' => '5880.00', 'saldo' => '234.00'],
], 'pilot ends with balances 76, 0 and 234');
$assert((int) $pdo->query('SELECT COUNT(*) FROM saldo_conciliaciones')->fetchColumn() === 3, 'exactly three audit rows');
$assert((int) $pdo->query('SELECT COUNT(*) FROM ventas WHERE id NOT IN (90582,118388,118981) AND abonos_migrados IS NOT NULL')->fetchColumn() === 0
    && $otherBefore === $otherSales(), 'all other sales unchanged');
$after = $hashes();
foreach ($before as $table => $checksum) {
    if (! in_array($table, ['ventas', 'migrations'], true)) {
        $assert($after[$table] === $checksum, "unchanged original table {$table}");
    }
}
$assert(str_contains($run($aplicar), 'YA APLICADO') && $after === $hashes(), 'repeat deployment changes nothing');

// A real application payment, isolated from .env and from every production server.
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'mysql', 'database.connections.mysql.host' => '127.0.0.1',
    'database.connections.mysql.port' => '33319', 'database.connections.mysql.database' => $base,
    'database.connections.mysql.username' => 'root', 'database.connections.mysql.password' => '',
    'database.connections.mysql.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('mysql');
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== $base) {
    throw new RuntimeException('Application connection not isolated.');
}
auth()->login(App\Models\User::findOrFail(1));
$service = app(App\Services\VentaProvisionalService::class);
$receipt = $service->createProvisional(['cliente_id' => 2, 'numero_interno' => 'SQL-TEST',
    'fecha_provisional' => '2026-10-21 12:00:00', 'total_cobranza' => 20, 'principal' => 20,
    'deposito' => 0, 'consorcio' => 0, 'idempotencia_clave' => (string) Illuminate\Support\Str::uuid(),
    'ventas' => [['venta_id' => 118981, 'monto' => 20, 'comprobante_tipo_codigo' => 'NP', 'serie' => '1', 'correlativo' => 118981]]]);
$withPayment = $hashes();
$assert(str_contains($run($aplicar), 'YA APLICADO') && $withPayment === $hashes(), 'later application payment preserved on repeat');
$assert($pdo->query('SELECT saldo FROM ventas WHERE id=118981')->fetchColumn() === '214.00', 'later payment leaves 214');
$lastNumber = $pdo->query("SELECT ultimo_numero FROM recibo_contadores WHERE modulo='venta_provisionales'")->fetchColumn();
$service->deleteProvisional($receipt->id);
$assert($pdo->query('SELECT saldo FROM ventas WHERE id=118981')->fetchColumn() === '234.00', 'deleting test receipt preserves historical base');
$run($aplicar);
$assert($pdo->query("SELECT ultimo_numero FROM recibo_contadores WHERE modulo='venta_provisionales'")->fetchColumn() === $lastNumber, 'counter never decreases after deleting highest receipt');
$assert(str_contains($run($sql), 'SIMULACION OK'), 'simulation works after installation');
echo json_encode(['engine' => 'MariaDB 10.11.19', 'database' => $base, 'checks' => count($checks), 'result' => 'OK'], JSON_PRETTY_PRINT)."\n";
