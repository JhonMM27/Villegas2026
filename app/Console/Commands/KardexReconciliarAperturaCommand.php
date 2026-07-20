<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Movimiento;
use App\Models\Producto;
use App\Services\MovimientoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KardexReconciliarAperturaCommand extends Command
{
    protected $signature = 'kardex:reconciliar-apertura
        {archivo : CSV revisado con los saldos de apertura}
        {--fecha=2026-05-31 : Fecha de cierre autoritativa (Y-m-d)}
        {--dry-run : Solo validar y proyectar, sin escribir datos}
        {--apply : Registrar aperturas y recalcular el kardex}';

    protected $description = 'Reconcilia una apertura legacy y recalcula el kardex posterior; por defecto no escribe datos.';

    public function handle(MovimientoService $movimientoService): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Use --dry-run o --apply, no ambos.');

            return self::FAILURE;
        }

        try {
            $fechaCorte = Carbon::createFromFormat('!Y-m-d', (string) $this->option('fecha'));
        } catch (\Throwable) {
            $this->error('Fecha invalida. Use el formato Y-m-d.');

            return self::FAILURE;
        }

        if (! $fechaCorte || $fechaCorte->format('Y-m-d') !== $this->option('fecha')) {
            $this->error('Fecha invalida. Use el formato Y-m-d.');

            return self::FAILURE;
        }

        $fechaApertura = $fechaCorte->endOfDay()->format('Y-m-d H:i:s');
        $ruta = $this->resolverRuta((string) $this->argument('archivo'));

        try {
            $aperturas = $this->leerYValidarCsv($ruta);
            $proyeccion = $this->construirProyeccion($aperturas, $fechaApertura);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $cambios = array_values(array_filter(
            $proyeccion,
            fn (array $fila): bool => abs($fila['diferencia']) > 0.0001
        ));

        $this->info('Preflight de reconciliacion de apertura');
        $this->line("Archivo: {$ruta}");
        $this->line("Punto de apertura: {$fechaApertura}");
        $this->line('Productos en el archivo: '.count($aperturas));
        $this->line('Productos cuyo saldo final cambiaria: '.count($cambios));
        $this->newLine();

        if ($cambios !== []) {
            $this->table(
                ['ID', 'Producto', 'Apertura', 'Neto posterior', 'Actual', 'Proyectado', 'Cambio', 'CPP apertura'],
                array_map(fn (array $fila): array => [
                    $fila['producto_id'],
                    $fila['nombre'],
                    number_format($fila['stock_apertura'], 4, '.', ''),
                    number_format($fila['neto'], 4, '.', ''),
                    number_format($fila['stock_actual'], 4, '.', ''),
                    number_format($fila['stock_proyectado'], 4, '.', ''),
                    ($fila['diferencia'] >= 0 ? '+' : '').number_format($fila['diferencia'], 4, '.', ''),
                    number_format($fila['costo_apertura'], 4, '.', ''),
                ], $cambios)
            );
        }

        if (! $this->option('apply')) {
            $this->info('DRY-RUN completado: no se modifico ningun dato.');
            $this->line('Para aplicar, repita el comando agregando --apply durante una pausa de operaciones.');

            return self::SUCCESS;
        }

        $this->warn('APPLY solicitado: asegure un respaldo consistente y una pausa de operaciones de inventario.');

        try {
            $resultado = $movimientoService->reconciliarAperturasLegacy(
                $aperturas,
                $fechaApertura
            );
        } catch (\Throwable $exception) {
            $this->error('La transaccion fue revertida: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Reconciliacion completada dentro de una transaccion.');
        $this->line("Aperturas: {$resultado['aperturas']}");
        $this->line("Productos recalculados: {$resultado['productos']}");
        $this->line("Movimientos procesados: {$resultado['movimientos']}");
        $this->line('La operacion es idempotente: una nueva ejecucion reutilizara las aperturas existentes.');

        return self::SUCCESS;
    }

    private function resolverRuta(string $archivo): string
    {
        $ruta = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $archivo);

        if (! preg_match('/^[A-Za-z]:'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', $ruta)
            && ! str_starts_with($ruta, DIRECTORY_SEPARATOR)) {
            $ruta = base_path($ruta);
        }

        $real = realpath($ruta);
        if ($real === false || ! is_file($real) || ! is_readable($real)) {
            throw new \RuntimeException("No se puede leer el archivo CSV: {$ruta}");
        }

        return $real;
    }

    /**
     * @return array<int, array{producto_id:int,nombre:string,stock_apertura:float,costo_apertura:float,fuente_costo:string}>
     */
    private function leerYValidarCsv(string $ruta): array
    {
        $handle = fopen($ruta, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el CSV.');
        }

        try {
            $encabezado = fgetcsv($handle);
            $requeridas = ['producto_id', 'nombre', 'stock_apertura', 'costo_apertura', 'fuente_costo'];

            if (! is_array($encabezado)) {
                throw new \RuntimeException('El CSV no contiene encabezado.');
            }

            $encabezado[0] = ltrim((string) $encabezado[0], "\xEF\xBB\xBF");
            $indices = array_flip($encabezado);

            foreach ($requeridas as $columna) {
                if (! array_key_exists($columna, $indices)) {
                    throw new \RuntimeException("Falta la columna obligatoria {$columna} en el CSV.");
                }
            }

            $filas = [];
            $linea = 1;

            while (($datos = fgetcsv($handle)) !== false) {
                $linea++;
                if ($datos === [null] || $datos === []) {
                    continue;
                }

                $productoId = filter_var($datos[$indices['producto_id']] ?? null, FILTER_VALIDATE_INT);
                $stock = $datos[$indices['stock_apertura']] ?? null;
                $costo = $datos[$indices['costo_apertura']] ?? null;

                if (! $productoId || $productoId <= 0 || ! is_numeric($stock) || ! is_numeric($costo)) {
                    throw new \RuntimeException("Datos invalidos en la linea {$linea} del CSV.");
                }

                if ((int) $productoId === 77) {
                    throw new \RuntimeException('SERVICIO MEZCLADO (77) no puede formar parte de la apertura.');
                }

                if (isset($filas[(int) $productoId])) {
                    throw new \RuntimeException("Producto duplicado en el CSV: {$productoId}.");
                }

                if ((float) $costo < 0) {
                    throw new \RuntimeException("CPP negativo para el producto {$productoId}.");
                }

                $filas[(int) $productoId] = [
                    'producto_id' => (int) $productoId,
                    'nombre' => trim((string) ($datos[$indices['nombre']] ?? '')),
                    'stock_apertura' => round((float) $stock, 4),
                    'costo_apertura' => round((float) $costo, 4),
                    'fuente_costo' => trim((string) ($datos[$indices['fuente_costo']] ?? 'CSV')),
                ];
            }
        } finally {
            fclose($handle);
        }

        if ($filas === []) {
            throw new \RuntimeException('El CSV no contiene aperturas.');
        }

        $productos = Producto::whereIn('id', array_keys($filas))->get()->keyBy('id');

        foreach ($filas as $productoId => $fila) {
            $producto = $productos->get($productoId);

            if (! $producto) {
                throw new \RuntimeException("El producto {$productoId} no existe en la base de datos.");
            }

            if ((float) $producto->empaque <= 0) {
                throw new \RuntimeException("El producto {$productoId} tiene un empaque invalido.");
            }

            if ($this->normalizarNombre((string) $producto->nombre) !== $this->normalizarNombre($fila['nombre'])) {
                throw new \RuntimeException(
                    "El nombre del producto {$productoId} no coincide: CSV '{$fila['nombre']}', BD '{$producto->nombre}'."
                );
            }
        }

        ksort($filas);

        return array_values($filas);
    }

    private function normalizarNombre(string $nombre): string
    {
        $nombre = mb_strtoupper(trim($nombre), 'UTF-8');
        $nombre = preg_replace('/\s+/u', ' ', $nombre) ?? $nombre;

        return strtr($nombre, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
        ]);
    }

    private function construirProyeccion(array $aperturas, string $fechaApertura): array
    {
        $productoIds = array_column($aperturas, 'producto_id');
        $transaccionId = (int) Carbon::parse($fechaApertura)->format('Ymd');
        $duplicados = Movimiento::whereIn('producto_id', $productoIds)
            ->where('tipo', MovimientoService::TIPO_APERTURA_LEGADO)
            ->where('transaccion_tipo', MovimientoService::TRANSACCION_APERTURA_LEGADO)
            ->where('transaccion_id', $transaccionId)
            ->groupBy('producto_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('producto_id');

        if ($duplicados->isNotEmpty()) {
            throw new \RuntimeException('Existen aperturas legacy duplicadas para los productos: '.$duplicados->implode(', '));
        }

        $huerfanos = DB::table('movimientos as m')
            ->leftJoin('productos as p', 'p.id', '=', 'm.producto_id')
            ->where('m.fecha', '>=', $fechaApertura)
            ->where('m.producto_id', '!=', 77)
            ->whereNull('p.id')
            ->distinct()
            ->pluck('m.producto_id');

        if ($huerfanos->isNotEmpty()) {
            throw new \RuntimeException('Existen movimientos huerfanos para los productos: '.$huerfanos->implode(', '));
        }

        $resultado = [];

        foreach ($aperturas as $apertura) {
            $producto = Producto::findOrFail($apertura['producto_id']);
            $neto = (float) Movimiento::where('producto_id', $producto->id)
                ->where('fecha', '>', $fechaApertura)
                ->where('transaccion_tipo', '!=', MovimientoService::TRANSACCION_APERTURA_LEGADO)
                ->selectRaw('COALESCE(SUM(entrada - salida), 0) as neto')
                ->value('neto');
            $stockProyectado = round($apertura['stock_apertura'] + $neto, 4);
            $stockActual = round((float) $producto->stock_almacen, 4);

            $resultado[] = $apertura + [
                'neto' => round($neto, 4),
                'stock_actual' => $stockActual,
                'stock_proyectado' => $stockProyectado,
                'diferencia' => round($stockProyectado - $stockActual, 4),
            ];
        }

        return $resultado;
    }
}
