<?php

/**
 * Modelo Movimiento (Kardex Valorizado).
 *
 * Representa un registro individual en el kardex del inventario.
 * Cada movimiento registra una entrada o salida de stock,
 * junto con los saldos anteriores y nuevos (stock, CPP, valorizado)
 * para mantener la trazabilidad del Costo Promedio Ponderado móvil.
 *
 * Tipos de movimiento:
 * - COMPRA: Ingreso de producto por compra
 * - VENTA: Salida de producto por venta
 * - PREPARADA_SALIDA: Salida de insumos para producción
 * - PREPARADA_INGRESO: Ingreso de producto final de producción
 *
 * Relaciones:
 * - producto(): Producto al que pertenece el movimiento
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    /**
     * Campos asignables masivamente.
     * Incluye: datos del movimiento, cantidades, costos,
     * saldos anteriores/nuevos, y auditoría.
     */
    protected $fillable = [
        'fecha',
        'tipo',
        'transaccion_tipo',
        'transaccion_id',
        'detalle_id',
        'producto_id',
        'producto_nombre',
        'empaque',
        'unidad_codigo',
        'cantidad',
        'cantidad_kg',
        'entrada',
        'salida',
        'costo_unitario',
        'costo_total',
        'stock_anterior',
        'costo_actual',
        'valor_anterior',
        'stock_nuevo',
        'costo_nuevo',
        'valor_nuevo',
        'user_id',
        'comentario',
    ];

    /**
     * Casteos de tipos para atributos numéricos.
     * Asegura que los valores se manejen como decimales en PHP.
     */
    protected $casts = [
        'fecha'           => 'datetime',
        'cantidad'        => 'decimal:4',
        'cantidad_kg'     => 'decimal:4',
        'entrada'         => 'decimal:4',
        'salida'          => 'decimal:4',
        'costo_unitario'  => 'decimal:4',
        'costo_total'     => 'decimal:4',
        'stock_anterior'  => 'decimal:4',
        'costo_actual'    => 'decimal:4',
        'valor_anterior'  => 'decimal:4',
        'stock_nuevo'     => 'decimal:4',
        'costo_nuevo'     => 'decimal:4',
        'valor_nuevo'     => 'decimal:4',
    ];

    // ─── Relaciones ─────────────────────────────────────────

    /**
     * Producto al que pertenece este movimiento.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // ─── Scopes ─────────────────────────────────────────────

    /**
     * Filtra movimientos por tipo (COMPRA, VENTA, etc.)
     */
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Filtra movimientos por producto.
     */
    public function scopeDeProducto($query, int $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    /**
     * Filtra movimientos por transacción origen.
     */
    public function scopeDeTransaccion($query, string $tipo, int $id)
    {
        return $query->where('transaccion_tipo', $tipo)
                     ->where('transaccion_id', $id);
    }
}
