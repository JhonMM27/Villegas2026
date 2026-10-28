<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\GastosDetalladoExport;
use App\Exports\GastosGeneralExport;
use App\Http\Controllers\GastoController;
use App\Models\Gasto;
use App\Models\GastoCategoria;
use App\Models\GastoTipo;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GastoControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('gastos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_nombre')->nullable();
            $table->dateTime('fecha_gasto');
            $table->string('descripcion')->nullable();
            $table->string('responsable')->nullable();
            $table->string('responsable_dni')->nullable();
            $table->unsignedBigInteger('categoria_gasto_id')->nullable();
            $table->unsignedBigInteger('gasto_tipo_id')->nullable();
            $table->string('numero_recibo')->nullable();
            $table->string('numero_interno')->nullable();
            $table->decimal('monto', 10, 2)->default(0);
            $table->decimal('importe_p', 10, 2)->default(0);
            $table->decimal('importe_d', 10, 2)->default(0);
            $table->decimal('importe_c', 10, 2)->default(0);
            $table->timestamps();
        });

        $user = new User(['name' => 'Usuario Prueba']);
        $user->id = 1;
        auth()->setUser($user);
    }

    public function test_store_guarda_un_gasto_distribuido_solo_en_consorcio(): void
    {
        $this->controller()->store($this->request([
            'principal' => 0,
            'deposito' => 0,
            'consorcio' => 15000,
            'total_cobranza' => 15000,
        ]));

        $this->assertDatabaseHas('gastos', [
            'monto' => 15000,
            'importe_p' => 0,
            'importe_d' => 0,
            'importe_c' => 15000,
        ]);
    }

    public function test_store_calcula_el_total_en_el_servidor_desde_las_tres_cajas(): void
    {
        $this->controller()->store($this->request([
            'principal' => 100,
            'deposito' => 200,
            'consorcio' => 300,
            'total_cobranza' => 99999,
        ]));

        $this->assertDatabaseHas('gastos', [
            'monto' => 600,
            'importe_p' => 100,
            'importe_d' => 200,
            'importe_c' => 300,
        ]);
    }

    public function test_update_recalcula_total_y_distribucion(): void
    {
        $gasto = Gasto::create([
            'fecha_gasto' => '2026-09-30 10:00:00',
            'monto' => 50,
            'importe_p' => 50,
        ]);

        $this->controller()->update($this->request([
            'principal' => 10,
            'deposito' => 20,
            'consorcio' => 30,
            'total_cobranza' => 1,
        ]), $gasto->id);

        $this->assertDatabaseHas('gastos', [
            'id' => $gasto->id,
            'monto' => 60,
            'importe_p' => 10,
            'importe_d' => 20,
            'importe_c' => 30,
        ]);
    }

    public function test_no_permite_guardar_un_gasto_sin_distribucion(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->store($this->request([
            'principal' => 0,
            'deposito' => 0,
            'consorcio' => 0,
            'total_cobranza' => 15000,
        ]));
    }

    public function test_reportes_de_gastos_usan_monto_total_y_conservan_distribucion_de_caja(): void
    {
        $gasto = new Gasto([
            'numero_recibo' => '651',
            'numero_interno' => '17782',
            'fecha_gasto' => '2026-10-01 00:00:00',
            'descripcion' => 'Pago Empleados mes Octubre/2026',
            'responsable' => 'Sistema',
            'monto' => 45900,
            'importe_p' => 25950,
            'importe_d' => 0,
            'importe_c' => 0,
        ]);
        $gasto->setRelation('categoriaGasto', new GastoCategoria(['nombre' => 'Gastos_Personal']));
        $gasto->setRelation('gastoTipo', new GastoTipo(['nombre' => 'Sueldos']));
        $reportes = collect([$gasto]);

        $general = view('reportes.gastos.general', compact('reportes'))->render();
        $detallado = view('reportes.gastos.detallado', compact('reportes'))->render();
        $datosPdf = [
            'reportes' => $reportes,
            'fechaInicio' => '2026-10-01',
            'fechaFin' => '2026-10-01',
            'categoriaId' => null,
            'tipoId' => null,
        ];
        $generalPdf = view('reportes.gastos.general_pdf', $datosPdf)->render();
        $detalladoPdf = view('reportes.gastos.detallado_pdf', $datosPdf)->render();
        $generalExcel = (new GastosGeneralExport($reportes))->collection();
        $detalladoExcel = (new GastosDetalladoExport($reportes))->collection();

        $this->assertStringContainsString('45,900.00', $general);
        $this->assertStringNotContainsString('25,950.00', $general);
        $this->assertStringContainsString('Monto Total', $detallado);
        $this->assertStringContainsString('45,900.00', $detallado);
        $this->assertStringContainsString('25,950.00', $detallado);
        $this->assertStringContainsString('45,900.00', $generalPdf);
        $this->assertStringNotContainsString('25,950.00', $generalPdf);
        $this->assertStringContainsString('45,900.00', $detalladoPdf);
        $this->assertStringContainsString('25,950.00', $detalladoPdf);
        $this->assertSame(45900.0, (float) $generalExcel->last()['total']);
        $this->assertSame(45900.0, (float) $detalladoExcel->last()['monto']);
        $this->assertSame(25950.0, (float) $detalladoExcel->last()['principal']);
    }

    private function controller(): GastoController
    {
        return app(GastoController::class);
    }

    private function request(array $distribucion): Request
    {
        return Request::create('/gastos', 'POST', array_merge([
            'fecha_gasto' => '2026-09-30T20:22',
            'descripcion' => 'Gasto de prueba',
            'responsable' => 'Responsable',
        ], $distribucion));
    }
}
