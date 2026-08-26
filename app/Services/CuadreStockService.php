<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CuadreStock;
use App\Models\CuadreStockDetalle;
use App\Models\Movimiento;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CuadreStockService
{
    public function __construct(
        protected MovimientoService $movimientoService
    ) {}

    public function getAll(): Collection
    {
        return CuadreStock::with(['user', 'detalles.producto'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }

    public function findById(int $id): ?CuadreStock
    {
        return CuadreStock::with(['user', 'detalles.producto'])->find($id);
    }

    public function getStockCalculadoDesdeKardex(int $productoId): float
    {
        $totales = Movimiento::where('producto_id', $productoId)
            ->selectRaw('SUM(entrada) as total_entradas, SUM(salida) as total_salidas')
            ->first();

        return round(
            ((float) ($totales->total_entradas ?? 0)) - ((float) ($totales->total_salidas ?? 0)),
            4
        );
    }

    public function generarPreview(array $detalles): array
    {
        $resultado = [];

        foreach ($detalles as $detalle) {
            $productoId = (int) ($detalle['producto_id'] ?? 0);
            $stockFisico = isset($detalle['stock_fisico']) ? (float) $detalle['stock_fisico'] : null;

            $producto = Producto::find($productoId);
            if (! $producto) {
                continue;
            }

            $stockSistema = (float) $producto->stock_almacen;
            $stockKardex = $this->getStockCalculadoDesdeKardex($productoId);
            $diferencia = $stockFisico !== null ? round($stockFisico - $stockSistema, 4) : null;

            $resultado[] = [
                'producto_id' => $productoId,
                'producto_nombre' => $producto->nombre,
                'producto_codigo' => $producto->codigo,
                'stock_sistema' => $stockSistema,
                'stock_kardex' => $stockKardex,
                'stock_fisico' => $stockFisico,
                'diferencia' => $diferencia,
                'tipo' => $diferencia !== null
                    ? ($diferencia > 0 ? 'entrada' : ($diferencia < 0 ? 'salida' : null))
                    : null,
                'discrepancia_kardex' => abs($stockSistema - $stockKardex) > 0.0001,
                'costo_unitario' => (float) $producto->costo_unitario,
                'empaque' => $producto->empaque,
                'unidad_codigo' => $producto->unidad_codigo,
            ];
        }

        return $resultado;
    }

    public function crearCuadreStock(array $data): CuadreStock
    {
        return DB::transaction(function () use ($data) {
            $cuadre = CuadreStock::create([
                'fecha' => $data['fecha'],
                'estado' => 'completado',
                'notas' => $data['notas'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            $this->movimientoService->bloquearProductos(
                collect($data['detalles'])->pluck('producto_id')->all()
            );

            $detallesAjustados = [];

            foreach ($data['detalles'] as $detalle) {
                $productoId = (int) ($detalle['producto_id'] ?? 0);
                $stockFisico = (float) ($detalle['stock_fisico'] ?? 0);

                $producto = Producto::find($productoId);
                if (! $producto) {
                    continue;
                }

                $stockSistema = (float) $producto->stock_almacen;
                $diferencia = round($stockFisico - $stockSistema, 4);

                if (abs($diferencia) < 0.0001) {
                    continue;
                }

                $cuadreDetalle = CuadreStockDetalle::create([
                    'cuadre_stock_id' => $cuadre->id,
                    'producto_id' => $productoId,
                    'stock_sistema' => $stockSistema,
                    'stock_fisico' => $stockFisico,
                    'diferencia' => $diferencia,
                    'tipo' => $diferencia > 0 ? 'entrada' : 'salida',
                ]);

                $tipoMovimiento = $diferencia > 0
                    ? MovimientoService::TIPO_AJUSTE_ENTRADA
                    : MovimientoService::TIPO_AJUSTE_SALIDA;

                $params = [
                    'tipo' => $tipoMovimiento,
                    'fecha' => $data['fecha'],
                    'transaccion_tipo' => MovimientoService::TRANSACCION_AJUSTES,
                    'transaccion_id' => $cuadre->id,
                    'detalle_id' => $cuadreDetalle->id,
                    'producto_id' => $productoId,
                    'producto_nombre' => $producto->nombre,
                    'empaque' => $producto->empaque,
                    'unidad_codigo' => $producto->unidad_codigo,
                    'cantidad' => abs($diferencia),
                    'cantidad_kg' => abs($diferencia) * (float) ($producto->empaque ?: 1),
                    'costo_unitario' => (float) $producto->costo_unitario,
                    'comentario' => "Cuadre stock: físico {$stockFisico} vs sistema {$stockSistema}. Diferencia: {$diferencia}",
                ];

                if ($diferencia > 0) {
                    $this->movimientoService->registrarIngreso($params);
                } else {
                    $this->movimientoService->registrarSalida($params);
                }

                $detallesAjustados[] = $cuadreDetalle;
            }

            $cuadre->load('detalles.producto');

            return $cuadre;
        });
    }

    public function anularCuadreStock(int $id, ?string $motivo): CuadreStock
    {
        return DB::transaction(function () use ($id, $motivo) {
            $cuadre = CuadreStock::query()->with('detalles.producto')->lockForUpdate()->findOrFail($id);

            if ($cuadre->estado === 'anulado') {
                throw new \Exception('Este cuadre de stock ya fue anulado.');
            }

            $auditoria = app(AuditoriaService::class);
            $datosAnteriores = $auditoria->capturar('cuadre_stocks', $cuadre);

            $productosAfectados = [];
            foreach ($cuadre->detalles as $detalle) {
                if (! in_array($detalle->producto_id, $productosAfectados)) {
                    $productosAfectados[] = $detalle->producto_id;
                }
            }

            $this->movimientoService->bloquearProductos($productosAfectados);

            $cuadre->update(['estado' => 'anulado']);

            foreach ($productosAfectados as $productoId) {
                $movIds = Movimiento::where('transaccion_tipo', MovimientoService::TRANSACCION_AJUSTES)
                    ->where('transaccion_id', $cuadre->id)
                    ->where('producto_id', $productoId)
                    ->pluck('id')
                    ->toArray();

                if (! empty($movIds)) {
                    $this->movimientoService->recalcularKardexExcluyendo($productoId, $movIds);
                }
            }

            $cuadre->refresh()->load('detalles.producto');
            $auditoria->registrarAnulacion('cuadre_stocks', $cuadre, $datosAnteriores,
                $auditoria->capturar('cuadre_stocks', $cuadre), $motivo);

            return $cuadre;
        });
    }

    public function rectificarCuadreStock(int $id, array $data): CuadreStock
    {
        return DB::transaction(function () use ($id, $data) {
            $cuadre = CuadreStock::query()->with('detalles.producto')->lockForUpdate()->findOrFail($id);

            if ($cuadre->estado !== 'anulado') {
                throw new \Exception('Solo se pueden rectificar cuadres en estado anulado.');
            }

            if ($cuadre->rectificacion_count >= 3) {
                throw new \Exception('Este cuadre ya no puede ser rectificado. Máximo 3 rectificaciones permitidas.');
            }

            $auditoria = app(AuditoriaService::class);
            $datosAnteriores = $auditoria->capturar('cuadre_stocks', $cuadre);
            $numeroRectificacion = (int) $cuadre->rectificacion_count + 1;

            $productosAfectados = [];
            foreach ($cuadre->detalles as $detalle) {
                if (! in_array($detalle->producto_id, $productosAfectados)) {
                    $productosAfectados[] = $detalle->producto_id;
                }
            }

            $productosAfectados = array_values(array_unique(array_merge(
                $productosAfectados,
                collect($data['detalles'])->pluck('producto_id')->map(fn ($id): int => (int) $id)->all()
            )));
            $this->movimientoService->bloquearProductos($productosAfectados);

            $cuadre->update([
                'estado' => 'completado',
                'notas' => trim(($cuadre->notas ?? '').' | Rectificado el '.now()->format('d/m/Y H:i')),
                'rectificacion_count' => $numeroRectificacion,
            ]);

            $cuadre->detalles()->delete();

            foreach ($data['detalles'] as $detalleData) {
                $productoId = (int) ($detalleData['producto_id'] ?? 0);
                $stockFisico = (float) ($detalleData['stock_fisico'] ?? 0);

                $producto = Producto::find($productoId);
                if (! $producto) {
                    continue;
                }

                $stockSistema = (float) $producto->stock_almacen;
                $diferencia = round($stockFisico - $stockSistema, 4);

                if (abs($diferencia) < 0.0001) {
                    continue;
                }

                $cuadreDetalle = CuadreStockDetalle::create([
                    'cuadre_stock_id' => $cuadre->id,
                    'producto_id' => $productoId,
                    'stock_sistema' => $stockSistema,
                    'stock_fisico' => $stockFisico,
                    'diferencia' => $diferencia,
                    'tipo' => $diferencia > 0 ? 'entrada' : 'salida',
                ]);

                $movNeutralizado = Movimiento::where('transaccion_tipo', MovimientoService::TRANSACCION_AJUSTES)
                    ->where('transaccion_id', $cuadre->id)
                    ->where('producto_id', $productoId)
                    ->where('entrada', 0)
                    ->where('salida', 0)
                    ->first();

                if ($movNeutralizado) {
                    $tipoMovimiento = $diferencia > 0
                        ? MovimientoService::TIPO_AJUSTE_ENTRADA
                        : MovimientoService::TIPO_AJUSTE_SALIDA;

                    $movNeutralizado->update([
                        'detalle_id' => $cuadreDetalle->id,
                        'fecha' => $cuadre->fecha,
                        'entrada' => $diferencia > 0 ? abs($diferencia) : 0,
                        'salida' => $diferencia < 0 ? abs($diferencia) : 0,
                        'cantidad' => abs($diferencia),
                        'cantidad_kg' => abs($diferencia) * (float) ($producto->empaque ?: 1),
                        'costo_unitario' => (float) $producto->costo_unitario,
                        'costo_total' => abs($diferencia) * (float) $producto->costo_unitario,
                        'comentario' => "Cuadre stock rectificado: físico {$stockFisico} vs sistema {$stockSistema}. Diferencia: {$diferencia}",
                    ]);
                } else {
                    $tipoMovimiento = $diferencia > 0
                        ? MovimientoService::TIPO_AJUSTE_ENTRADA
                        : MovimientoService::TIPO_AJUSTE_SALIDA;

                    $params = [
                        'tipo' => $tipoMovimiento,
                        'fecha' => $cuadre->fecha,
                        'transaccion_tipo' => MovimientoService::TRANSACCION_AJUSTES,
                        'transaccion_id' => $cuadre->id,
                        'detalle_id' => $cuadreDetalle->id,
                        'producto_id' => $productoId,
                        'producto_nombre' => $producto->nombre,
                        'empaque' => $producto->empaque,
                        'unidad_codigo' => $producto->unidad_codigo,
                        'cantidad' => abs($diferencia),
                        'cantidad_kg' => abs($diferencia) * (float) ($producto->empaque ?: 1),
                        'costo_unitario' => (float) $producto->costo_unitario,
                        'comentario' => "Cuadre stock rectificado: físico {$stockFisico} vs sistema {$stockSistema}. Diferencia: {$diferencia}",
                    ];

                    if ($diferencia > 0) {
                        $this->movimientoService->registrarIngreso($params);
                    } else {
                        $this->movimientoService->registrarSalida($params);
                    }
                }
            }

            foreach ($productosAfectados as $productoId) {
                $primerMov = Movimiento::where('transaccion_tipo', MovimientoService::TRANSACCION_AJUSTES)
                    ->where('transaccion_id', $cuadre->id)
                    ->where('producto_id', $productoId)
                    ->orderBy('id', 'asc')
                    ->first();

                if ($primerMov) {
                    $this->movimientoService->recalcularKardexProducto($productoId, $primerMov->id);
                }
            }

            $cuadre->load('detalles.producto');

            $auditoria->registrarRectificacion('cuadre_stocks', $cuadre, $numeroRectificacion, $datosAnteriores,
                $auditoria->capturar('cuadre_stocks', $cuadre), $data['rectificacion_motivo'] ?? null);

            return $cuadre;
        });
    }
}
