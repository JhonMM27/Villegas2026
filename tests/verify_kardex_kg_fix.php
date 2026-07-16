<?php

namespace Tests\Scripts;

use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\User;
use App\Services\MovimientoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerifyKardexFix
{
    public function run()
    {
        echo "--- Iniciando Verificación de Fix Kardex (KG vs Saco) ---\n";

        // Asegurar que hay un usuario autenticado para el user_id del movimiento
        $user = User::first();
        if ($user) {
            Auth::login($user);
            echo "Logueado como: {$user->name}\n";
        }

        DB::beginTransaction();

        try {
            // 1. Crear producto de prueba (Base: SACO de 50kg)
            $producto = Producto::create([
                'nombre' => 'PRODUCTO TEST KG FIX '.time(),
                'codigo' => 'TEST-KG-'.time(),
                'linea_id' => 1,
                'unidad_codigo' => 'BG', // Saco/Bolsa
                'afectacion_tipo_codigo' => '10',
                'empaque' => 50, // 50kg por saco
                'stock_almacen' => 0,
                'stock_kardex' => 0,
                'costo_unitario' => 0,
                'activo' => 1,
            ]);
            echo "1. Producto creado: {$producto->nombre} (ID: {$producto->id}, Empaque: 50kg)\n";

            $movService = app(MovimientoService::class);

            // 2. Simular compra de 100kg a $2/kg (Total $200)
            // Esto equivale a 2 sacos. El costo por saco debe ser $100.
            echo "2. Registrando ingreso de 100kg a $2 cada uno (Total $200)...\n";
            $paramsIngreso = [
                'producto_id' => $producto->id,
                'tipo' => MovimientoService::TIPO_COMPRA,
                'transaccion_tipo' => 'compras',
                'transaccion_id' => 99999,
                'fecha' => now()->format('Y-m-d'),
                'cantidad' => 100, // Compró 100 kg
                'cantidad_kg' => 100,
                'costo_unitario' => 2, // $2 por cada 1 (kg)
                'empaque' => 1, // La unidad de compra es de 1kg
                'user_id' => $user ? $user->id : 1,
            ];

            $mov = $movService->registrarIngreso($paramsIngreso);
            $producto->refresh();

            echo "   - Movimiento registrado (Entrada Base): {$mov->entrada} sacos\n";
            echo "   - Costo Unitario guardado en Movimiento: \${$mov->costo_unitario} (Esperado: \$100)\n";
            echo "   - Costo Unitario del Producto: \${$producto->costo_unitario} (Esperado: \$100)\n";

            if (abs($mov->costo_unitario - 100) > 0.01) {
                echo "FAIL: El costo unitario en el movimiento es {$mov->costo_unitario}, se esperaba 100\n";
                throw new \Exception('ERROR: El costo unitario en el movimiento no es $100 (Costo por saco)');
            }

            // 3. Simular una venta de 1 saco
            // El costo de venta debe ser $100.
            echo "3. Registrando salida de 1 saco...\n";
            $movSalida = $movService->registrarSalida([
                'producto_id' => $producto->id,
                'tipo' => MovimientoService::TIPO_VENTA,
                'transaccion_tipo' => 'ventas',
                'transaccion_id' => 88888,
                'fecha' => now()->format('Y-m-d'),
                'cantidad' => 1,
                'empaque' => 50, // Vende sacos de 50kg
            ]);

            echo "   - Costo Unitario de la salida: \${$movSalida->costo_unitario} (Esperado: \$100)\n";
            echo "   - Costo Total de la salida: \${$movSalida->costo_total} (Esperado: \$100)\n";

            if (abs($movSalida->costo_total - 100) > 0.01) {
                throw new \Exception('ERROR: El costo de salida no es $100');
            }

            // 4. Probar recálculo de Kardex
            echo "4. Probando recálculo de Kardex...\n";
            $movService->recalcularKardexProducto($producto->id, $mov->id);
            $producto->refresh();

            echo "   - Costo Unitario tras recálculo: \${$producto->costo_unitario} (Esperado: \$100)\n";

            if (abs($producto->costo_unitario - 100) > 0.01) {
                throw new \Exception('ERROR: El recálculo destruyo el costo unitario');
            }

            echo "\n--- VERIFICACIÓN EXITOSA ---\n";

        } catch (\Exception $e) {
            echo "\n--- ERROR EN VERIFICACIÓN ---\n";
            echo $e->getMessage()."\n";
            echo $e->getTraceAsString()."\n";
        } finally {
            DB::rollBack();
            echo "DB Rolled back.\n";
        }
    }
}

(new VerifyKardexFix)->run();
