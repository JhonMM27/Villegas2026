<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Movimiento;
use App\Models\Producto;
use App\Services\MovimientoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KardexCorregirAperturaCommand extends Command
{
    protected $signature = 'kardex:corregir-apertura';

    protected $description = 'Recalcula el kardex de CALCIO (29), SAL (39) y BICARBONATO (43) desde el 01/06/2026 en adelante, sin tocar movimientos anteriores. Verifica que el stock final coincida con el reporte del sistema viejo y reporta movimientos problemáticos si no coincide.';

    public function handle(MovimientoService $movimientoService): int
    {
        $productos = [
            29 => [
                'nombre' => 'CALCIO',
                'stock_esperado_15_06' => 499.6400,
            ],
            39 => [
                'nombre' => 'SAL',
                'stock_esperado_15_06' => 84.3500,
            ],
            43 => [
                'nombre' => 'BICARBONATO',
                'stock_esperado_15_06' => 137.0900,
            ],
        ];

        $this->info('================================================');
        $this->info('Recálculo de kardex desde 01/06/2026');
        $this->info('================================================');
        $this->newLine();

        foreach ($productos as $productoId => $info) {
            $producto = Producto::find($productoId);
            if (! $producto) {
                $this->error("Producto ID {$productoId} no existe.");

                continue;
            }

            $stockAntes = (float) $producto->stock_almacen;
            $this->line("Procesando: {$info['nombre']} (ID {$productoId})");
            $this->line("  Stock antes: {$stockAntes}");

            DB::transaction(function () use ($movimientoService, $productoId) {
                $movimientoService->recalcularKardexProductoDesdeFecha(
                    $productoId,
                    '2026-06-01'
                );
            });

            $producto->refresh();
            $stockDespues = (float) $producto->stock_almacen;
            $stockEsperado = $info['stock_esperado_15_06'];
            $diferencia = round($stockDespues - $stockEsperado, 4);

            $this->line("  Stock después: {$stockDespues}");
            $this->line("  Stock esperado (15/06): {$stockEsperado}");

            $estado = abs($diferencia) < 0.01 ? '✓ OK' : "✗ Diferencia: {$diferencia}";
            $this->line("  Estado: {$estado}");

            if (abs($diferencia) >= 0.01) {
                $this->warn('  ⚠ El stock no coincide. Ejecutando validación para identificar movimientos problemáticos...');
                $this->newLine();

                $movimientos = Movimiento::where('producto_id', $productoId)
                    ->where('fecha', '>=', '2026-06-01')
                    ->orderBy('fecha', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $problemas = [];
                $stockEsperadoAnterior = 0.0;

                foreach ($movimientos as $mov) {
                    $stockAnteriorReal = (float) $mov->stock_anterior;
                    $stockEsperadoAnterior = round($stockEsperadoAnterior, 4);
                    $stockAnteriorRedondeado = round($stockAnteriorReal, 4);

                    if (abs($stockAnteriorRedondeado - $stockEsperadoAnterior) > 0.0001) {
                        $problemas[] = [
                            'id' => $mov->id,
                            'fecha' => $mov->fecha,
                            'tipo' => $mov->tipo,
                            'entrada' => $mov->entrada,
                            'salida' => $mov->salida,
                            'stock_esperado' => $stockEsperadoAnterior,
                            'stock_anterior_actual' => $stockAnteriorRedondeado,
                            'diferencia' => round($stockAnteriorRedondeado - $stockEsperadoAnterior, 4),
                        ];
                    }

                    $stockEsperadoAnterior = (float) $mov->stock_nuevo;
                }

                if (empty($problemas)) {
                    $this->line('  No se encontraron inconsistencias en la cadena. La diferencia puede deberse a un stock inicial incorrecto al 01/06/2026.');
                    $ultimoMovimiento31May = Movimiento::where('producto_id', $productoId)
                        ->whereDate('fecha', '2026-05-31')
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($ultimoMovimiento31May) {
                        $stockAl3105 = (float) $ultimoMovimiento31May->stock_nuevo;
                        $this->line("  Stock al 31/05/2026 (último movimiento): {$stockAl3105}");
                        $this->line("  Stock al 31/05 esperado: {$this->getStockEsperado3105($productoId)}");
                    }
                } else {
                    $this->line('  Movimientos con inconsistencias detectadas:');
                    $this->table(
                        ['ID', 'Fecha', 'Tipo', 'Entrada', 'Salida', 'Stock Esperado', 'Stock Anterior', 'Diferencia'],
                        array_map(fn ($p) => [
                            $p['id'],
                            $p['fecha'],
                            $p['tipo'],
                            number_format((float) $p['entrada'], 4),
                            number_format((float) $p['salida'], 4),
                            number_format((float) $p['stock_esperado'], 4),
                            number_format((float) $p['stock_anterior_actual'], 4),
                            number_format((float) $p['diferencia'], 4),
                        ], array_slice($problemas, 0, 30))
                    );

                    if (count($problemas) > 30) {
                        $this->warn('    ... y '.(count($problemas) - 30).' inconsistencia(s) más.');
                    }

                    $this->newLine();
                    $this->warn('  ACCIÓN REQUERIDA: Rectificar manualmente el/los movimiento(s) con diferencia significativa.');
                }
            }

            $this->newLine();
        }

        $this->info('Proceso completado.');

        return self::SUCCESS;
    }

    private function getStockEsperado3105(int $productoId): float
    {
        $stocks = [
            29 => 84.0200,
            39 => 163.1000,
            43 => 245.9100,
        ];

        return $stocks[$productoId] ?? 0.0;
    }
}
