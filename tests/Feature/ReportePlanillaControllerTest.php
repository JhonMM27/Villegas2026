<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\ReportePlanillaController;
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
            $table->decimal('sueldo_planilla', 10, 2);
            $table->decimal('sueldo_real', 10, 2);
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->string('estado')->default('activo');
            $table->timestamps();
        });

        Schema::create('planilla_pagos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empleado_id');
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
            'sueldo_planilla' => 1130,
            'sueldo_real' => 2200,
            'fecha_ingreso' => '2026-03-20',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('planilla_pagos')->insert([
            'id' => 79,
            'empleado_id' => 6,
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
