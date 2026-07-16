<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// use App\Models\Producto;

class Producto extends Model
{
    protected $fillable = [
        'unidad_codigo',
        'afectacion_tipo_codigo',
        'linea_id',
        'codigo',
        'nombre',
        'empaque',
        'descripcion',
        'imagen',
        'stock_almacen',
        'stock_minimo',
        'costo_unitario',
        'activo',
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_codigo', 'codigo');
    }

    public function afectacionTipo()
    {
        return $this->belongsTo(AfectacionTipo::class, 'afectacion_tipo_codigo', 'codigo');
    }

    public function linea()
    {
        return $this->belongsTo(Linea::class, 'linea_id', 'id');
    }

    public function fracciones()
    {
        return $this->hasMany(ProductoFraccion::class, 'producto_id', 'id');
    }

    /* ============================================================
     * COMENTADO: Migrado a MovimientoService::registrarIngreso()
     * y MovimientoService::registrarSalida()
     * ============================================================ */
    // public static function updateStock(bool $increase, array $detalles)
    // {
    //     foreach ($detalles as $detalle) {
    //         $producto = self::where('id', $detalle['producto_id'])
    //         ->lockForUpdate()
    //         ->firstOrFail();
    //         $cantidadDetalle = floatval($detalle['cantidad']);
    //         $empaqueDetalle  = floatval($detalle['producto_empaque']);
    //         $empaqueProducto = floatval($producto->empaque);
    //         if ($empaqueDetalle <= 0 || $empaqueProducto <= 0) {
    //             throw new \Exception("Empaque inválido para el producto {$producto->nombre}");
    //         }
    //         if ($empaqueDetalle == $empaqueProducto) {
    //             $cantidadFinal = $cantidadDetalle;
    //         } else {
    //             $cantidadFinal = $cantidadDetalle * ($empaqueDetalle / $empaqueProducto);
    //         }
    //         if ($increase) {
    //             $producto->stock_almacen = round((float)$producto->stock_almacen + $cantidadFinal, 4);
    //             $producto->stock_kardex = round((float)$producto->stock_kardex + $cantidadFinal, 4);
    //         } else {
    //             $producto->stock_almacen -= $cantidadFinal;
    //             $producto->stock_kardex -= $cantidadFinal;
    //         }
    //         $producto->save();
    //     }
    // }

