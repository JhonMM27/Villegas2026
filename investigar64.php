<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$productoId = 64;

echo "=== NUCLEO_PREPARADAS que usaron PLUS SINTOX (ID 64) ===\n";
echo "Período: 24/03/2026 al 03/06/2026\n";
echo "Comparar con los R.N del sistema viejo\n\n";

// Movimientos de nucleo_preparadas para producto 64
$movNucleo = DB::select(
    "SELECT m.id as mov_id, m.fecha, m.transaccion_id as nucleo_preparada_id,
            ROUND(m.salida,4) as salida,
            ROUND(m.stock_anterior,4) as st_ant,
            ROUND(m.stock_nuevo,4) as st_nuevo
     FROM movimientos m
     WHERE m.producto_id = ?
       AND m.transaccion_tipo = 'nucleo_preparadas'
       AND m.tipo = 'PREPARADA_SALIDA'
       AND m.fecha BETWEEN '2026-03-24' AND '2026-06-03 23:59:59'
     ORDER BY m.fecha ASC, m.id ASC",
    [$productoId]
);

echo str_pad('MOV_ID', 7).' | '.str_pad('FECHA', 22).' | '.str_pad('NP_ID', 7).' | SALIDA'.PHP_EOL;
echo str_repeat('-', 65).PHP_EOL;
$totalNucleo = 0;
foreach ($movNucleo as $m) {
    echo str_pad($m->mov_id, 7).' | '.str_pad($m->fecha, 22).' | '.str_pad($m->nucleo_preparada_id, 7).' | '.$m->salida.PHP_EOL;
    $totalNucleo += $m->salida;
}
echo "\nTotal nucleo_preparadas salidas: ".round($totalNucleo, 4)." sacos\n";

echo "\n=== PREPARADAS NORMALES que usaron PLUS SINTOX (ID 64) ===\n";
$movPrep = DB::select(
    "SELECT m.id as mov_id, m.fecha, m.transaccion_id as preparada_id,
            ROUND(m.salida,4) as salida
     FROM movimientos m
     WHERE m.producto_id = ?
       AND m.transaccion_tipo = 'preparadas'
       AND m.tipo = 'PREPARADA_SALIDA'
       AND m.fecha BETWEEN '2026-03-24' AND '2026-06-03 23:59:59'
     ORDER BY m.fecha ASC, m.id ASC
     LIMIT 30",
    [$productoId]
);

echo str_pad('MOV_ID', 7).' | '.str_pad('FECHA', 22).' | '.str_pad('PREP_ID', 8).' | SALIDA'.PHP_EOL;
echo str_repeat('-', 60).PHP_EOL;
$totalPrep = 0;
foreach ($movPrep as $m) {
    echo str_pad($m->mov_id, 7).' | '.str_pad($m->fecha, 22).' | '.str_pad($m->preparada_id, 8).' | '.$m->salida.PHP_EOL;
    $totalPrep += $m->salida;
}

$totalPrepAll = DB::select(
    "SELECT ROUND(SUM(salida),4) as total
     FROM movimientos
     WHERE producto_id=? AND transaccion_tipo='preparadas' AND tipo='PREPARADA_SALIDA'
       AND fecha BETWEEN '2026-03-24' AND '2026-06-03 23:59:59'",
    [$productoId]
)[0]->total;
echo "(mostrando primeros 30 de los 206 movimientos)\n";
echo "Total preparadas salidas: {$totalPrepAll} sacos\n";

echo "\n=== VERIFICAR: ¿Existe relación entre nucleo_preparadas y preparadas? ===\n";
echo "(¿Algún nucleo_preparada también registra su insumo en 'preparadas'?)\n\n";

// Verificar si los nucleo_preparada_id tienen también movimientos en preparadas
$npIds = array_column($movNucleo, 'nucleo_preparada_id');
if (! empty($npIds)) {
    $placeholder = implode(',', $npIds);
    $cruce = DB::select(
        "SELECT m.transaccion_tipo, m.transaccion_id, COUNT(*) as qty, ROUND(SUM(m.salida),4) as total_salida
         FROM movimientos m
         WHERE m.producto_id = ? AND m.transaccion_id IN ({$placeholder})
           AND m.fecha BETWEEN '2026-03-24' AND '2026-06-03 23:59:59'
         GROUP BY m.transaccion_tipo, m.transaccion_id
         ORDER BY m.transaccion_tipo",
        [$productoId]
    );

    $dobles = [];
    foreach ($cruce as $c) {
        echo "  transaccion_tipo={$c->transaccion_tipo} | transaccion_id={$c->transaccion_id} | {$c->qty} movs | salida={$c->total_salida}\n";
        if ($c->transaccion_tipo === 'preparadas') {
            $dobles[] = $c;
        }
    }

    if (! empty($dobles)) {
        echo "\n⚠️  DOBLE REGISTRO DETECTADO: Los siguientes IDs aparecen como PREPARADA_SALIDA\n";
        echo "   en AMBAS tablas (preparadas Y nucleo_preparadas):\n";
        foreach ($dobles as $d) {
            echo "   ID {$d->transaccion_id} → salida doble: {$d->total_salida} sacos\n";
        }
    } else {
        echo "\n✅ No hay doble registro por ID entre nucleo_preparadas y preparadas.\n";
    }
}
