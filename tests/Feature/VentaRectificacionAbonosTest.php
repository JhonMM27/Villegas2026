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

    public function test_rectificar_conserva_abonos_y_recalcula_el_saldo(): void
    {
        $this->insertarVenta(abonos: 3762, saldo: 8769);

        app(VentaService::class)->rectificarVenta(1, $this->datosRectificacion());

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'estado' => 'rectificada',
            'total' => 12531,
            'acuenta' => 0,
            'abonos' => 3762,
            'saldo' => 8769,
            'rectificacion_count' => 1,
        ]);
    }

    public function test_rectificar_aumentando_total_conserva_pagos_y_aumenta_solo_la_deuda(): void
    {
        $this->insertarVenta(abonos: 3762, saldo: 8769);

        app(VentaService::class)->rectificarVenta(
            1,
            $this->datosRectificacion(total: 14000)
        );

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'total' => 14000,
            'abonos' => 3762,
            'saldo' => 10238,
        ]);
    }

    public function test_rectificacion_por_debajo_de_lo_pagado_es_rechazada_y_revertida(): void
    {
        $this->insertarVenta(abonos: 3762, saldo: 8769);

        try {
            app(VentaService::class)->rectificarVenta(
                1,
                $this->datosRectificacion(total: 3000)
            );

            $this->fail('La rectificación debió ser rechazada.');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('ya tiene pagado S/ 3,762.00', $exception->getMessage());
        }

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'estado' => 'anulada',
            'total' => 12531,
            'abonos' => 3762,
            'saldo' => 8769,
            'rectificacion_count' => 0,
        ]);
        $this->assertDatabaseCount('venta_detalles', 1);
    }

    public function test_rectificar_sin_abonos_mantiene_el_comportamiento_normal(): void
    {
        $this->insertarVenta(abonos: 0, saldo: 12531);

        app(VentaService::class)->rectificarVenta(1, $this->datosRectificacion());

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'estado' => 'rectificada',
            'abonos' => 0,
            'saldo' => 12531,
        ]);
    }

    public function test_no_permite_cambiar_cliente_si_existen_cobranzas_aplicadas(): void
    {
        $this->insertarVenta(abonos: 0, saldo: 12531);
        $this->insertarCobranza();

        $datos = $this->datosRectificacion();
        $datos['cliente_id'] = 54;

        try {
            app(VentaService::class)->rectificarVenta(1, $datos);

            $this->fail('El cambio de cliente debió ser rechazado.');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('tiene cobranzas aplicadas', $exception->getMessage());
        }

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'cliente_id' => 53,
            'estado' => 'anulada',
            'abonos' => 0,
        ]);
    }

    public function test_tres_ciclos_de_anulacion_y_rectificacion_no_pierden_abonos(): void
    {
        $this->insertarVenta(abonos: 3762, saldo: 8769);
        $servicio = app(VentaService::class);

        for ($ciclo = 1; $ciclo <= 3; $ciclo++) {
            $servicio->rectificarVenta(1, $this->datosRectificacion());

            $this->assertDatabaseHas('ventas', [
                'id' => 1,
                'estado' => 'rectificada',
                'abonos' => 3762,
                'saldo' => 8769,
                'rectificacion_count' => $ciclo,
            ]);

            if ($ciclo < 3) {
                $servicio->anularVenta(1);
            }
        }
    }

    public function test_eliminar_cobranza_despues_de_rectificar_actualiza_abonos_y_saldo(): void
    {
        $this->insertarVenta(abonos: 3762, saldo: 8769);
        $this->insertarCobranza();

        app(VentaService::class)->rectificarVenta(1, $this->datosRectificacion());
        app(VentaProvisionalService::class)->deleteProvisional(10);

        $this->assertDatabaseHas('ventas', [
            'id' => 1,
            'estado' => 'rectificada',
            'abonos' => 0,
            'saldo' => 12531,
        ]);
        $this->assertDatabaseMissing('venta_provisionales', ['id' => 10]);
        $this->assertDatabaseMissing('venta_provisional_detalles', ['id' => 20]);
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

        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('user_nombre')->nullable();
            $table->unsignedInteger('cliente_id');
            $table->string('cliente_nombre')->nullable();
            $table->unsignedInteger('items')->nullable();
            $table->char('comprobante_tipo_codigo', 2);
            $table->string('comprobante_tipo_nombre')->nullable();
            $table->string('serie', 4);
            $table->integer('correlativo');
            $table->string('docpagoi')->nullable();
            $table->dateTime('fecha_venta');
            $table->char('pago_forma_codigo', 2);
            $table->string('pago_forma_nombre');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('moneda', 3);
            $table->decimal('op_gravada', 12, 2)->default(0);
            $table->decimal('op_exonerada', 12, 2)->default(0);
            $table->decimal('op_inafecta', 12, 2)->default(0);
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('importe_p', 12, 2)->default(0);
            $table->decimal('importe_d', 12, 2)->default(0);
            $table->decimal('importe_c', 12, 2)->default(0);
            $table->decimal('acuenta', 12, 2)->default(0);
            $table->decimal('abonos', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);
            $table->decimal('rentabilidad', 12, 4)->default(0);
            $table->string('estado');
            $table->unsignedInteger('rectificacion_count')->default(0);
            $table->timestamps();
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venta_id');
            $table->unsignedInteger('detalle')->nullable();
            $table->unsignedInteger('producto_id');
            $table->string('producto_nombre')->nullable();
            $table->decimal('producto_empaque', 10, 4)->nullable();
            $table->char('unidad_codigo', 3)->nullable();
            $table->decimal('salida_saco', 12, 4)->default(0);
            $table->decimal('salida_kg', 12, 4)->default(0);
            $table->decimal('cantidad', 12, 4);
            $table->decimal('entregado', 12, 4);
            $table->decimal('saldo', 12, 4)->default(0);
            $table->decimal('precio_unitario', 12, 4);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('porcentaje_impuesto', 8, 4)->default(0);
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('costo_unitario', 12, 4)->default(0);
            $table->decimal('costo_total', 12, 4)->default(0);
            $table->decimal('rentabilidad', 12, 4)->default(0);
        });

        Schema::create('movimientos', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('fecha');
            $table->string('tipo');
            $table->string('transaccion_tipo');
            $table->unsignedInteger('transaccion_id');
            $table->unsignedInteger('detalle_id')->nullable();
            $table->unsignedInteger('producto_id');
            $table->decimal('cantidad', 12, 4)->default(0);
            $table->decimal('cantidad_kg', 12, 4)->default(0);
        });

        Schema::create('venta_provisionales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('user_nombre')->nullable();
            $table->string('numero_recibo')->nullable();
            $table->string('numero_interno')->nullable();
            $table->dateTime('fecha_provisional');
            $table->decimal('monto', 12, 2);
            $table->decimal('libre', 12, 2)->default(0);
            $table->string('tipo')->default('APLICADO');
            $table->unsignedInteger('cliente_id');
            $table->string('cliente_nombre');
            $table->decimal('importe_p', 12, 2)->default(0);
            $table->decimal('importe_d', 12, 2)->default(0);
            $table->decimal('importe_c', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('venta_provisional_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venta_provisional_id');
            $table->unsignedInteger('venta_id')->nullable();
            $table->char('comprobante_tipo_codigo', 2)->nullable();
            $table->string('serie', 4)->nullable();
            $table->integer('correlativo')->nullable();
            $table->decimal('monto', 12, 2);
            $table->string('comentario')->nullable();
        });
    }

    private function sembrarCatalogos(): void
    {
        DB::table('clientes')->insert([
            ['id' => 53, 'razon_social' => 'ROSALES - ASOCIACION GALLITO'],
            ['id' => 54, 'razon_social' => 'OTRO CLIENTE'],
        ]);
        DB::table('comprobante_tipos')->insert([
            'codigo' => 'NP',
            'descripcion' => 'Nota de pedido',
        ]);
        DB::table('pago_formas')->insert([
            'codigo' => '01',
            'descripcion' => 'Crédito a 15 días',
        ]);
        DB::table('unidades')->insert([
            'codigo' => 'KGM',
            'descripcion' => 'Kilogramo',
        ]);
        DB::table('afectacion_tipos')->insert([
            'codigo' => '20',
            'porcentaje' => 0,
        ]);
        DB::table('productos')->insert([
            'id' => 1,
            'unidad_codigo' => 'KGM',
            'afectacion_tipo_codigo' => '20',
            'nombre' => 'PRODUCTO DE PRUEBA',
            'empaque' => 50,
            'costo_unitario' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertarVenta(
        float $abonos,
        float $saldo,
        string $estado = 'anulada'
    ): void {
        DB::table('ventas')->insert([
            'id' => 1,
            'user_id' => 1,
            'user_nombre' => 'Administrador',
            'cliente_id' => 53,
            'cliente_nombre' => 'ROSALES - ASOCIACION GALLITO',
            'items' => 1,
            'comprobante_tipo_codigo' => 'NP',
            'comprobante_tipo_nombre' => 'NP',
            'serie' => '01',
            'correlativo' => 126307,
            'docpagoi' => null,
            'fecha_venta' => '2026-06-24 14:53:00',
            'pago_forma_codigo' => '01',
            'pago_forma_nombre' => 'Crédito a 15 días',
            'fecha_vencimiento' => '2026-07-09',
            'moneda' => 'PEN',
            'op_gravada' => 0,
            'op_exonerada' => 12531,
            'op_inafecta' => 0,
            'impuesto' => 0,
            'total' => 12531,
            'importe_p' => 0,
            'importe_d' => 0,
            'importe_c' => 0,
            'acuenta' => 0,
            'abonos' => $abonos,
            'saldo' => $saldo,
            'rentabilidad' => 0,
            'estado' => $estado,
            'rectificacion_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('venta_detalles')->insert([
            'id' => 1,
            'venta_id' => 1,
            'detalle' => 1,
            'producto_id' => 1,
            'producto_nombre' => 'PRODUCTO DE PRUEBA',
            'producto_empaque' => 50,
            'unidad_codigo' => 'KGM',
            'salida_saco' => 1,
            'salida_kg' => 50,
            'cantidad' => 1,
            'entregado' => 1,
            'saldo' => 0,
            'precio_unitario' => 12531,
            'subtotal' => 12531,
            'porcentaje_impuesto' => 0,
            'impuesto' => 0,
            'total' => 12531,
            'costo_unitario' => 100,
            'costo_total' => 100,
            'rentabilidad' => 12431,
        ]);
    }

    private function insertarCobranza(): void
    {
        DB::table('venta_provisionales')->insert([
            'id' => 10,
            'user_id' => 1,
            'numero_recibo' => '43341',
            'fecha_provisional' => '2026-07-13 15:05:00',
            'monto' => 3762,
            'libre' => 0,
            'tipo' => 'APLICADO',
            'cliente_id' => 53,
            'cliente_nombre' => 'ROSALES - ASOCIACION GALLITO',
            'importe_p' => 3762,
            'importe_d' => 0,
            'importe_c' => 0,
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
            'monto' => 3762,
            'comentario' => 'COBRANZA A NP01-126307',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosRectificacion(float $total = 12531): array
    {
        return [
            'cliente_id' => 53,
            'comprobante_tipo_codigo' => 'NP',
            'serie' => '01',
            'correlativo' => 126307,
            'docpagoi' => null,
            'fecha_venta' => '2026-06-24 14:53:00',
            'fecha_vencimiento' => '2026-07-09',
            'pago_forma_codigo' => '01',
            'moneda' => 'PEN',
            'principal' => 0,
            'deposito' => 0,
            'consorcio' => 0,
            'total_cobranza' => 0,
            'op_gravada' => 0,
            'op_exonerada' => $total,
            'op_inafecta' => 0,
            'impuesto' => 0,
            'total' => $total,
            'detalles' => [[
                'producto_id' => 1,
                'unidad_codigo' => 'KGM',
                'empaque' => 50,
                'cantidad' => 1,
                'entrega' => 1,
                'precio_unitario' => $total,
                'total' => $total,
            ]],
        ];
    }
}