    public static function updateStockPrestamo(bool $increase, array $detalles)
    {
        foreach ($detalles as $detalle) {

            $producto = self::where('id', $detalle['producto_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $cantidadDetalle = floatval($detalle['cantidad']);
            $empaqueDetalle = floatval($detalle['producto_empaque']);          // viene del detalle
            $empaqueProducto = floatval($producto->empaque);           // base del producto

            if ($empaqueDetalle <= 0 || $empaqueProducto <= 0) {
                throw new \Exception("Empaque inválido para el producto {$producto->nombre}");
            }

            // 🔁 Conversión al empaque base del producto
            if ($empaqueDetalle == $empaqueProducto) {
                $cantidadFinal = $cantidadDetalle;
            } else {
                $cantidadFinal = $cantidadDetalle * ($empaqueDetalle / $empaqueProducto);
            }

            // ➕➖ Actualización de stock
            if ($increase) {
                $producto->stock_kardex = round((float) $producto->stock_kardex + $cantidadFinal, 4);
            } else {
                /*
                if ($producto->stock_kardex < $cantidadFinal) {
                    throw new \Exception(
                        "Stock insuficiente para {$producto->nombre}"
                    );
                }
                */
                $producto->stock_kardex -= $cantidadFinal;
            }

            $producto->save();
        }
    }

    /* ============================================================
     * COMENTADO: Migrado a MovimientoService::registrarIngreso()
     * y MovimientoService::registrarSalida() desde PreparadaService
     * ============================================================ */
    // public static function updateStockPreparada(bool $increase, Preparada $preparada): void
    // {
    //     $productoFinal = Producto::where('id', $preparada->producto_id)
    //         ->lockForUpdate()
    //         ->firstOrFail();
    //     $qtyFinalSacos = (float) ($preparada->ingreso_saco ?? 0);
    //     $empaqueFinalDetalle  = (float) ($preparada->producto_empaque ?? 0);
    //     $empaqueFinalProducto = (float) ($productoFinal->empaque ?? 0);
    //     if ($qtyFinalSacos > 0) {
    //         if ($empaqueFinalDetalle <= 0 || $empaqueFinalProducto <= 0) {
    //             throw new \Exception("Empaque inválido para el producto final {$productoFinal->nombre}");
    //         }
    //         $qtyFinalConvertida = round(
    //             ($empaqueFinalDetalle == $empaqueFinalProducto)
    //                 ? $qtyFinalSacos
    //                 : $qtyFinalSacos * ($empaqueFinalDetalle / $empaqueFinalProducto),
    //             4
    //         );
    //         $productoFinal->stock_almacen = round(
    //             (float) $productoFinal->stock_almacen + ($increase ? $qtyFinalConvertida : -$qtyFinalConvertida),
    //             4
    //         );
    //         $productoFinal->stock_kardex = round(
    //             (float) $productoFinal->stock_kardex + ($increase ? $qtyFinalConvertida : -$qtyFinalConvertida),
    //             4
    //         );
    //         $productoFinal->save();
    //     }
    //     $preparada->loadMissing('detalles');
    //     foreach ($preparada->detalles as $detalle) {
    //         $producto = Producto::where('id', $detalle->producto_id)
    //             ->lockForUpdate()
    //             ->firstOrFail();
    //         $cantidadDetalle = (float) ($detalle->salida_saco ?? 0);
    //         $empaqueDetalle  = (float) ($detalle->producto_empaque ?? 0);
    //         $empaqueProducto = (float) ($producto->empaque ?? 0);
    //         if ($cantidadDetalle <= 0) continue;
    //         if ($empaqueDetalle <= 0 || $empaqueProducto <= 0) {
    //             throw new \Exception("Empaque inválido para el insumo {$producto->nombre}");
    //         }
    //         $cantidadFinal = round(
    //             ($empaqueDetalle == $empaqueProducto)
    //                 ? $cantidadDetalle
    //                 : $cantidadDetalle * ($empaqueDetalle / $empaqueProducto),
    //             4
    //         );
    //         $producto->stock_almacen = round(
    //             (float) $producto->stock_almacen + ($increase ? -$cantidadFinal : $cantidadFinal),
    //             4
    //         );
    //         $producto->stock_kardex = round(
    //             (float) $producto->stock_kardex + ($increase ? -$cantidadFinal : $cantidadFinal),
    //             4
    //         );
    //         $producto->save();
    //     }
    // }

    public static function updateStockPreparadaNucleo(bool $increase, NucleoPreparada $preparada): void
    {
        // =========================================================
        // 1) PRODUCTO FINAL (núcleo producido) -> SUBE stock
        // =========================================================
        $productoFinal = Producto::where('id', $preparada->nucleo_id)
            ->lockForUpdate()
            ->firstOrFail();

        $kgProducidos = (float) ($preparada->ingreso_kg ?? 0);
        if ($kgProducidos > 0) {

            $empaqueFinal = (float) ($productoFinal->empaque ?? 0); // kg por unidad
            if ($empaqueFinal <= 0) {
                throw new \Exception("Empaque inválido para el producto final {$productoFinal->nombre}");
            }

            // KG -> unidades (empaques)
            $unidadesFinal = round($kgProducidos / $empaqueFinal, 4);

            // increase=true => sube stock; false => baja stock
            $productoFinal->stock_almacen = round(
                (float) $productoFinal->stock_almacen + ($increase ? $unidadesFinal : -$unidadesFinal),
                4
            );
            $productoFinal->stock_kardex = round(
                (float) $productoFinal->stock_kardex + ($increase ? $unidadesFinal : -$unidadesFinal),
                4
            );
            $productoFinal->save();
        }

        // =========================================================
        // 2) INSUMOS (detalles consumidos) -> BAJA stock
        // =========================================================
        $preparada->loadMissing('detalles');

        foreach ($preparada->detalles as $detalle) {

            $producto = Producto::where('id', $detalle->producto_id)
                ->lockForUpdate()
                ->firstOrFail();

            $kgConsumidos = (float) ($detalle->salida_kg ?? 0);
            if ($kgConsumidos <= 0) {
                continue;
            }

            $empaqueInsumo = (float) ($producto->empaque ?? 0); // kg por unidad
            if ($empaqueInsumo <= 0) {
                throw new \Exception("Empaque inválido para el insumo {$producto->nombre}");
            }

            // KG -> unidades (empaques)
            $unidadesInsumo = round($kgConsumidos / $empaqueInsumo, 4);

            // increase=true => consumo => baja stock
            // increase=false => reversa => sube stock
            $producto->stock_almacen = round(
                (float) $producto->stock_almacen + ($increase ? -$unidadesInsumo : $unidadesInsumo),
                4
            );
            $producto->stock_kardex = round(
                (float) $producto->stock_kardex + ($increase ? -$unidadesInsumo : $unidadesInsumo),
                4
            );
            $producto->save();
        }
    }

    // public function actualizarCostoIngresoPonderado(float $cantidadBase, float $precioBase): void
    // {
    //     $cantidadBase = (float) $cantidadBase;
    //     $precioBase   = (float) $precioBase;
    //     if ($cantidadBase <= 0) return;
    //     $stockActual = (float) ($this->stock_almacen ?? 0);
    //     $costoActual = (float) ($this->costo_unitario ?? 0);
    //     if ($stockActual <= 0) {
    //         $this->costo_unitario = round($precioBase, 4);
    //         $this->save();
    //         return;
    //     }
    //     $den = $stockActual + $cantidadBase;
    //     if ($den <= 0) {
    //         $this->costo_unitario = round($precioBase, 4);
    //         $this->save();
    //         return;
    //     }
    //     $nuevoCosto = (($stockActual * $costoActual) + ($cantidadBase * $precioBase)) / $den;
    //     $this->costo_unitario = round($nuevoCosto, 4);
    //     $this->save();
    // }

    // public static function actualizarCostosIngresoDesdeDetalles(array $detalles): void
    // {
    //     foreach ($detalles as $d) {
    //         $productoId = (int) ($d['producto_id'] ?? 0);
    //         $cantidad   = (float) ($d['cantidad'] ?? 0);
    //         $precio     = (float) ($d['costo_unitario'] ?? 0);
    //         $empaqueDetalle = (float) ($d['producto_empaque'] ?? 0);
    //         if ($productoId <= 0 || $cantidad <= 0) continue;
    //         $producto = Producto::lockForUpdate()->findOrFail($productoId);
    //         $empaqueProducto = (float) ($producto->empaque ?? 0);
    //         if ($empaqueProducto <= 0) {
    //             throw new \Exception("Empaque inválido en producto ID {$productoId}");
    //         }
    //         if ($empaqueDetalle <= 0) $empaqueDetalle = $empaqueProducto;
    //         $ratio = $empaqueDetalle / $empaqueProducto;
    //         $cantidadBase = round(($ratio == 1.0) ? $cantidad : ($cantidad * $ratio), 4);
    //         $precioBase = round(($ratio == 1.0) ? $precio : ($precio / $ratio), 4);
    //         $producto->actualizarCostoIngresoPonderado($cantidadBase, $precioBase);
    //     }
    // }

    // public static function actualizarCostosIngresoDesdeDetallesCompra(array $detalles): void
    // {
    //     foreach ($detalles as $d) {
    //         $productoId = (int) ($d['producto_id'] ?? 0);
    //         $cantidad   = (float) ($d['cantidad'] ?? 0);
    //         $costo     = (float) ($d['costo_unitario'] ?? 0);
    //         $servicio     = (float) ($d['costo_unitario_servicio'] ?? 0);
    //         $precio     = (float) ($costo + $servicio);
    //         $empaqueDetalle = (float) ($d['producto_empaque'] ?? 0);
    //         if ($productoId <= 0 || $cantidad <= 0) continue;
    //         $producto = Producto::lockForUpdate()->findOrFail($productoId);
    //         $empaqueProducto = (float) ($producto->empaque ?? 0);
    //         if ($empaqueProducto <= 0) {
    //             throw new \Exception("Empaque inválido en producto ID {$productoId}");
    //         }
    //         if ($empaqueDetalle <= 0) $empaqueDetalle = $empaqueProducto;
    //         $ratio = $empaqueDetalle / $empaqueProducto;
    //         $cantidadBase = round(($ratio == 1.0) ? $cantidad : ($cantidad * $ratio), 4);
    //         $precioBase = round(($ratio == 1.0) ? $precio : ($precio / $ratio), 4);
    //         $producto->actualizarCostoIngresoPonderado($cantidadBase, $precioBase);
    //     }
    // }

    // public static function actualizarCostoDesdeIngresoCabecera(array $cabecera): void
    // {
    //     $productoId = (int) ($cabecera['producto_id'] ?? 0);
    //     if ($productoId <= 0) return;
    //     $cantidad = (float) ($cabecera['ingreso_saco'] ?? 0);
    //     $precio   = (float) ($cabecera['costo_unitario'] ?? 0);
    //     $empaqueDetalle = (float) ($cabecera['producto_empaque'] ?? 0);
    //     if ($cantidad <= 0) return;
    //     $producto = Producto::lockForUpdate()->findOrFail($productoId);
    //     $empaqueProducto = (float) ($producto->empaque ?? 0);
    //     if ($empaqueProducto <= 0) {
    //         throw new \Exception("Empaque inválido en producto ID {$productoId}");
    //     }
    //     if ($empaqueDetalle <= 0) $empaqueDetalle = $empaqueProducto;
    //     $ratio = $empaqueDetalle / $empaqueProducto;
    //     $cantidadBase = round(($ratio == 1.0) ? $cantidad : ($cantidad * $ratio), 4);
    //     $precioBase = round(($ratio == 1.0) ? $precio : ($precio / $ratio), 4);
    //     $producto->actualizarCostoIngresoPonderado($cantidadBase, $precioBase);
    // }
}
