<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Producto;
use App\Services\MovimientoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KardexRecalcularCommand extends Command
{
    protected $signature = 'kardex:recalcular
        {producto_id : ID del producto a recalcular}
        {fecha : Fecha desde la cual recalcular (Y-m-d)}';

    protected $description = 'Recalcula el kardex de un producto desde una fecha específica usando ordenamiento por fecha.';

    public function handle(MovimientoService $movimientoService): int
    {
        $productoId = (int) $this->argument('producto_id');
        $fecha = (string) $this->argument('fecha');

        try {
            $fechaCarbon = Carbon::parse($fecha);
        } catch (\Exception $e) {
            $this->error("Fecha inválida: {$fecha}. Use formato Y-m-d.");

            return self::FAILURE;
        }

        $producto = Producto::find($productoId);
        if (! $producto) {
            $this->error("Producto con ID {$productoId} no existe.");

            return self::FAILURE;
        }

        $stockAntes = (float) $producto->stock_almacen;

        $this->info("Recalculando kardex de {$producto->nombre} (ID {$productoId}) desde {$fechaCarbon->toDateString()}");
        $this->line("  Stock antes: {$stockAntes}");

        DB::transaction(function () use ($movimientoService, $productoId, $fechaCarbon) {
            $movimientoService->recalcularKardexProductoDesdeFecha(
                $productoId,
                $fechaCarbon->toDateString()
            );
        });

        $producto->refresh();
        $stockDespues = (float) $producto->stock_almacen;
        $diferencia = round($stockDespues - $stockAntes, 4);

        $this->line("  Stock después: {$stockDespues}");
        $this->line('  Diferencia: '.($diferencia >= 0 ? '+' : '').$diferencia);

        $this->info('Recálculo completado.');

        return self::SUCCESS;
    }
}
