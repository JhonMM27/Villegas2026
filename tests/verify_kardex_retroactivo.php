<?php

/**
 * Test de Regresión: Recálculo en Cascada para Movimientos Retroactivos.
 *
 * Cubre 5 escenarios del comportamiento retroactivo del kardex:
 *  1.1) Venta → Compra retroactiva (olvido de compra de la mañana).
 *  1.2) Compra A → Ventas → Compra B retroactiva intermedia.
 *  1.3) Múltiples retroactivos encadenados.
 *  1.4) Compra retroactiva con stock=0 (stock negativo previo).
 *  1.5) Venta retroactiva.
 *
 * Composición de la verificación:
 *  - Crea productos de prueba con timestamp en el nombre (evita choques).
 *  - Ejecuta operaciones reales con MovimientoService.
 *  - Compara stock_almacen, costo_unitario y saldos de movimientos.
 *  - Rollback al final (no contamina la base de datos).
 *
 * Ejecución:
 *   php tests/verify_kardex_retroactivo.php
 */

namespace Tests\Scripts;

use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\User;
use App\Services\MovimientoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerifyKardexRetroactivo
{
    /** @var int Contador de asserts exitosos */
    private int $assertsPassed = 0;

    /** @var int Contador de asserts fallidos */
    private int $assertsFailed = 0;

    /** @var array Lista de fallos para el reporte final */
    private array $failures = [];

    public function run(): void
    {
        echo "\n--- INICIANDO VERIFICACIÓN DE RECÁLCULO RETROACTIVO ---\n\n";

        $user = User::first();
        if ($user) {
            Auth::login($user);
            echo "Logueado como: {$user->name} (ID: {$user->id})\n\n";
        } else {
            echo "ERROR: No hay usuarios en la BD. Crea uno antes de correr el test.\n";

            return;
        }

        DB::beginTransaction();

        try {
            $this->test1_1_ventaLuegoCompraRetroactiva();
            $this->test1_2_compraIntermediaRetroactiva();
            $this->test1_3_multiplesRetroactivosEncadenados();
            $this->test1_4_compraRetroactivaConStockNegativo();
            $this->test1_5_ventaRetroactiva();
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
    // Test 1.1: Venta → Compra retroactiva
    // ─────────────────────────────────────────────────────────────────
    private function test1_1_ventaLuegoCompraRetroactiva(): void
    {
        echo "[Test 1.1] Venta → Compra retroactiva (olvido de compra de la mañana)\n";

        $producto = $this->crearProductoTest('TEST-1.1', empaque: 50);

        $movService = app(MovimientoService::class);

        // 1) Venta de 10 sacos a las 10:00 (no retroactiva, stock inicial = 0)
        $movVenta = $movService->registrarSalida([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_VENTA,
            'transaccion_tipo' => 'ventas',
            'transaccion_id' => 9001,
            'fecha' => '2026-06-17 10:00:00',
            'cantidad' => 10,
            'cantidad_kg' => 500,
            'empaque' => 50,
        ]);
        $this->assertEqual(10, (float) $movVenta->salida, 'Venta: salida = 10');
        $this->assertEqual(-10, (float) $movVenta->stock_nuevo, 'Venta: stock_nuevo = -10 (inicial 0 - 10)');

        // 2) Compra retroactiva de 100 sacos a $1/saco a las 09:00 (ANTES de la venta)
        $movCompra = $movService->registrarIngreso([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_COMPRA,
            'transaccion_tipo' => 'compras',
            'transaccion_id' => 9002,
            'fecha' => '2026-06-17 09:00:00',
            'cantidad' => 100,
            'cantidad_kg' => 5000,
            'costo_unitario' => 1.0,
            'empaque' => 50,
        ]);

        // 3) Verificaciones después del recálculo en cascada
        //    Estado final esperado: stock = 100 - 10 = 90, CPP = $1
        $producto->refresh();
        $movCompra->refresh();
        $movVenta->refresh();
        $this->assertEqual(90, (float) $producto->stock_almacen, 'Producto: stock_almacen final = 90');
        $this->assertEqual(1.0, (float) $producto->costo_unitario, 'Producto: costo_unitario = 1.0');

        // Movimiento de compra (09:00): stock_nuevo = 100, costo = 1
        $this->assertEqual(100, (float) $movCompra->stock_nuevo, 'Compra 09:00: stock_nuevo = 100');
        $this->assertEqual(1.0, (float) $movCompra->costo_nuevo, 'Compra 09:00: costo_nuevo = 1.0');

        // Movimiento de venta (10:00): RECALCULADO, stock_nuevo = 90, CPP = 1
        $this->assertEqual(100, (float) $movVenta->stock_anterior, 'Venta 10:00 (recalculada): stock_anterior = 100');
        $this->assertEqual(90, (float) $movVenta->stock_nuevo, 'Venta 10:00 (recalculada): stock_nuevo = 90');
        $this->assertEqual(1.0, (float) $movVenta->costo_unitario, 'Venta 10:00 (recalculada): costo_unitario = 1.0 (CPP)');

        echo "  → Test 1.1 completado.\n\n";
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 1.2: Compra A → Ventas → Compra B retroactiva intermedia
    // ─────────────────────────────────────────────────────────────────
    private function test1_2_compraIntermediaRetroactiva(): void
    {
        echo "[Test 1.2] Compra A → Ventas → Compra B retroactiva intermedia\n";

        $producto = $this->crearProductoTest('TEST-1.2', empaque: 50);

        $movService = app(MovimientoService::class);

        // 1) Compra A: 50 sacos a $1 a las 08:00
        $movCompraA = $movService->registrarIngreso([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_COMPRA,
            'transaccion_tipo' => 'compras',
            'transaccion_id' => 9101,
            'fecha' => '2026-06-17 08:00:00',
            'cantidad' => 50,
            'cantidad_kg' => 2500,
            'costo_unitario' => 1.0,
            'empaque' => 50,
        ]);
        $this->assertEqual(50, (float) $movCompraA->stock_nuevo, 'Compra A 08:00: stock_nuevo = 50');
        $this->assertEqual(1.0, (float) $movCompraA->costo_nuevo, 'Compra A 08:00: costo_nuevo = 1.0');

        // 2) Venta 1: 10 sacos a las 10:00
        $movVenta1 = $movService->registrarSalida([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_VENTA,
            'transaccion_tipo' => 'ventas',
            'transaccion_id' => 9102,
            'fecha' => '2026-06-17 10:00:00',
            'cantidad' => 10,
            'cantidad_kg' => 500,
            'empaque' => 50,
        ]);
        $this->assertEqual(40, (float) $movVenta1->stock_nuevo, 'Venta 1 10:00: stock_nuevo = 40');

        // 3) Venta 2: 5 sacos a las 11:00
        $movVenta2 = $movService->registrarSalida([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_VENTA,
            'transaccion_tipo' => 'ventas',
            'transaccion_id' => 9103,
            'fecha' => '2026-06-17 11:00:00',
            'cantidad' => 5,
            'cantidad_kg' => 250,
            'empaque' => 50,
        ]);
        $this->assertEqual(35, (float) $movVenta2->stock_nuevo, 'Venta 2 11:00: stock_nuevo = 35');

        // 4) Compra B RETROACTIVA: 30 sacos a $3 a las 09:00 (entre A y Venta 1)
        $movCompraB = $movService->registrarIngreso([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_COMPRA,
            'transaccion_tipo' => 'compras',
            'transaccion_id' => 9104,
            'fecha' => '2026-06-17 09:00:00',
            'cantidad' => 30,
            'cantidad_kg' => 1500,
            'costo_unitario' => 3.0,
            'empaque' => 50,
        ]);

        // 5) Verificaciones tras el recálculo en cascada
        //    Tras compra B: stock=50+30=80, CPP=(50*1+30*3)/80 = 1.75
        //    Tras venta 1:  stock=80-10=70, CPP=1.75
        //    Tras venta 2:  stock=70-5=65,  CPP=1.75
        $producto->refresh();
        $movCompraB->refresh();
        $movVenta1->refresh();
        $movVenta2->refresh();
        $this->assertEqual(65, (float) $producto->stock_almacen, 'Producto: stock_almacen final = 65');
        $this->assertEqual(1.75, (float) $producto->costo_unitario, 'Producto: costo_unitario = 1.75');

        // Movimiento Compra B (09:00): stock_nuevo = 80
        $this->assertEqual(80, (float) $movCompraB->stock_nuevo, 'Compra B 09:00: stock_nuevo = 80');
        $this->assertEqual(1.75, (float) $movCompraB->costo_nuevo, 'Compra B 09:00: costo_nuevo = 1.75');

        // Ventas recalculadas
        $this->assertEqual(80, (float) $movVenta1->stock_anterior, 'Venta 1 (recalc): stock_anterior = 80');
        $this->assertEqual(70, (float) $movVenta1->stock_nuevo, 'Venta 1 (recalc): stock_nuevo = 70');
        $this->assertEqual(1.75, (float) $movVenta1->costo_unitario, 'Venta 1 (recalc): costo_unitario = 1.75');
        $this->assertEqual(70, (float) $movVenta2->stock_anterior, 'Venta 2 (recalc): stock_anterior = 70');
        $this->assertEqual(65, (float) $movVenta2->stock_nuevo, 'Venta 2 (recalc): stock_nuevo = 65');

        echo "  → Test 1.2 completado.\n\n";
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 1.3: Múltiples retroactivos encadenados
    // ─────────────────────────────────────────────────────────────────
    private function test1_3_multiplesRetroactivosEncadenados(): void
    {
        echo "[Test 1.3] Múltiples retroactivos encadenados\n";

        $producto = $this->crearProductoTest('TEST-1.3', empaque: 25);

        $movService = app(MovimientoService::class);

        // 1) Venta: 10 sacos a las 12:00 (no retroactiva)
        $movVenta = $movService->registrarSalida([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_VENTA,
            'transaccion_tipo' => 'ventas',
            'transaccion_id' => 9201,
            'fecha' => '2026-06-17 12:00:00',
            'cantidad' => 10,
            'cantidad_kg' => 250,
            'empaque' => 25,
        ]);
        $this->assertEqual(-10, (float) $movVenta->stock_nuevo, 'Venta 12:00: stock_nuevo = -10');

        // 2) Compra retroactiva 1: 20 sacos a $1 a las 10:00
        $movCompra1 = $movService->registrarIngreso([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_COMPRA,
            'transaccion_tipo' => 'compras',
            'transaccion_id' => 9202,
            'fecha' => '2026-06-17 10:00:00',
            'cantidad' => 20,
            'cantidad_kg' => 500,
            'costo_unitario' => 1.0,
            'empaque' => 25,
        ]);

        // 3) Compra retroactiva 2: 30 sacos a $2 a las 09:00 (aún más atrás)
        $movCompra2 = $movService->registrarIngreso([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_COMPRA,
            'transaccion_tipo' => 'compras',
            'transaccion_id' => 9203,
            'fecha' => '2026-06-17 09:00:00',
            'cantidad' => 30,
            'cantidad_kg' => 750,
            'costo_unitario' => 2.0,
            'empaque' => 25,
        ]);

        // 4) Verificaciones
        //    Tras compra 2 (09:00): stock=30, CPP=2.0
        //    Tras compra 1 (10:00): stock=30+20=50, CPP=(30*2+20*1)/50=1.6
        //    Tras venta   (12:00): stock=50-10=40, CPP=1.6
        $producto->refresh();
        $movCompra1->refresh();
        $movCompra2->refresh();
        $movVenta->refresh();
        $this->assertEqual(40, (float) $producto->stock_almacen, 'Producto: stock_almacen final = 40');
        $this->assertEqual(1.6, (float) $producto->costo_unitario, 'Producto: costo_unitario = 1.6');

        $this->assertEqual(50, (float) $movCompra1->stock_nuevo, 'Compra 1 10:00: stock_nuevo = 50 (tras compra 2)');
        $this->assertEqual(1.6, (float) $movCompra1->costo_nuevo, 'Compra 1 10:00: costo_nuevo = 1.6');

        $this->assertEqual(50, (float) $movVenta->stock_anterior, 'Venta 12:00 (recalc): stock_anterior = 50');
        $this->assertEqual(40, (float) $movVenta->stock_nuevo, 'Venta 12:00 (recalc): stock_nuevo = 40');
        $this->assertEqual(1.6, (float) $movVenta->costo_unitario, 'Venta 12:00 (recalc): CPP = 1.6');

        echo "  → Test 1.3 completado.\n\n";
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 1.4: Compra retroactiva con stock negativo previo
    // ─────────────────────────────────────────────────────────────────
    private function test1_4_compraRetroactivaConStockNegativo(): void
    {
        echo "[Test 1.4] Compra retroactiva con stock negativo previo\n";

        $producto = $this->crearProductoTest('TEST-1.4', empaque: 50);

        $movService = app(MovimientoService::class);

        // 1) Venta: 5 sacos a las 10:00 → stock = -5
        $movService->registrarSalida([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_VENTA,
            'transaccion_tipo' => 'ventas',
            'transaccion_id' => 9301,
            'fecha' => '2026-06-17 10:00:00',
            'cantidad' => 5,
            'cantidad_kg' => 250,
            'empaque' => 50,
        ]);

        // 2) Compra retroactiva: 20 sacos a $2 a las 09:00
        $movCompra = $movService->registrarIngreso([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_COMPRA,
            'transaccion_tipo' => 'compras',
            'transaccion_id' => 9302,
            'fecha' => '2026-06-17 09:00:00',
            'cantidad' => 20,
            'cantidad_kg' => 1000,
            'costo_unitario' => 2.0,
            'empaque' => 50,
        ]);

        // 3) Verificaciones
        //    Tras compra (09:00): stock=20, CPP=2.0 (caso stock<=0 usa costo directo)
        //    Tras venta  (10:00): stock=20-5=15, CPP=2.0
        $producto->refresh();
        $movCompra->refresh();
        $this->assertEqual(15, (float) $producto->stock_almacen, 'Producto: stock_almacen final = 15');
        $this->assertEqual(2.0, (float) $producto->costo_unitario, 'Producto: costo_unitario = 2.0');

        // Movimiento de compra: stock_anterior recalculado = 0, stock_nuevo = 20
        $this->assertEqual(0, (float) $movCompra->stock_anterior, 'Compra 09:00: stock_anterior = 0 (no había mov previo)');
        $this->assertEqual(20, (float) $movCompra->stock_nuevo, 'Compra 09:00: stock_nuevo = 20');
        $this->assertEqual(2.0, (float) $movCompra->costo_nuevo, 'Compra 09:00: costo_nuevo = 2.0');

        echo "  → Test 1.4 completado.\n\n";
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 1.5: Venta retroactiva
    // ─────────────────────────────────────────────────────────────────
    private function test1_5_ventaRetroactiva(): void
    {
        echo "[Test 1.5] Venta retroactiva\n";

        $producto = $this->crearProductoTest('TEST-1.5', empaque: 50);

        $movService = app(MovimientoService::class);

        // 1) Compra: 100 sacos a $1 a las 14:00 (no retroactiva)
        $movCompra = $movService->registrarIngreso([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_COMPRA,
            'transaccion_tipo' => 'compras',
            'transaccion_id' => 9401,
            'fecha' => '2026-06-17 14:00:00',
            'cantidad' => 100,
            'cantidad_kg' => 5000,
            'costo_unitario' => 1.0,
            'empaque' => 50,
        ]);
        $this->assertEqual(100, (float) $movCompra->stock_nuevo, 'Compra 14:00: stock_nuevo = 100');
        $this->assertEqual(1.0, (float) $movCompra->costo_nuevo, 'Compra 14:00: costo_nuevo = 1.0');

        // 2) Venta retroactiva: 20 sacos a las 10:00 (ANTES de la compra)
        $movVenta = $movService->registrarSalida([
            'producto_id' => $producto->id,
            'tipo' => MovimientoService::TIPO_VENTA,
            'transaccion_tipo' => 'ventas',
            'transaccion_id' => 9402,
            'fecha' => '2026-06-17 10:00:00',
            'cantidad' => 20,
            'cantidad_kg' => 1000,
            'empaque' => 50,
        ]);

        // 3) Verificaciones
        //    Tras venta  (10:00): stock=0-20=-20, CPP=0 (no había compras antes)
        //    Tras compra (14:00): stock=-20+100=80, CPP=1.0 (caso stock<=0 usa costo directo)
        $producto->refresh();
        $movCompra->refresh();
        $movVenta->refresh();
        $this->assertEqual(80, (float) $producto->stock_almacen, 'Producto: stock_almacen final = 80');
        $this->assertEqual(1.0, (float) $producto->costo_unitario, 'Producto: costo_unitario = 1.0 (caso stock anterior <= 0)');

        // Venta retroactiva: stock_anterior=0, stock_nuevo=-20
        $this->assertEqual(0, (float) $movVenta->stock_anterior, 'Venta 10:00 (retroactiva): stock_anterior = 0');
        $this->assertEqual(-20, (float) $movVenta->stock_nuevo, 'Venta 10:00 (retroactiva): stock_nuevo = -20');

        echo "  → Test 1.5 completado.\n\n";
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Crea un producto de prueba con timestamp en el nombre.
     * Garantiza nombre único incluso si la transacción tiene rollback parcial.
     */
    private function crearProductoTest(string $prefijo, int $empaque): Producto
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

    /**
     * Verifica igualdad con tolerancia de 0.0001 (decimales del kardex).
     */
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

    /**
     * Imprime el resumen de la verificación.
     */
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

        echo "\nDB Rolled back (no se persistieron datos de prueba).\n";

        if ($this->assertsFailed === 0) {
            echo "\n✓ TODOS LOS TESTS PASARON\n";
        } else {
            echo "\n✗ HAY TESTS FALLIDOS - REVISAR IMPLEMENTACIÓN\n";
            exit(1);
        }
    }
}

(new VerifyKardexRetroactivo)->run();
