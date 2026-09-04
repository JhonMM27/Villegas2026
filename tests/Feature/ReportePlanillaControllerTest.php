<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\PlanillaAdelantoController;
use App\Http\Controllers\ReportePlanillaController;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ReportePlanillaControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-30 12:00:00');

        Schema::create('empleados', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
            $table->string('dni')->nullable();
            $table->string('telefono')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->string('estado')->default('activo');
            $table->timestamps();
        });

        Schema::create('empleado_sueldos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empleado_id');
            $table->decimal('sueldo_base', 10, 2);
            $table->decimal('sueldo_real', 10, 2);
            $table->decimal('sueldo_planilla', 10, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->string('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedInteger('registrado_por')->nullable();
            $table->timestamps();
        });

        Schema::create('planilla_pagos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empleado_id');
            $table->unsignedInteger('empleado_sueldo_id')->nullable();
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->decimal('sueldo_base', 10, 2)->default(0);
            $table->decimal('horas_extras', 10, 2)->default(0);
            $table->decimal('adelantos', 10, 2)->default(0);
            $table->decimal('cts_planilla', 10, 2)->nullable();
            $table->decimal('cts_sueldo_real', 10, 2)->nullable();
            $table->decimal('dias_faltados', 10, 2)->default(0);
            $table->decimal('descuento_faltas', 10, 2)->default(0);
            $table->decimal('total_pagar', 10, 2);
            $table->date('fecha_pago')->nullable();
            $table->string('estado')->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->decimal('importe_p', 10, 2)->default(0);
            $table->decimal('importe_d', 10, 2)->default(0);
            $table->decimal('importe_c', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('planilla_adelantos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empleado_id');
            $table->unsignedInteger('numero_interno')->nullable();
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->unsignedInteger('planilla_pago_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('importe_p', 10, 2)->default(0);
            $table->decimal('importe_d', 10, 2)->default(0);
            $table->decimal('importe_c', 10, 2)->default(0);
            $table->timestamps();
        });

        $this->insertarEmpleadoYPago();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_reporte_pagado_muestra_sueldo_completo_y_cero_pendiente(): void
    {
        $datos = $this->prepararReporte('2026-07-01', '2026-07-30');

        $this->assertSame(1130.0, $datos['resumen']['total_sueldo_planilla']);
        $this->assertSame(2200.0, $datos['resumen']['total_sueldo_real']);
        $this->assertSame(150.0, $datos['resumen']['total_adelantos']);
        $this->assertSame(920.0, $datos['resumen']['total_sueldo_base']);
        $this->assertSame(920.0, $datos['resumen']['total_pagado']);
        $this->assertSame(0.0, $datos['resumen']['total_pendiente']);
        $this->assertSame(
            1130.0,
            (float) $datos['pagos']->first()->sueldo_planilla_proporcional
        );
        $this->assertSame(
            920.0,
            (float) $datos['pagos']->first()->monto_proporcional
        );

        $html = view('planilla.reportes.empleados_pdf', array_merge($datos, [
            'empresa' => (object) [
                'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
                'direccion' => 'Carretera Pomalca KM 3',
                'ruc' => '20538937321',
            ],
        ]))->render();

        $this->assertStringContainsString('Total Pagado:', $html);
        $this->assertStringNotContainsString('Total Pendiente:', $html);
        $this->assertStringNotContainsString('1,093.55', $html);
    }

    public function test_pdf_individual_y_descarga_masiva_comparten_el_mismo_calculo(): void
    {
        $pdfIndividual = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfIndividual->shouldReceive('stream')
            ->once()
            ->with('reporte_empleado_6.pdf')
            ->andReturn(response('pdf'));

        $pdfLote = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfLote->shouldReceive('download')
            ->once()
            ->with('reporte_empleado_6_sueldo.pdf')
            ->andReturn(response('pdf'));

        Pdf::shouldReceive('loadView')
            ->twice()
            ->with(
                'planilla.reportes.empleados_pdf',
                Mockery::on(function (array $datos): bool {
                    $this->assertSame(
                        1130.0,
                        (float) $datos['pagos']->first()->sueldo_planilla_proporcional
                    );
                    $this->assertSame(920.0, $datos['resumen']['total_pagado']);
                    $this->assertSame(0.0, $datos['resumen']['total_pendiente']);

                    return true;
                })
            )
            ->andReturn($pdfIndividual, $pdfLote);

        $request = Request::create('/reporte', 'GET', [
            'empleado_id' => 6,
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-07-31',
        ]);
        $controller = app(ReportePlanillaController::class);

        $this->assertSame(200, $controller->empleadoPdf($request)->getStatusCode());
        $this->assertSame(
            200,
            $controller->empleadoConSueldoPdf($request)->getStatusCode()
        );
    }

    public function test_resumen_separa_pagos_pagados_y_pendientes(): void
    {
        DB::table('planilla_pagos')->insert([
            'empleado_id' => 6,
            'empleado_sueldo_id' => 1,
            'mes' => 8,
            'anio' => 2026,
            'sueldo_base' => 300,
            'horas_extras' => 0,
            'adelantos' => 0,
            'dias_faltados' => 0,
            'descuento_faltas' => 0,
            'total_pagar' => 300,
            'estado' => 'pendiente',
            'importe_p' => 300,
            'importe_d' => 0,
            'importe_c' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $datos = $this->prepararReporte('2026-07-15', '2026-08-14');

        $this->assertSame(920.0, $datos['resumen']['total_pagado']);
        $this->assertSame(300.0, $datos['resumen']['total_pendiente']);
        $this->assertSame(1220.0, $datos['resumen']['total_general']);
    }

    public function test_index_inicia_con_el_mes_actual_completo(): void
    {
        $view = app(ReportePlanillaController::class)->index();
        $datos = $view->getData();

        $this->assertSame('2026-07-01', $datos['fechaInicioPredeterminada']);
        $this->assertSame('2026-07-31', $datos['fechaFinPredeterminada']);

        $html = $view->render();
        $this->assertSame(2, substr_count($html, 'value="2026-07-01"'));
        $this->assertSame(2, substr_count($html, 'value="2026-07-31"'));
        $this->assertStringContainsString(route('reportes.planilla.historial_sueldos_pdf'), $html);
        $this->assertStringContainsString('data-allow-long-range="true"', $html);
        $this->assertStringContainsString('Historial de sueldos', $html);
    }

    public function test_historial_clasifica_aumentos_y_muestra_los_tres_sueldos(): void
    {
        DB::table('empleado_sueldos')->where('id', 1)->update([
            'vigente_hasta' => '2026-07-31',
        ]);
        DB::table('empleado_sueldos')->insert([
            'id' => 2,
            'empleado_id' => 6,
            'sueldo_base' => 1270,
            'sueldo_planilla' => 1130,
            'sueldo_real' => 2400,
            'vigente_desde' => '2026-08-01',
            'motivo' => 'Aumento',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(ReportePlanillaController::class);
        $method = new ReflectionMethod($controller, 'prepararHistorialSueldos');
        $method->setAccessible(true);
        $datos = $method->invoke($controller, 6, null, null);

        $this->assertCount(2, $datos['historial']);
        $this->assertSame('Inicial', $datos['historial'][0]->tipo_cambio);
        $this->assertSame('Aumento', $datos['historial'][1]->tipo_cambio);
        $this->assertSame(200.0, $datos['historial'][1]->variacion_base);
        $this->assertSame(200.0, $datos['historial'][1]->variacion_real);
        $this->assertSame(0.0, $datos['historial'][1]->variacion_planilla);
    }

    public function test_reporte_muestra_el_sueldo_vigente_de_cada_mes(): void
    {
        DB::table('empleado_sueldos')->where('id', 1)->update([
            'vigente_hasta' => '2026-07-31',
        ]);
        DB::table('empleado_sueldos')->insert([
            'id' => 2,
            'empleado_id' => 6,
            'sueldo_base' => 1270,
            'sueldo_planilla' => 1130,
            'sueldo_real' => 2400,
            'vigente_desde' => '2026-08-01',
            'motivo' => 'Aumento',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('planilla_pagos')->insert([
            'empleado_id' => 6,
            'empleado_sueldo_id' => 2,
            'mes' => 8,
            'anio' => 2026,
            'sueldo_base' => 1270,
            'horas_extras' => 0,
            'adelantos' => 0,
            'dias_faltados' => 0,
            'descuento_faltas' => 0,
            'total_pagar' => 1270,
            'estado' => 'pendiente',
            'importe_p' => 1270,
            'importe_d' => 0,
            'importe_c' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $datos = $this->prepararReporte('2026-07-01', '2026-08-31');
        $julio = $datos['pagos']->firstWhere('mes', 7);
        $agosto = $datos['pagos']->firstWhere('mes', 8);

        $this->assertSame(2200.0, $julio->sueldo_real_historico_reporte);
        $this->assertSame(1070.0, $julio->sueldo_base_historico);
        $this->assertSame(2400.0, $agosto->sueldo_real_historico_reporte);
        $this->assertSame(1270.0, $agosto->sueldo_base_historico);
        $this->assertSame(2260.0, $datos['resumen']['total_sueldo_planilla']);
        $this->assertSame(4600.0, $datos['resumen']['total_sueldo_real']);
        $this->assertSame(2340.0, $datos['resumen']['total_sueldo_base_historico']);

        $html = view('planilla.reportes.empleados_pdf', array_merge($datos, [
            'empresa' => (object) [
                'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
                'direccion' => 'Carretera Pomalca KM 3',
                'ruc' => '20538937321',
            ],
        ]))->render();

        $this->assertStringContainsString('2,400.00', $html);
        $this->assertStringContainsString('1,270.00', $html);
    }

    public function test_reporte_resuelve_por_vigencia_si_un_pago_legacy_no_tiene_relacion(): void
    {
        DB::table('planilla_pagos')->where('id', 79)->update([
            'empleado_sueldo_id' => null,
        ]);

        $datos = $this->prepararReporte('2026-07-01', '2026-07-31');
        $pago = $datos['pagos']->first();

        $this->assertTrue($pago->sueldo_historico_disponible);
        $this->assertSame(1070.0, $pago->sueldo_base_historico);
        $this->assertSame(2200.0, $pago->sueldo_real_historico_reporte);
        $this->assertSame(1130.0, $pago->sueldo_planilla_historico_reporte);
    }

    public function test_pagos_pendientes_usa_el_total_guardado_y_base_treinta_para_ercli(): void
    {
        DB::table('empleados')->insert([
            'id' => 26,
            'nombre' => 'ERCLI RENAN CUSMA IRIGOIN',
            'dni' => '60155210',
            'fecha_ingreso' => '2026-08-03',
            'fecha_salida' => '2026-08-30',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('empleado_sueldos')->insert([
            'id' => 26,
            'empleado_id' => 26,
            'sueldo_base' => 1800,
            'sueldo_real' => 1800,
            'sueldo_planilla' => 0,
            'vigente_desde' => '2026-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('planilla_pagos')->insert([
            'id' => 119,
            'empleado_id' => 26,
            'empleado_sueldo_id' => 26,
            'mes' => 8,
            'anio' => 2026,
            'sueldo_base' => 1680,
            'total_pagar' => 1680,
            'estado' => 'pendiente',
            'importe_p' => 1680,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('stream')->once()->with('reporte_pagos_pendientes.pdf')->andReturn(response('pdf'));
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('planilla.reportes.pagos_pendientes', Mockery::on(function (array $datos): bool {
                $ercli = $datos['pagos']->firstWhere('id', 119);
                $this->assertSame(1680.0, (float) $ercli->total_pagar);
                $this->assertSame(28, $ercli->desglose_planilla['dias_trabajados']);
                $this->assertSame(1680.0, $ercli->desglose_planilla['sueldo_real_periodo']);

                return true;
            }))
            ->andReturn($pdf);

        $request = Request::create('/reporte', 'GET', [
            'fecha_inicio' => '2026-08-01',
            'fecha_fin' => '2026-08-31',
        ]);

        $this->assertSame(200, app(ReportePlanillaController::class)->pagosPendientesPdf($request)->getStatusCode());
    }

    public function test_buscador_de_adelantos_encuentra_nombre_dni_fecha_numero_y_monto(): void
    {
        $usuario = Mockery::mock(User::class)->makePartial();
        $usuario->id = 1;
        $usuario->shouldReceive('can')->andReturn(false);
        $this->actingAs($usuario);

        foreach (['RODRIGUEZ', '63390350', '18/07/2026', '1780', 'S/ 150'] as $termino) {
            $request = Request::create('/planilla-adelantos', 'GET', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'mes' => '2026-07',
                'search' => ['value' => $termino, 'regex' => false],
                'columns' => [
                    ['data' => 'action', 'name' => 'action', 'searchable' => false, 'orderable' => false, 'search' => ['value' => '', 'regex' => false]],
                    ['data' => 'numero_interno', 'name' => 'numero_interno', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                    ['data' => 'empleado_nombre', 'name' => 'empleados.nombre', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                    ['data' => 'monto', 'name' => 'monto', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                    ['data' => 'fecha', 'name' => 'fecha', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                    ['data' => 'observaciones', 'name' => 'observaciones', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                ],
            ], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

            $response = app(PlanillaAdelantoController::class)->index($request);
            $contenido = $response->getData(true);

            $this->assertSame(1, $contenido['recordsFiltered'], "No se encontró el término {$termino}");
            $this->assertSame('JHONATAN ELIACER RODRIGUEZ VASQUEZ', $contenido['data'][0]['empleado_nombre']);
        }
    }

    private function prepararReporte(string $inicio, string $fin): array
    {
        $controller = app(ReportePlanillaController::class);
        $method = new ReflectionMethod($controller, 'prepararReporteEmpleado');
        $method->setAccessible(true);

        return $method->invoke($controller, 6, $inicio, $fin);
    }

    private function insertarEmpleadoYPago(): void
    {
        DB::table('empleados')->insert([
            'id' => 6,
            'nombre' => 'JHONATAN ELIACER RODRIGUEZ VASQUEZ',
            'dni' => '63390350',
            'fecha_ingreso' => '2026-03-20',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('empleado_sueldos')->insert([
            'id' => 1,
            'empleado_id' => 6,
            'sueldo_base' => 1070,
            'sueldo_planilla' => 1130,
            'sueldo_real' => 2200,
            'vigente_desde' => '2026-03-20',
            'motivo' => 'Carga inicial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('planilla_pagos')->insert([
            'id' => 79,
            'empleado_id' => 6,
            'empleado_sueldo_id' => 1,
            'mes' => 7,
            'anio' => 2026,
            'sueldo_base' => 920,
            'horas_extras' => 0,
            'adelantos' => 150,
            'dias_faltados' => 0,
            'descuento_faltas' => 0,
            'total_pagar' => 920,
            'fecha_pago' => '2026-07-30',
            'estado' => 'pagado',
            'importe_p' => 920,
            'importe_d' => 0,
            'importe_c' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('planilla_adelantos')->insert([
            'id' => 53,
            'empleado_id' => 6,
            'numero_interno' => 1780,
            'monto' => 150,
            'fecha' => '2026-07-18',
            'importe_p' => 150,
            'importe_d' => 0,
            'importe_c' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
