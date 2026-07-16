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
            $stockEsperado = 0.0;

            foreach ($movimientos as $index => $mov) {
                $stockEsperadoRedondeado = round($stockEsperado, 4);
                $stockAnterior = round((float) $mov->stock_anterior, 4);

                if (abs($stockAnterior - $stockEsperadoRedondeado) > 0.0001) {
                    $problemas[] = [
                        'id' => $mov->id,
                        'fecha' => $mov->fecha,
                        'tipo' => $mov->tipo,
                        'stock_esperado' => $stockEsperadoRedondeado,
                        'stock_anterior_actual' => $stockAnterior,
                        'diferencia' => round($stockAnterior - $stockEsperadoRedondeado, 4),
                    ];
                }

                $stockEsperado = (float) $mov->stock_nuevo;
            }

            $productoOk = empty($problemas);
            $simbolo = $productoOk ? '✓' : '✗';

            $this->line("  [{$simbolo}] [{$producto->id}] {$producto->nombre}: ".
                ($productoOk ? 'cadena correcta' : count($problemas).' inconsistencia(s)'));

            if (! $productoOk) {
                $productosConProblemas[] = $producto->id;
                $totalProblemas += count($problemas);

                $this->table(
                    ['ID', 'Fecha', 'Tipo', 'Stock Esperado', 'Stock Anterior', 'Diferencia'],
                    array_map(fn ($p) => [
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
            $this->warn('Para corregir, ejecute: php artisan kardex:recalcular {producto_id} {fecha_inicio}');
        }

        return self::SUCCESS;
    }
}
