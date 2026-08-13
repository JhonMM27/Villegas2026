<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\CuentaCorrienteClienteController;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class CuentaCorrienteClienteControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-24 12:00:00');

        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('razon_social');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
        });

        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cliente_id');
            $table->string('cliente_nombre')->nullable();
            $table->dateTime('fecha_venta');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('comprobante_tipo_codigo')->nullable();
            $table->string('serie')->nullable();
            $table->integer('correlativo')->nullable();
            $table->string('pago_forma_nombre')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('acuenta', 12, 2)->default(0);
            $table->decimal('abonos', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);
            $table->string('estado')->nullable();
            $table->string('user_nombre')->nullable();
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venta_id');
        });

        Schema::create('venta_provisionales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cliente_id');
            $table->dateTime('fecha_provisional');
            $table->string('numero_recibo')->nullable();
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('tipo')->nullable();
        });

        Schema::create('venta_provisional_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venta_provisional_id');
            $table->unsignedInteger('venta_id')->nullable();
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('comentario')->nullable();
            $table->string('comprobante_tipo_codigo')->nullable();
            $table->string('serie')->nullable();
            $table->integer('correlativo')->nullable();
        });

        DB::table('clientes')->insert([
            'id' => 131,
            'razon_social' => 'ALIPIO VILLEGAS',
            'direccion' => null,
            'telefono' => '989478494',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_creditos_por_cobrar_usa_el_saldo_guardado_y_excluye_pagadas_y_anuladas(): void
    {
        $this->insertarVenta(1, 'CONTADO PAGADO', 100, 0, 0, 'registrada');
        $this->insertarVenta(2, 'CREDITO PAGADO', 150, 150, 0, 'registrada');
        $this->insertarVenta(3, 'CREDITO PARCIAL', 200, 75, 125, 'registrada');
        $this->insertarVenta(4, 'CREDITO PENDIENTE', 300, 0, 300, 'registrada');
        $this->insertarVenta(5, 'VENTA ANULADA', 400, 0, 400, ' ANULADA ');

        DB::table('venta_provisionales')->insert([
            'id' => 10,
            'cliente_id' => 131,
            'fecha_provisional' => '2026-07-20 10:00:00',
            'numero_recibo' => '50001',
            'monto' => 1000,
            'tipo' => 'APLICADO',
        ]);

        DB::table('venta_provisional_detalles')->insert([
            'venta_provisional_id' => 10,
            'venta_id' => 3,
            'monto' => 400,
        ]);

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')
            ->once()
            ->with('a4', 'portrait')
            ->andReturnSelf();
        $pdf->shouldReceive('stream')
            ->once()
            ->andReturn(response('pdf'));

        Pdf::shouldReceive('loadView')
            ->once()
            ->with(
                'cuenta-cliente.reportes.creditos_por_cobrar_cliente_todos',
                Mockery::on(function (array $data): bool {
                    $this->assertSame([3, 4], $data['reportes']->pluck('id')->all());
                    $this->assertSame(500.0, $data['totTotal']);
                    $this->assertSame(75.0, $data['totAbonos']);
                    $this->assertSame(425.0, $data['totSaldo']);
                    $this->assertCount(1, $data['abonosSueltos']);
                    $this->assertSame(600.0, (float) $data['abonosSueltos']->first()->monto);

                    return true;
                })
            )
            ->andReturn($pdf);

        $request = Request::create(
            '/cuenta-corriente/cliente/creditos_cobrar_cliente_todos/pdf',
            'GET',
            ['cliente_ids' => [131]]
        );

        $response = app(CuentaCorrienteClienteController::class)
            ->creditosPorCobrarClienteTodosPdf($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_estado_de_cuenta_conserva_el_historial_pagado_y_termina_en_el_saldo_pendiente(): void
    {
        $this->insertarVenta(
            20,
            'PAGADA EN RANGO',
            100,
            100,
            0,
            'registrada',
            '2026-01-05 08:00:00'
        );
        $this->insertarVenta(
            21,
            'PENDIENTE',
            200,
            0,
            200,
            'registrada',
            '2026-01-07 08:00:00'
        );

        DB::table('venta_provisionales')->insert([
            'id' => 20,
            'cliente_id' => 131,
            'fecha_provisional' => '2026-01-06 10:00:00',
            'numero_recibo' => '50002',
            'monto' => 100,
            'tipo' => 'APLICADO',
        ]);

        DB::table('venta_provisional_detalles')->insert([
            'venta_provisional_id' => 20,
            'venta_id' => 20,
            'monto' => 100,
        ]);

        $method = new ReflectionMethod(
            CuentaCorrienteClienteController::class,
            'prepararDatosEstadoCuentaCliente'
        );

        $data = $method->invoke(
            app(CuentaCorrienteClienteController::class),
            131,
            Carbon::parse('2026-01-01')->startOfDay(),
            Carbon::parse('2026-01-31')->endOfDay()
        );

        $this->assertSame(200.0, $data['saldoFinal']);
        $this->assertSame([20, 21], $data['ventas']->pluck('id')->all());
        $this->assertCount(1, $data['ventas']->firstWhere('id', 20)->pagos);
        $this->assertSame(
            0.0,
            (float) $data['ventas']->firstWhere('id', 20)->pagos->first()->saldo_despues
        );
    }

    private function insertarVenta(
        int $id,
        string $documento,
        float $total,
        float $abonos,
        float $saldo,
        string $estado,
        string $fecha = '2026-07-15 08:00:00'
    ): void {
        DB::table('ventas')->insert([
            'id' => $id,
            'cliente_id' => 131,
            'cliente_nombre' => 'ALIPIO VILLEGAS',
            'fecha_venta' => $fecha,
            'fecha_vencimiento' => Carbon::parse($fecha)->addDays(15)->toDateString(),
            'comprobante_tipo_codigo' => 'NP',
            'serie' => '01',
            'correlativo' => $id,
            'pago_forma_nombre' => $documento,
            'total' => $total,
            'acuenta' => 0,
            'abonos' => $abonos,
            'saldo' => $saldo,
            'estado' => $estado,
            'user_nombre' => 'Administrador',
        ]);
    }
}
