<?php

/**
 * Test de Regresión: Re-uso de Movimientos Neutralizados en Rectificación.
 *
 * Verifica que cuando un préstamo o preparada tiene 2+ detalles del mismo
 * producto, cada detalle restaura un movimiento neutralizado DISTINTO.
 *
 * Cubre 2 escenarios:
 *  2.1) Préstamo con 2 detalles del mismo producto.
 *  2.2) Preparada con 2 insumos del mismo producto.
 *
 * Composición de la verificación:
 *  - Crea entidades mínimas necesarias (cliente, productos).
 *  - Simula el estado post-anulación: prestamo/preparada con estado='anulada'
 *    y N movimientos neutralizados en el kardex.
 *  - Ejecuta el código EXACTO del fix (el loop de rectificar) con la data
 *    de prueba.
 *  - Verifica que se asignaron movimientos distintos a cada detalle.
 *  - Rollback al final.
 *
 * Ejecución:
 *   php artisan tinker --execute="require 'tests/verify_kardex_reuso.php';"
 */

namespace Tests\Scripts;

use App\Models\Cliente;
use App\Models\Movimiento;
use App\Models\Prestamo;
use App\Models\Preparada;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerifyKardexReuso
{
    /** @var int Asserts exitosos */
    private int $assertsPassed = 0;

    /** @var int Asserts fallidos */
    private int $assertsFailed = 0;

    /** @var array Lista de fallos */
    private array $failures = [];

    public function run(): void
    {
        echo "\n--- INICIANDO VERIFICACIÓN DE RE-USO EN RECTIFICACIÓN ---\n\n";

        $user = User::first();
        if ($user) {
            Auth::login($user);
            echo "Logueado como: {$user->name} (ID: {$user->id})\n\n";
        } else {
            echo "ERROR: No hay usuarios en la BD.\n";

            return;
        }

        DB::beginTransaction();

        try {
            $this->test2_1_prestamoConDosDetallesMismoProducto();
            $this->test2_2_preparadaConDosInsumosMismoProducto();
        } catch (\Exception $e) {
            echo "\nERROR INESPERADO: {$e->getMessage()}\n";
            echo $e->getTraceAsString()."\n";
            $this->assertsFailed++;
            $this->failures[] = 'Excepción no controlada: '.$e->getMessage();
        } finally {
            DB::rollBack();
            $this->printSummary();
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 2.1: Préstamo con 2 detalles del mismo producto
    // ─────────────────────────────────────────────────────────────────
    private function test2_1_prestamoConDosDetallesMismoProducto(): void
    {
        echo "[Test 2.1] Préstamo con 2 detalles del mismo producto\n";

        $producto = $this->crearProductoTest('TEST-2.1');
        $cliente = $this->crearClienteTest();

        // 1) Crear préstamo en estado 'anulada' con 2 detalles del MISMO producto
        $prestamo = Prestamo::create([
            'user_id' => 1,
            'user_nombre' => 'Test',
            'movimiento_tipo' => 'PA',
            'cliente_origen_id' => 11, // EMPRESA_CLIENTE_ID (constante del servicio)
            'cliente_destino_id' => $cliente->id,
            'comprobante_tipo_codigo' => 'SP',
            'comprobante_tipo_nombre' => 'Préstamo',
            'serie' => '001',
            'correlativo' => 1,
            'fecha_prestamo' => '2026-06-17 10:00:00',
            'total' => 200,
            'estado' => 'anulada',
            'rectificacion_count' => 0,
        ]);
        $prestamoId = 99001; // ID ficticio para no chocar

        // 2) Simular 2 movimientos neutralizados en el kardex
        //    (estado que queda tras anular el préstamo)
        $mov1 = Movimiento::create([
            'fecha' => '2026-06-17 10:00:00',
            'tipo' => 'PRESTAMO_SALIDA',
            'transaccion_tipo' => 'prestamos',
            'transaccion_id' => $prestamoId,
            'detalle_id' => 1,
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'empaque' => 50,
            'unidad_codigo' => 'BG',
            'cantidad' => 10,
            'cantidad_kg' => 500,
            'entrada' => 0,
            'salida' => 10,
            'costo_unitario' => 1.0,
            'costo_total' => 10,
            'comentario' => '[ANULADO] Préstamo neutralizado',
        ]);
        $mov2 = Movimiento::create([
            'fecha' => '2026-06-17 10:00:00',
            'tipo' => 'PRESTAMO_SALIDA',
            'transaccion_tipo' => 'prestamos',
            'transaccion_id' => $prestamoId,
            'detalle_id' => 2,
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'empaque' => 50,
            'unidad_codigo' => 'BG',
            'cantidad' => 5,
            'cantidad_kg' => 250,
            'entrada' => 0,
            'salida' => 5,
            'costo_unitario' => 1.0,
            'costo_total' => 5,
            'comentario' => '[ANULADO] Préstamo neutralizado',
        ]);

        // 3) SIMULAR EL CÓDIGO DEL FIX (loop de rectificarPrestamo)
        //    con 2 detalles del mismo producto
        $detallesNuevos = [
            (object) [
                'id' => 1001,
                'producto_id' => $producto->id,
                'producto_empaque' => 50,
                'unidad_codigo' => 'BG',
                'cantidad' => 10,
                'cantidad_kgm' => 500,
                'valor_unitario' => 1.0,
                'total' => 10,
            ],
            (object) [
                'id' => 1002,
                'producto_id' => $producto->id,
                'producto_empaque' => 50,
                'unidad_codigo' => 'BG',
                'cantidad' => 5,
                'cantidad_kgm' => 250,
                'valor_unitario' => 1.0,
                'total' => 5,
            ],
        ];

        $movimientosUsados = [];
        $asignaciones = []; // Para verificar qué mov se asignó a cada detalle

        foreach ($detallesNuevos as $detalle) {
            $movNeutralizado = Movimiento::where('transaccion_tipo', 'prestamos')
                ->where('transaccion_id', $prestamoId)
                ->where('producto_id', $detalle->producto_id)
                ->where('entrada', 0)
                ->where('salida', 0)
                ->whereNotIn('id', $movimientosUsados)
                ->orderBy('id', 'asc')
                ->first();

            if ($movNeutralizado) {
                $movNeutralizado->update([
                    'detalle_id' => $detalle->id,
                    'cantidad' => $detalle->cantidad,
                    'cantidad_kg' => $detalle->cantidad_kgm,
                    'costo_unitario' => $detalle->valor_unitario,
                    'costo_total' => $detalle->total,
                    'entrada' => 0,
                    'salida' => $detalle->cantidad,
                    'comentario' => 'Rectificación de préstamo (Actualizado)',
                ]);
                $movimientosUsados[] = $movNeutralizado->id;
                $asignaciones[$detalle->id] = $movNeutralizado->id;
            }
        }

        // 4) Verificaciones
        $this->assertEqual(2, count($asignaciones), 'Ambos detalles encontraron un mov neutralizado');

        $mov1->refresh();
        $mov2->refresh();
        $mov1DetalleId = (int) $mov1->detalle_id;
        $mov2DetalleId = (int) $mov2->detalle_id;

        // Cada detalle debe tener un movimiento DISTINTO
        $this->assertNotEqual($mov1DetalleId, $mov2DetalleId, 'mov1.detalle_id ≠ mov2.detalle_id (asignaciones distintas)');

        // Verificar que los movimientos están restaurados (no neutralizados)
        $this->assertEqual(10, (float) $mov1->salida, 'mov1 restaurado: salida = 10');
        $this->assertEqual(5, (float) $mov2->salida, 'mov2 restaurado: salida = 5');
        $this->assertEqual(10, (float) $mov1->cantidad, 'mov1 restaurado: cantidad = 10');
        $this->assertEqual(5, (float) $mov2->cantidad, 'mov2 restaurado: cantidad = 5');
        $this->assertEqual(1001, $mov1DetalleId, 'mov1.detalle_id = 1001 (primer detalle)');
        $this->assertEqual(1002, $mov2DetalleId, 'mov2.detalle_id = 1002 (segundo detalle)');

        // Verificar que el comentario fue reemplazado
        $this->assertEqual(
            'Rectificación de préstamo (Actualizado)',
            $mov1->comentario,
            'mov1.comentario = Rectificación (reemplazó [ANULADO])'
        );

        echo "  → Test 2.1 completado.\n\n";
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 2.2: Preparada con 2 insumos del mismo producto
    // ─────────────────────────────────────────────────────────────────
    private function test2_2_preparadaConDosInsumosMismoProducto(): void
    {
        echo "[Test 2.2] Preparada con 2 insumos del mismo producto\n";

        $producto = $this->crearProductoTest('TEST-2.2');

        // 1) Crear preparada en estado 'anulada'
        $preparadaId = 99101;
        $preparada = Preparada::create([
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'producto_empaque' => 50,
            'fecha' => '2026-06-17 09:00:00',
            'ingreso_saco' => 100,
            'ingreso_kg' => 5000,
            'costo_unitario' => 1.0,
            'estado' => 'anulada',
            'rectificacion_count' => 0,
        ]);
        $preparada->id = $preparadaId;

        // 2) Simular 2 movimientos neutralizados de SALIDA del mismo insumo
        $movInsumo1 = Movimiento::create([
            'fecha' => '2026-06-17 09:00:00',
            'tipo' => 'PREPARADA_SALIDA',
            'transaccion_tipo' => 'preparadas',
            'transaccion_id' => $preparadaId,
            'detalle_id' => 2001,
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'empaque' => 50,
            'unidad_codigo' => 'BG',
            'cantidad' => 20,
            'cantidad_kg' => 1000,
            'entrada' => 0,
            'salida' => 20,
            'costo_unitario' => 1.0,
            'costo_total' => 20,
            'comentario' => '[ANULADO] Preparada neutralizada',
        ]);
        $movInsumo2 = Movimiento::create([
            'fecha' => '2026-06-17 09:00:00',
            'tipo' => 'PREPARADA_SALIDA',
            'transaccion_tipo' => 'preparadas',
            'transaccion_id' => $preparadaId,
            'detalle_id' => 2002,
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'empaque' => 50,
            'unidad_codigo' => 'BG',
            'cantidad' => 10,
            'cantidad_kg' => 500,
            'entrada' => 0,
            'salida' => 10,
            'costo_unitario' => 1.0,
            'costo_total' => 10,
            'comentario' => '[ANULADO] Preparada neutralizada',
        ]);

        // 3) Simular el loop de insumos de rectificarPreparada con 2 detalles del mismo insumo
        $detallesNuevos = [
            (object) [
                'id' => 3001,
                'producto_id' => $producto->id,
                'producto_empaque' => 50,
                'salida_saco' => 20,
                'salida_kg' => 1000,
            ],
            (object) [
                'id' => 3002,
                'producto_id' => $producto->id,
                'producto_empaque' => 50,
                'salida_saco' => 10,
                'salida_kg' => 500,
            ],
        ];

        $movimientosUsados = [];
        $asignaciones = [];

        foreach ($detallesNuevos as $detalle) {
            $movNeutralizado = Movimiento::where('transaccion_tipo', 'preparadas')
                ->where('transaccion_id', $preparadaId)
                ->where('producto_id', $detalle->producto_id)
                ->where('entrada', 0)
                ->where('salida', 0)
                ->where('cantidad_kg', 0)
                ->whereNotIn('id', $movimientosUsados)
                ->orderBy('id', 'asc')
                ->first();

            if ($movNeutralizado) {
                $movNeutralizado->update([
                    'detalle_id' => $detalle->id,
                    'cantidad' => $detalle->salida_saco,
                    'cantidad_kg' => $detalle->salida_kg,
                    'entrada' => 0,
                    'salida' => $detalle->salida_saco,
                    'comentario' => 'Rectificación de preparada (Actualizado)',
                ]);
                $movimientosUsados[] = $movNeutralizado->id;
                $asignaciones[$detalle->id] = $movNeutralizado->id;
            }
        }

        // 4) Verificaciones
        $this->assertEqual(2, count($asignaciones), 'Ambos insumos encontraron un mov neutralizado');

        $movInsumo1->refresh();
        $movInsumo2->refresh();

        // Cada insumo debe tener un movimiento DISTINTO
        $this->assertNotEqual(
            (int) $movInsumo1->detalle_id,
            (int) $movInsumo2->detalle_id,
            'insumo1.detalle_id ≠ insumo2.detalle_id (asignaciones distintas)'
        );

        // Verificar restauraciones
        $this->assertEqual(20, (float) $movInsumo1->salida, 'insumo1 restaurado: salida = 20');
        $this->assertEqual(10, (float) $movInsumo2->salida, 'insumo2 restaurado: salida = 10');
        $this->assertEqual(20, (float) $movInsumo1->cantidad, 'insumo1 restaurado: cantidad = 20');
        $this->assertEqual(10, (float) $movInsumo2->cantidad, 'insumo2 restaurado: cantidad = 10');
        $this->assertEqual(3001, (int) $movInsumo1->detalle_id, 'insumo1.detalle_id = 3001');
        $this->assertEqual(3002, (int) $movInsumo2->detalle_id, 'insumo2.detalle_id = 3002');

        $this->assertEqual(
            'Rectificación de preparada (Actualizado)',
            $movInsumo1->comentario,
            'insumo1.comentario = Rectificación (reemplazó [ANULADO])'
        );

        echo "  → Test 2.2 completado.\n\n";
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function crearProductoTest(string $prefijo, int $empaque = 50): Producto
    {
        $timestamp = time().'-'.random_int(1000, 9999);

        return Producto::create([
            'unidad_codigo' => 'BG',
            'afectacion_tipo_codigo' => '10',
            'linea_id' => 1,
            'codigo' => "{$prefijo}-{$timestamp}",
            'nombre' => "PRODUCTO {$prefijo} {$timestamp}",
            'empaque' => $empaque,
            'stock_almacen' => 0,
            'costo_unitario' => 0,
            'activo' => 1,
        ]);
    }

    private function crearClienteTest(): Cliente
    {
        $timestamp = time().'-'.random_int(1000, 9999);

        return Cliente::create([
            'documento_tipo_codigo' => '06', // RUC (código real en la BD)
            'documento_numero' => "20{$timestamp}",
            'razon_social' => "Cliente Test {$timestamp}",
            'direccion' => 'Dirección de prueba',
            'telefono' => '999999999',
            'email' => "test{$timestamp}@test.com",
            'saldo_credito' => 0,
        ]);
    }

    private function assertEqual(float $expected, float $actual, string $message): void
    {
        if (abs($expected - $actual) < 0.0001) {
            $this->assertsPassed++;
            echo "    ✓ {$message}\n";
        } else {
            $this->assertsFailed++;
            $this->failures[] = "{$message} → esperado: {$expected}, actual: {$actual}";
            echo "    ✗ {$message} → esperado: {$expected}, actual: {$actual}\n";
        }
    }

    private function assertNotEqual(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            $this->assertsPassed++;
            echo "    ✓ {$message}\n";
        } else {
            $this->assertsFailed++;
            $this->failures[] = "{$message} → ambos son: {$actual}";
            echo "    ✗ {$message} → ambos son: {$actual}\n";
        }
    }

    private function printSummary(): void
    {
        echo "\n================================================\n";
        echo "RESUMEN DE VERIFICACIÓN\n";
        echo "================================================\n";
        echo "Asserts exitosos:  {$this->assertsPassed}\n";
        echo "Asserts fallidos:  {$this->assertsFailed}\n";
        echo 'Total verificados: '.($this->assertsPassed + $this->assertsFailed)."\n";

        if (! empty($this->failures)) {
            echo "\nFALLOS:\n";
            foreach ($this->failures as $f) {
                echo "  - {$f}\n";
            }
        }

        echo "\nDB Rolled back.\n";

        if ($this->assertsFailed === 0) {
            echo "\n✓ TODOS LOS TESTS PASARON\n";
        } else {
            echo "\n✗ HAY TESTS FALLIDOS\n";
            exit(1);
        }
    }
}

(new VerifyKardexReuso)->run();
