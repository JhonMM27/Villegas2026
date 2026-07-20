<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Movimiento;
use App\Models\Producto;
use Illuminate\Console\Command;

class KardexValidarCommand extends Command
{
    protected $signature = 'kardex:validar
        {--producto_id= : ID de producto específico a validar (opcional)}';

    protected $description = 'Valida la integridad de la cadena del kardex para todos los productos (o uno específico) detectando movimientos con stock_anterior inconsistente.';

    public function handle(): int
    {
        $productoIdFiltro = $this->option('producto_id');

        $productos = $productoIdFiltro
            ? Producto::where('id', $productoIdFiltro)->get()
            : Producto::orderBy('id')->get();

        if ($productos->isEmpty()) {
            $this->error('No se encontraron productos'.($productoIdFiltro ? " con ID {$productoIdFiltro}" : '').'.');

            return self::FAILURE;
        }

        $this->info("Validando cadena de kardex para {$productos->count()} producto(s)...");
        $this->newLine();

        $totalProblemas = 0;
        $productosConProblemas = [];

        foreach ($productos as $producto) {
            $movimientos = Movimiento::where('producto_id', $producto->id)
                ->orderBy('fecha', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($movimientos->isEmpty()) {
                $this->line("  [{$producto->id}] {$producto->nombre}: sin movimientos.");

                continue;
            }

            $problemas = [];
            // Las aperturas históricas se migraron en stock_anterior del
            // primer movimiento, no como un movimiento de tipo APERTURA.
            $stockEsperado = (float) $movimientos->first()->stock_anterior;

            foreach ($movimientos as $index => $mov) {
                $stockEsperadoRedondeado = round($stockEsperado, 4);
                $stockAnterior = round((float) $mov->stock_anterior, 4);

                if (abs($stockAnterior - $stockEsperadoRedondeado) > 0.0001) {
                    $problemas[] = [
                        'problema' => 'CADENA',
                        'id' => $mov->id,
                        'fecha' => $mov->fecha,
                        'tipo' => $mov->tipo,
                        'stock_esperado' => $stockEsperadoRedondeado,
                        'stock_anterior_actual' => $stockAnterior,
                        'diferencia' => round($stockAnterior - $stockEsperadoRedondeado, 4),
                    ];
                }

                $stockNuevoCalculado = round(
                    $stockAnterior + (float) $mov->entrada - (float) $mov->salida,
                    4
                );

                if (abs((float) $mov->stock_nuevo - $stockNuevoCalculado) > 0.0001) {
                    $problemas[] = [
                        'problema' => 'FÓRMULA',
                        'id' => $mov->id,
                        'fecha' => $mov->fecha,
                        'tipo' => $mov->tipo,
                        'stock_esperado' => $stockNuevoCalculado,
                        'stock_anterior_actual' => (float) $mov->stock_nuevo,
                        'diferencia' => round((float) $mov->stock_nuevo - $stockNuevoCalculado, 4),
                    ];
                }

                $valorEsperado = round((float) $mov->stock_nuevo * (float) $mov->costo_nuevo, 4);
                $toleranciaValor = max(0.1, abs($valorEsperado) * 0.00001);
                if (abs((float) $mov->valor_nuevo - $valorEsperado) > $toleranciaValor) {
                    $problemas[] = [
                        'problema' => 'VALORIZACIÓN',
                        'id' => $mov->id,
                        'fecha' => $mov->fecha,
                        'tipo' => $mov->tipo,
                        'stock_esperado' => $valorEsperado,
                        'stock_anterior_actual' => (float) $mov->valor_nuevo,
                        'diferencia' => round((float) $mov->valor_nuevo - $valorEsperado, 4),
                    ];
                }

                $stockEsperado = (float) $mov->stock_nuevo;
            }

            $ultimoMovimiento = $movimientos->last();
            if (abs((float) $producto->stock_almacen - (float) $ultimoMovimiento->stock_nuevo) > 0.0001) {
                $problemas[] = [
                    'problema' => 'SALDO PRODUCTO',
                    'id' => $ultimoMovimiento->id,
                    'fecha' => $ultimoMovimiento->fecha,
                    'tipo' => $ultimoMovimiento->tipo,
                    'stock_esperado' => (float) $ultimoMovimiento->stock_nuevo,
                    'stock_anterior_actual' => (float) $producto->stock_almacen,
                    'diferencia' => round((float) $producto->stock_almacen - (float) $ultimoMovimiento->stock_nuevo, 4),
                ];
            }

            $productoOk = empty($problemas);
            $simbolo = $productoOk ? '✓' : '✗';

            $this->line("  [{$simbolo}] [{$producto->id}] {$producto->nombre}: ".
                ($productoOk ? 'cadena correcta' : count($problemas).' inconsistencia(s)'));

            if (! $productoOk) {
                $productosConProblemas[] = $producto->id;
                $totalProblemas += count($problemas);

                $this->table(
                    ['Problema', 'ID', 'Fecha', 'Tipo', 'Esperado', 'Actual', 'Diferencia'],
                    array_map(fn ($p) => [
                        $p['problema'],
                        $p['id'],
                        $p['fecha'],
                        $p['tipo'],
                        number_format($p['stock_esperado'], 4),
                        number_format($p['stock_anterior_actual'], 4),
                        number_format($p['diferencia'], 4),
                    ], array_slice($problemas, 0, 20))
                );

                if (count($problemas) > 20) {
                    $this->warn('    ... y '.(count($problemas) - 20).' inconsistencia(s) más.');
                }
            }
        }

        $this->newLine();
        $this->info('================================================');
        $this->info('Resumen:');
        $this->line("  Productos validados: {$productos->count()}");
        $this->line('  Productos con problemas: '.count($productosConProblemas));
        $this->line("  Total inconsistencias: {$totalProblemas}");

        if (! empty($productosConProblemas)) {
            $this->line('  IDs afectados: '.implode(', ', $productosConProblemas));
            $this->newLine();
            $this->warn('Auditoría de solo lectura: no se modificó ningún dato.');
        }

        return self::SUCCESS;
    }
}
