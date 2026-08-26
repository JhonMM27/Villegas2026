<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Movimiento;
use App\Services\MovimientoService;
use App\Services\VentaProvisionalService;
use App\Services\VentaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class VentaRectificacionAbonosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->crearEsquema();
        $this->sembrarCatalogos();

        $movimientos = Mockery::mock(MovimientoService::class);
        $movimientos->shouldReceive('bloquearProductos')->zeroOrMoreTimes();
        $movimientos->shouldReceive('registrarSalida')
            ->zeroOrMoreTimes()
            ->andReturn(new Movimiento);
        $movimientos->shouldReceive('recalcularKardexProducto')
            ->zeroOrMoreTimes();
        $movimientos->shouldReceive('recalcularKardexProductoDesdeFecha')
            ->zeroOrMoreTimes();
        $movimientos->shouldReceive('recalcularKardexExcluyendo')
            ->zeroOrMoreTimes();

        $this->app->instance(MovimientoService::class, $movimientos);
    }

    public function test_rectificar_con_cobranzas_provisionales_es_bloqueado(): void
    {
        $this->insertarVenta(abonos: 3762, saldo: 8769, estado: 'anulada');
        $this->insertarCobranza();

        try {
            app(VentaService::class)->rectificarVenta(1, $this->datosRectificacion());
            $this->fail('La rectificación con cobranzas aplicadas debió ser rechazada.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('tiene cobranzas provisionales aplicadas: Recibo #43341 (S/ 3762.00)', $e->getMessage());
        }
    }

    public function test_anular_con_cobranzas_provisionales_es_bloqueado(): void
    {
        $this->insertarVenta(abonos: 3762, saldo: 8769, estado: 'registrada');
        $this->insertarCobranza();

        try {
            app(VentaService::class)->anularVenta(1, 'Anulación de prueba');
            $this->fail('La anulación con cobranzas aplicadas debió ser rechazada.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('tiene cobranzas provisionales aplicadas: Recibo #43341 (S/ 3762.00)', $e->getMessage());
        }
    }

    public function test_liberar_cobranza_provisional_permite_anular_y_rectificar(): void
    {
        $this->insertarVenta(abonos: 3762, saldo: 8769, estado: 'registrada');
        $this->insertarCobranza();

        // 1. Intentar anular la venta -> falla porque tiene cobranza
        try {
            app(VentaService::class)->anularVenta(1, 'Anulación de prueba');
            $this->fail('Debió bloquear anulación');
        } catch (\Exception $e) {
            $this->assertStringContainsString('tiene cobranzas provisionales aplicadas', $e->getMessage());
        }

        // 2. Eliminar/liberar la cobranza provisional
        app(VentaProvisionalService::class)->deleteProvisional(10);

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'abonos' => 0,
            'saldo' => 12531,
        ]);

        // 3. Ahora anular la venta y luego rectificarla
        app(VentaService::class)->anularVenta(1, 'Anulación de prueba');
        app(VentaService::class)->rectificarVenta(1, $this->datosRectificacion());

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'estado' => 'rectificada',
            'total' => 12531,
            'acuenta' => 12531,
            'abonos' => 0,
            'saldo' => 0,
            'rectificacion_count' => 1,
        ]);
        $this->assertDatabaseHas('auditoria_eventos', [
            'tipo_evento' => 'rectificacion',
            'modulo' => 'ventas',
            'registro_id' => 1,
            'numero_rectificacion' => 1,
            'user_nombre' => 'Sistema',
        ]);

        $historial = DB::table('auditoria_eventos')->where('tipo_evento', 'rectificacion')->first();
        $cambios = json_decode($historial->cambios, true, flags: JSON_THROW_ON_ERROR);
        $this->assertContains('Pago inicial', array_column($cambios['cabecera'], 'campo'));
    }

    public function test_rectificar_sin_abonos_mantiene_el_comportamiento_normal(): void
    {
        $this->insertarVenta(abonos: 0, saldo: 12531, estado: 'anulada');

        app(VentaService::class)->rectificarVenta(1, $this->datosRectificacion());

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'estado' => 'rectificada',
            'acuenta' => 12531,
            'abonos' => 0,
            'saldo' => 0,
        ]);
    }

    public function test_abonos_migrados_sin_detalle_bloquean_operacion_con_mensaje_auditoria(): void
    {
        $this->insertarVenta(abonos: 3762, saldo: 8769, estado: 'anulada');

        try {
            app(VentaService::class)->rectificarVenta(1, $this->datosRectificacion());
            $this->fail('La rectificación con abonos migrados debió ser rechazada.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('registra abonos por S/ 3762.00 de los cuales no hay detalle de recibo identificable', $e->getMessage());
        }
    }

    public function test_auditoria_detecta_la_incidencia_sin_modificar_datos(): void
    {
        $this->insertarVenta(abonos: 0, saldo: 12531, estado: 'rectificada');
        $this->insertarCobranza();

        $this->artisan('ventas:validar-saldos-rectificados', ['--cliente_id' => 53])
            ->expectsOutputToContain('Esta auditoría no realizó ninguna modificación.')
            ->expectsOutputToContain('NP 01-126307')
            ->assertExitCode(1);

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'abonos' => 0,
            'saldo' => 12531,
        ]);
        $this->assertDatabaseHas('venta_provisional_detalles', [
            'id' => 20,
            'monto' => 3762,
        ]);
    }

    private function crearEsquema(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('razon_social');
            $table->timestamps();
        });

        Schema::create('comprobante_tipos', function (Blueprint $table) {
            $table->char('codigo', 2)->primary();
            $table->string('descripcion')->nullable();
        });

        Schema::create('pago_formas', function (Blueprint $table) {
            $table->char('codigo', 2)->primary();
            $table->string('descripcion');
        });

        Schema::create('unidades', function (Blueprint $table) {
            $table->char('codigo', 3)->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
        });

        Schema::create('afectacion_tipos', function (Blueprint $table) {
            $table->char('codigo', 2)->primary();
            $table->decimal('porcentaje', 6, 4)->default(0);
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id');
            $table->char('unidad_codigo', 3);
            $table->char('afectacion_tipo_codigo', 2);
            $table->string('nombre');
            $table->decimal('empaque', 10, 4);
            $table->decimal('costo_unitario', 12, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('movimientos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tipo');
            $table->dateTime('fecha');
            $table->string('transaccion_tipo');
            $table->unsignedInteger('transaccion_id');
            $table->unsignedInteger('detalle_id')->nullable();
            $table->unsignedInteger('producto_id');
            $table->string('producto_nombre');
            $table->decimal('empaque', 10, 4);
            $table->char('unidad_codigo', 3);
            $table->decimal('cantidad', 12, 4);
            $table->decimal('cantidad_kg', 12, 4);
            $table->timestamps();
        });

        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('user_nombre')->nullable();
            $table->unsignedInteger('cliente_id');
            $table->string('cliente_nombre')->nullable();
            $table->unsignedInteger('items')->nullable();

            $table->char('comprobante_tipo_codigo', 2);
            $table->string('comprobante_tipo_nombre', 50)->nullable();
            $table->string('serie', 10);
            $table->unsignedInteger('correlativo');
            $table->string('docpagoi', 30)->nullable();
            $table->dateTime('fecha_venta');
            $table->dateTime('fecha_vencimiento')->nullable();

            $table->char('pago_forma_codigo', 2);
            $table->string('pago_forma_nombre', 50)->nullable();
            $table->string('moneda', 3)->default('PEN');

            $table->decimal('op_gravada', 12, 2)->default(0);
            $table->decimal('op_exonerada', 12, 2)->default(0);
            $table->decimal('op_inafecta', 12, 2)->default(0);
            $table->decimal('impuesto', 12, 2)->default(0);

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('icbper', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('acuenta', 12, 2)->default(0);
            $table->decimal('abonos', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);

            $table->decimal('importe_p', 12, 2)->default(0);
            $table->decimal('importe_d', 12, 2)->default(0);
            $table->decimal('importe_c', 12, 2)->default(0);
            $table->decimal('rentabilidad', 12, 2)->default(0);

            $table->string('estado', 20)->default('registrada');
            $table->unsignedSmallInteger('rectificacion_count')->default(0);
            $table->dateTime('rectificada_at')->nullable();
            $table->text('rectificacion_motivo')->nullable();

            $table->text('comentario')->nullable();
            $table->string('hash', 100)->nullable();
            $table->text('qr')->nullable();
            $table->timestamps();
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venta_id');
            $table->unsignedInteger('detalle')->default(1);
            $table->unsignedInteger('producto_id');
            $table->string('producto_nombre');
            $table->decimal('producto_empaque', 10, 4);
            $table->char('unidad_codigo', 3);
            $table->char('afectacion_tipo_codigo', 2)->default('10');

            $table->decimal('salida_saco', 12, 4)->default(0);
            $table->decimal('salida_kg', 12, 4)->default(0);
            $table->decimal('cantidad', 12, 4);
            $table->decimal('entregado', 12, 4);
            $table->decimal('saldo', 12, 4)->default(0);
            $table->decimal('saldo_entrega', 12, 4)->default(0);

            $table->decimal('precio_unitario', 12, 4);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('porcentaje_impuesto', 6, 4)->default(0.18);
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('icbper', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            $table->decimal('costo_unitario', 12, 4)->default(0);
            $table->decimal('costo_total', 12, 4)->default(0);
            $table->decimal('utilidad', 12, 4)->default(0);
            $table->decimal('rentabilidad', 12, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('venta_provisionales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('user_nombre')->nullable();
            $table->dateTime('fecha_provisional');
            $table->string('numero_recibo', 20);
            $table->string('numero_interno', 20)->nullable();
            $table->unsignedInteger('cliente_id');
            $table->string('cliente_nombre');
            $table->decimal('monto', 12, 2);
            $table->decimal('libre', 12, 2);
            $table->decimal('importe_p', 12, 2)->default(0);
            $table->decimal('importe_d', 12, 2)->default(0);
            $table->decimal('importe_c', 12, 2)->default(0);
            $table->string('tipo', 20);
            $table->timestamps();
        });

        Schema::create('venta_provisional_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venta_provisional_id');
            $table->unsignedInteger('venta_id')->nullable();
            $table->char('comprobante_tipo_codigo', 2)->nullable();
            $table->string('serie', 10)->nullable();
            $table->unsignedInteger('correlativo')->nullable();
            $table->decimal('monto', 12, 2);
            $table->string('comentario')->nullable();
            $table->timestamps();
        });

        Schema::create('rectificacion_historiales', function (Blueprint $table) {
            $table->id();
            $table->string('modulo', 50);
            $table->unsignedBigInteger('registro_id');
            $table->unsignedTinyInteger('numero_rectificacion');
            $table->string('registro_referencia', 150);
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->string('user_nombre', 100);
            $table->string('motivo', 500)->nullable();
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');
            $table->json('cambios');
            $table->timestamps();
            $table->unique(['modulo', 'registro_id', 'numero_rectificacion']);
        });

        Schema::create('auditoria_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_evento', 20);
            $table->string('modulo', 50);
            $table->unsignedBigInteger('registro_id');
            $table->unsignedTinyInteger('numero_rectificacion')->nullable();
            $table->string('registro_referencia', 150);
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->string('user_nombre', 100);
            $table->string('motivo', 500);
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');
            $table->json('cambios');
            $table->timestamps();
        });
    }

    private function sembrarCatalogos(): void
    {
        DB::table('clientes')->insert([
            'id' => 53,
            'razon_social' => 'CLIENTE TEST',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('clientes')->insert([
            'id' => 54,
            'razon_social' => 'OTRO CLIENTE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('comprobante_tipos')->insert([
            ['codigo' => 'NP', 'descripcion' => 'NOTA DE PEDIDO'],
        ]);

        DB::table('pago_formas')->insert([
            ['codigo' => '01', 'descripcion' => 'EFECTIVO'],
        ]);

        DB::table('unidades')->insert([
            ['codigo' => 'SAC', 'descripcion' => 'SACO', 'activo' => 1],
        ]);

        DB::table('afectacion_tipos')->insert([
            ['codigo' => '10', 'porcentaje' => 0.18],
        ]);

        DB::table('productos')->insert([
            'id' => 77,
            'unidad_codigo' => 'SAC',
            'afectacion_tipo_codigo' => '10',
            'nombre' => 'PRODUCTO TEST',
            'empaque' => 40.0,
            'costo_unitario' => 50.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertarVenta(float $abonos, float $saldo, string $estado = 'anulada'): void
    {
        DB::table('ventas')->insert([
            'id' => 1,
            'user_id' => 1,
            'user_nombre' => 'TEST USER',
            'cliente_id' => 53,
            'cliente_nombre' => 'CLIENTE TEST',
            'items' => 1,
            'comprobante_tipo_codigo' => 'NP',
            'comprobante_tipo_nombre' => 'NOTA DE PEDIDO',
            'serie' => '01',
            'correlativo' => 126307,
            'docpagoi' => '01-126307',
            'fecha_venta' => '2026-03-30 08:00:00',
            'pago_forma_codigo' => '01',
            'pago_forma_nombre' => 'EFECTIVO',
            'moneda' => 'PEN',
            'op_gravada' => 10619.49,
            'op_exonerada' => 0,
            'op_inafecta' => 0,
            'impuesto' => 1911.51,
            'subtotal' => 10619.49,
            'igv' => 1911.51,
            'icbper' => 0,
            'total' => 12531.00,
            'acuenta' => 0,
            'abonos' => $abonos,
            'saldo' => $saldo,
            'importe_p' => 12531.00,
            'importe_d' => 0,
            'importe_c' => 0,
            'rentabilidad' => 5619.49,
            'estado' => $estado,
            'rectificacion_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('venta_detalles')->insert([
            'id' => 1,
            'venta_id' => 1,
            'detalle' => 1,
            'producto_id' => 77,
            'producto_nombre' => 'PRODUCTO TEST',
            'producto_empaque' => 40.0,
            'unidad_codigo' => 'SAC',
            'afectacion_tipo_codigo' => '10',
            'salida_saco' => 100,
            'salida_kg' => 4000,
            'cantidad' => 100.0,
            'entregado' => 100.0,
            'saldo' => 0,
            'saldo_entrega' => 0,
            'precio_unitario' => 125.31,
            'subtotal' => 10619.49,
            'porcentaje_impuesto' => 0.18,
            'impuesto' => 1911.51,
            'igv' => 1911.51,
            'icbper' => 0,
            'total' => 12531.00,
            'costo_unitario' => 50.0,
            'costo_total' => 5000.0,
            'utilidad' => 5619.49,
            'rentabilidad' => 5619.49,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertarCobranza(): void
    {
        DB::table('venta_provisionales')->insert([
            'id' => 10,
            'user_id' => 1,
            'user_nombre' => 'TEST USER',
            'fecha_provisional' => '2026-04-01 10:00:00',
            'numero_recibo' => '43341',
            'numero_interno' => 'INT-10',
            'cliente_id' => 53,
            'cliente_nombre' => 'CLIENTE TEST',
            'monto' => 3762.00,
            'libre' => 0.00,
            'importe_p' => 3762.00,
            'importe_d' => 0,
            'importe_c' => 0,
            'tipo' => 'APLICADO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('venta_provisional_detalles')->insert([
            'id' => 20,
            'venta_provisional_id' => 10,
            'venta_id' => 1,
            'comprobante_tipo_codigo' => 'NP',
            'serie' => '01',
            'correlativo' => 126307,
            'monto' => 3762.00,
            'comentario' => 'COBRANZA NP 01-126307',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function datosRectificacion(float $total = 12531.00): array
    {
        $subtotal = round($total / 1.18, 2);
        $igv = round($total - $subtotal, 2);

        return [
            'fecha_venta' => '2026-03-30 08:00:00',
            'cliente_id' => 53,
            'comprobante_tipo_codigo' => 'NP',
            'serie' => '01',
            'correlativo' => 126307,
            'docpagoi' => '01-126307',
            'pago_forma_codigo' => '01',
            'rectificacion_motivo' => 'Ajuste de prueba',
            'op_gravada' => $subtotal,
            'op_exonerada' => 0,
            'op_inafecta' => 0,
            'impuesto' => $igv,
            'total' => $total,
            'principal' => $total,
            'deposito' => 0,
            'consorcio' => 0,
            'detalles' => [
                [
                    'producto_id' => 77,
                    'producto_nombre' => 'PRODUCTO TEST',
                    'producto_empaque' => 40.0,
                    'empaque' => 40.0,
                    'unidad_codigo' => 'SAC',
                    'afectacion_tipo_codigo' => '10',
                    'cantidad' => 100.0,
                    'entregado' => 100.0,
                    'entrega' => 100.0,
                    'precio_unitario' => round($total / 100, 4),
                    'total' => $total,
                ],
            ],
        ];
    }
}
