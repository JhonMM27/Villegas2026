<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\KardexController;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Services\MovimientoService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MovimientoServiceChronologyTest extends TestCase
{
    private MovimientoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('movimientos');
        Schema::dropIfExists('preparada_detalles');
        Schema::dropIfExists('preparadas');
        Schema::dropIfExists('nucleo_preparada_detalles');
        Schema::dropIfExists('nucleo_preparadas');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('lineas');

        Schema::create('lineas', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nombre');
        });

        DB::table('lineas')->insert(['id' => 1, 'nombre' => 'PRUEBA']);

        Schema::create('productos', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nombre');
            $table->unsignedInteger('linea_id')->nullable()->default(1);
            $table->unsignedInteger('empaque')->default(50);
            $table->string('unidad_codigo')->nullable();
            $table->decimal('stock_almacen', 10, 4)->default(0);
            $table->decimal('costo_unitario', 10, 4)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('movimientos', function (Blueprint $table): void {
            $table->increments('id');
            $table->dateTime('fecha');
            $table->string('tipo', 25);
            $table->string('transaccion_tipo', 30);
            $table->unsignedInteger('transaccion_id');
            $table->unsignedInteger('detalle_id')->nullable();
            $table->unsignedInteger('producto_id');
            $table->string('producto_nombre')->nullable();
            $table->unsignedInteger('empaque')->nullable();
            $table->string('unidad_codigo')->nullable();
            foreach (['cantidad', 'cantidad_kg', 'entrada', 'salida', 'costo_unitario', 'costo_total', 'stock_anterior', 'costo_actual', 'valor_anterior', 'stock_nuevo', 'costo_nuevo', 'valor_nuevo'] as $column) {
                $table->decimal($column, 12, 4)->default(0);
            }
            $table->unsignedInteger('user_id')->nullable();
            $table->string('comentario')->nullable();
            $table->timestamps();
        });

        Schema::create('preparadas', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('producto_id');
            $table->decimal('ingreso_saco', 12, 4)->default(0);
            $table->decimal('ingreso_soles', 12, 4)->default(0);
            $table->decimal('costo_unitario', 12, 4)->default(0);
        });

        Schema::create('preparada_detalles', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('preparada_id');
            $table->unsignedInteger('producto_id');
            $table->unsignedInteger('producto_empaque')->default(50);
            $table->decimal('salida_soles', 12, 4)->default(0);
            $table->decimal('precio_unitario', 12, 4)->default(0);
        });

        Schema::create('nucleo_preparadas', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('nucleo_id');
            $table->decimal('ingreso_saco', 12, 4)->default(0);
            $table->decimal('ingreso_soles', 12, 4)->default(0);
            $table->decimal('costo_unitario', 12, 4)->default(0);
        });

        Schema::create('nucleo_preparada_detalles', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('nucleo_preparada_id');
            $table->unsignedInteger('producto_id');
            $table->unsignedInteger('producto_empaque')->default(50);
            $table->decimal('costo_unitario', 12, 4)->default(0);
            $table->decimal('salida_soles', 12, 4)->default(0);
        });

        Producto::create([
            'nombre' => 'Producto de prueba',
            'empaque' => 50,
            'stock_almacen' => 100,
            'costo_unitario' => 10,
        ]);

        $this->service = app(MovimientoService::class);
    }

    public function test_rectificacion_retroactiva_del_mismo_dia_mantiene_orden_cronologico(): void
    {
        $this->salida('2026-07-15 08:22:00', 7, 1);
        $this->salida('2026-07-15 08:41:00', 1, 2);
        $this->salida('2026-07-15 08:46:00', 3.75, 3);
        $this->salida('2026-07-15 08:28:00', 9.25, 4);
        $this->salida('2026-07-15 09:15:00', 8.7, 5);

        $movimientos = Movimiento::orderBy('fecha')->orderBy('id')->get();
        $esperado = 100.0;

        foreach ($movimientos as $movimiento) {
            $this->assertEqualsWithDelta($esperado, (float) $movimiento->stock_anterior, 0.0001);
            $esperado = round($esperado - (float) $movimiento->salida, 4);
            $this->assertEqualsWithDelta($esperado, (float) $movimiento->stock_nuevo, 0.0001);
        }

        $this->assertEqualsWithDelta(70.3, (float) Producto::findOrFail(1)->stock_almacen, 0.0001);
    }

    public function test_anular_movimiento_retroactivo_no_omite_movimientos_con_id_menor(): void
    {
        $this->salida('2026-07-15 08:22:00', 7, 1);
        $this->salida('2026-07-15 08:41:00', 1, 2);
        $this->salida('2026-07-15 08:46:00', 3.75, 3);
        $retroactivo = $this->salida('2026-07-15 08:28:00', 9.25, 4);
        $this->salida('2026-07-15 09:15:00', 8.7, 5);

        $cantidadMovimientos = Movimiento::count();
        $this->service->recalcularKardexExcluyendo(1, [$retroactivo->id]);

        $this->assertSame($cantidadMovimientos, Movimiento::count());
        $this->assertEqualsWithDelta(79.55, (float) Producto::findOrFail(1)->stock_almacen, 0.0001);
        $this->assertEqualsWithDelta(0, (float) $retroactivo->fresh()->salida, 0.0001);

        $movimientos = Movimiento::orderBy('fecha')->orderBy('id')->get();
        for ($index = 1; $index < $movimientos->count(); $index++) {
            $this->assertEqualsWithDelta(
                (float) $movimientos[$index - 1]->stock_nuevo,
                (float) $movimientos[$index]->stock_anterior,
                0.0001
            );
        }
    }

    public function test_ingreso_retroactivo_recalcula_cpp_y_costo_de_salidas_posteriores(): void
    {
        $salida = $this->salida('2026-07-15 08:40:00', 10, 1);

        $this->service->registrarIngreso([
            'tipo' => MovimientoService::TIPO_AJUSTE_ENTRADA,
            'fecha' => '2026-07-15 08:30:00',
            'transaccion_tipo' => MovimientoService::TRANSACCION_AJUSTES,
            'transaccion_id' => 2,
            'producto_id' => 1,
            'producto_nombre' => 'Producto de prueba',
            'empaque' => 50,
            'unidad_codigo' => null,
            'cantidad' => 20,
            'cantidad_kg' => 1000,
            'costo_unitario' => 20,
        ]);

        $producto = Producto::findOrFail(1);
        $salida->refresh();

        $this->assertEqualsWithDelta(110, (float) $producto->stock_almacen, 0.0001);
        $this->assertEqualsWithDelta(11.6667, (float) $producto->costo_unitario, 0.0001);
        $this->assertEqualsWithDelta(11.6667, (float) $salida->costo_unitario, 0.0001);
        $this->assertEqualsWithDelta(116.667, (float) $salida->costo_total, 0.001);
    }

    public function test_apertura_legacy_es_absoluta_idempotente_y_protege_recalculos_futuros(): void
    {
        Movimiento::create([
            'fecha' => '2026-05-30 10:00:00',
            'tipo' => MovimientoService::TIPO_AJUSTE_ENTRADA,
            'transaccion_tipo' => MovimientoService::TRANSACCION_AJUSTES,
            'transaccion_id' => 10,
            'producto_id' => 1,
            'producto_nombre' => 'Producto de prueba',
            'empaque' => 50,
            'cantidad' => 50,
            'entrada' => 50,
            'costo_unitario' => 8,
            'costo_total' => 400,
            'stock_anterior' => 0,
            'costo_actual' => 0,
            'valor_anterior' => 0,
            'stock_nuevo' => 50,
            'costo_nuevo' => 8,
            'valor_nuevo' => 400,
        ]);

        Movimiento::create([
            'fecha' => '2026-06-01 08:00:00',
            'tipo' => MovimientoService::TIPO_AJUSTE_SALIDA,
            'transaccion_tipo' => MovimientoService::TRANSACCION_AJUSTES,
            'transaccion_id' => 11,
            'producto_id' => 1,
            'producto_nombre' => 'Producto de prueba',
            'empaque' => 50,
            'cantidad' => 10,
            'salida' => 10,
            'costo_unitario' => 10,
            'costo_total' => 100,
            'stock_anterior' => 100,
            'costo_actual' => 10,
            'valor_anterior' => 1000,
            'stock_nuevo' => 90,
            'costo_nuevo' => 10,
            'valor_nuevo' => 900,
        ]);

        $aperturas = [[
            'producto_id' => 1,
            'stock_apertura' => 100.0,
            'costo_apertura' => 12.0,
            'fuente_costo' => 'V_AUTORITATIVO',
        ]];

        $this->service->reconciliarAperturasLegacy($aperturas, '2026-05-31 23:59:59');
        $this->service->reconciliarAperturasLegacy($aperturas, '2026-05-31 23:59:59');

        $apertura = Movimiento::where('tipo', MovimientoService::TIPO_APERTURA_LEGADO)->firstOrFail();
        $salida = Movimiento::where('transaccion_id', 11)->firstOrFail();

        $this->assertSame(1, Movimiento::where('tipo', MovimientoService::TIPO_APERTURA_LEGADO)->count());
        $this->assertEqualsWithDelta(50, (float) $apertura->stock_anterior, 0.0001);
        $this->assertEqualsWithDelta(50, (float) $apertura->entrada, 0.0001);
        $this->assertEqualsWithDelta(100, (float) $apertura->stock_nuevo, 0.0001);
        $this->assertEqualsWithDelta(12, (float) $apertura->costo_nuevo, 0.0001);
        $this->assertEqualsWithDelta(100, (float) $salida->stock_anterior, 0.0001);
        $this->assertEqualsWithDelta(90, (float) Producto::findOrFail(1)->stock_almacen, 0.0001);

        $this->service->recalcularKardexProductoDesdeFecha(1, '2026-05-30 00:00:00');

        $this->assertEqualsWithDelta(100, (float) $apertura->fresh()->stock_nuevo, 0.0001);
        $this->assertEqualsWithDelta(90, (float) Producto::findOrFail(1)->stock_almacen, 0.0001);
    }

    public function test_comando_reconciliar_es_dry_run_por_defecto(): void
    {
        $ruta = tempnam(sys_get_temp_dir(), 'apertura_');
        file_put_contents(
            $ruta,
            "producto_id,nombre,stock_apertura,costo_apertura,fuente_costo\n".
            "1,Producto de prueba,100.0000,10.0000,V_AUTORITATIVO\n"
        );

        try {
            $this->artisan('kardex:reconciliar-apertura', [
                'archivo' => $ruta,
                '--fecha' => '2026-05-31',
            ])->assertSuccessful();
        } finally {
            @unlink($ruta);
        }

        $this->assertSame(0, Movimiento::count());
        $this->assertEqualsWithDelta(100, (float) Producto::findOrFail(1)->stock_almacen, 0.0001);
    }

    public function test_reporte_al_corte_lee_la_apertura_desde_movimientos(): void
    {
        $this->salida('2026-06-01 08:00:00', 10, 1);
        $this->service->reconciliarAperturasLegacy([[
            'producto_id' => 1,
            'stock_apertura' => 100,
            'costo_apertura' => 12,
            'fuente_costo' => 'V_AUTORITATIVO',
        ]], '2026-05-31 23:59:59');

        $controller = app(KardexController::class);
        $metodo = new \ReflectionMethod($controller, 'getStockAlCorteReportes');
        $reporte = $metodo->invoke($controller, Carbon::parse('2026-05-31'), true, true);

        $this->assertCount(1, $reporte);
        $this->assertEqualsWithDelta(100, (float) $reporte->first()->stock, 0.0001);
        $this->assertEqualsWithDelta(12, (float) $reporte->first()->costo_unitario, 0.0001);
        $this->assertEqualsWithDelta(90, (float) Producto::findOrFail(1)->stock_almacen, 0.0001);
    }

    public function test_replay_global_propaga_costos_de_preparadas_y_nucleos_en_orden(): void
    {
        Producto::create([
            'nombre' => 'Producto final',
            'empaque' => 50,
            'stock_almacen' => 0,
            'costo_unitario' => 0,
        ]);
        Producto::create([
            'nombre' => 'Nucleo final',
            'empaque' => 25,
            'stock_almacen' => 0,
            'costo_unitario' => 0,
        ]);

        DB::table('preparadas')->insert([
            'id' => 10,
            'producto_id' => 2,
            'ingreso_saco' => 2,
            'ingreso_soles' => 50,
            'costo_unitario' => 25,
        ]);
        DB::table('preparada_detalles')->insert([
            'id' => 100,
            'preparada_id' => 10,
            'producto_id' => 1,
            'producto_empaque' => 50,
            'salida_soles' => 50,
            'precio_unitario' => 5,
        ]);

        DB::table('nucleo_preparadas')->insert([
            'id' => 20,
            'nucleo_id' => 3,
            'ingreso_saco' => 4,
            'ingreso_soles' => 1,
            'costo_unitario' => 0.25,
        ]);
        DB::table('nucleo_preparada_detalles')->insert([
            'id' => 200,
            'nucleo_preparada_id' => 20,
            'producto_id' => 1,
            'producto_empaque' => 50,
            'costo_unitario' => 0.10,
            'salida_soles' => 20,
        ]);

        $this->movimiento([
            'fecha' => '2026-06-01 08:00:00',
            'tipo' => MovimientoService::TIPO_PREPARADA_SALIDA,
            'transaccion_tipo' => 'preparadas',
            'transaccion_id' => 10,
            'detalle_id' => 100,
            'producto_id' => 1,
            'salida' => 10,
            'cantidad' => 10,
            'costo_unitario' => 5,
        ]);
        $this->movimiento([
            'fecha' => '2026-06-01 08:00:00',
            'tipo' => MovimientoService::TIPO_PREPARADA_INGRESO,
            'transaccion_tipo' => 'preparadas',
            'transaccion_id' => 10,
            'producto_id' => 2,
            'entrada' => 2,
            'cantidad' => 2,
            'costo_unitario' => 25,
        ]);
        $this->movimiento([
            'fecha' => '2026-06-01 09:00:00',
            'tipo' => MovimientoService::TIPO_PREPARADA_INGRESO,
            'transaccion_tipo' => 'nucleo_preparadas',
            'transaccion_id' => 20,
            'producto_id' => 3,
            'entrada' => 4,
            'cantidad' => 4,
            'costo_unitario' => 0.25,
        ]);
        $this->movimiento([
            'fecha' => '2026-06-01 09:00:00',
            'tipo' => MovimientoService::TIPO_PREPARADA_SALIDA,
            'transaccion_tipo' => 'nucleo_preparadas',
            'transaccion_id' => 20,
            'detalle_id' => 200,
            'producto_id' => 1,
            'salida' => 2,
            'cantidad' => 2,
            'costo_unitario' => 10,
        ]);

        $this->service->reconciliarAperturasLegacy([
            ['producto_id' => 1, 'stock_apertura' => 100, 'costo_apertura' => 10, 'fuente_costo' => 'V'],
            ['producto_id' => 2, 'stock_apertura' => 0, 'costo_apertura' => 0, 'fuente_costo' => 'V'],
            ['producto_id' => 3, 'stock_apertura' => 0, 'costo_apertura' => 0, 'fuente_costo' => 'V'],
        ], '2026-05-31 23:59:59');

        $this->assertEqualsWithDelta(88, (float) Producto::findOrFail(1)->stock_almacen, 0.0001);
        $this->assertEqualsWithDelta(50, (float) Producto::findOrFail(2)->costo_unitario, 0.0001);
        $this->assertEqualsWithDelta(5, (float) Producto::findOrFail(3)->costo_unitario, 0.0001);
        $this->assertEqualsWithDelta(100, (float) DB::table('preparadas')->value('ingreso_soles'), 0.0001);
        $this->assertEqualsWithDelta(20, (float) DB::table('nucleo_preparadas')->value('ingreso_soles'), 0.0001);
        $this->assertEqualsWithDelta(0.2, (float) DB::table('nucleo_preparada_detalles')->value('costo_unitario'), 0.0001);
    }

    public function test_recalculo_normal_propaga_cpp_hacia_nucleo_preparada(): void
    {
        Producto::create([
            'nombre' => 'Nucleo final',
            'empaque' => 25,
            'stock_almacen' => 4,
            'costo_unitario' => 0.25,
        ]);
        DB::table('nucleo_preparadas')->insert([
            'id' => 30,
            'nucleo_id' => 2,
            'ingreso_saco' => 4,
            'ingreso_soles' => 1,
            'costo_unitario' => 0.25,
        ]);
        DB::table('nucleo_preparada_detalles')->insert([
            'id' => 300,
            'nucleo_preparada_id' => 30,
            'producto_id' => 1,
            'producto_empaque' => 50,
            'costo_unitario' => 0.10,
            'salida_soles' => 1,
        ]);

        $this->movimiento([
            'fecha' => '2026-06-01 09:00:00',
            'tipo' => MovimientoService::TIPO_PREPARADA_SALIDA,
            'transaccion_tipo' => 'nucleo_preparadas',
            'transaccion_id' => 30,
            'detalle_id' => 300,
            'producto_id' => 1,
            'salida' => 2,
            'cantidad' => 2,
            'costo_unitario' => 5,
            'stock_anterior' => 100,
            'costo_actual' => 10,
            'valor_anterior' => 1000,
            'stock_nuevo' => 98,
            'costo_nuevo' => 10,
            'valor_nuevo' => 980,
        ]);
        $this->movimiento([
            'fecha' => '2026-06-01 09:00:00',
            'tipo' => MovimientoService::TIPO_PREPARADA_INGRESO,
            'transaccion_tipo' => 'nucleo_preparadas',
            'transaccion_id' => 30,
            'producto_id' => 2,
            'entrada' => 4,
            'cantidad' => 4,
            'costo_unitario' => 0.25,
            'stock_nuevo' => 4,
        ]);

        $this->service->recalcularKardexProductoDesdeFecha(1, '2026-06-01 00:00:00');

        $this->assertEqualsWithDelta(20, (float) DB::table('nucleo_preparadas')->value('ingreso_soles'), 0.0001);
        $this->assertEqualsWithDelta(5, (float) Producto::findOrFail(2)->costo_unitario, 0.0001);
    }

    private function salida(string $fecha, float $cantidad, int $transaccionId): Movimiento
    {
        return $this->service->registrarSalida([
            'tipo' => MovimientoService::TIPO_AJUSTE_SALIDA,
            'fecha' => $fecha,
            'transaccion_tipo' => MovimientoService::TRANSACCION_AJUSTES,
            'transaccion_id' => $transaccionId,
            'producto_id' => 1,
            'producto_nombre' => 'Producto de prueba',
            'empaque' => 50,
            'unidad_codigo' => null,
            'cantidad' => $cantidad,
            'cantidad_kg' => $cantidad * 50,
        ]);
    }

    private function movimiento(array $datos): Movimiento
    {
        return Movimiento::create($datos + [
            'detalle_id' => null,
            'producto_nombre' => 'Producto de prueba',
            'empaque' => 50,
            'cantidad' => 0,
            'cantidad_kg' => 0,
            'entrada' => 0,
            'salida' => 0,
            'costo_unitario' => 0,
            'costo_total' => 0,
            'stock_anterior' => 0,
            'costo_actual' => 0,
            'valor_anterior' => 0,
            'stock_nuevo' => 0,
            'costo_nuevo' => 0,
            'valor_nuevo' => 0,
        ]);
    }
}
