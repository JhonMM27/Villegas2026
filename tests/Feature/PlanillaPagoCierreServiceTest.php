<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\EmpleadoSueldo;
use App\Models\PlanillaPago;
use App\Services\PlanillaPagoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlanillaPagoCierreServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('empleados', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('dni');
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->string('estado')->default('activo');
            $table->timestamps();
        });
        Schema::create('empleado_sueldos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id');
            $table->decimal('sueldo_base', 10, 2);
            $table->decimal('sueldo_real', 10, 2);
            $table->decimal('sueldo_planilla', 10, 2)->default(0);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->string('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('registrado_por')->nullable();
            $table->timestamps();
        });
        Schema::create('planilla_pagos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id');
            $table->foreignId('empleado_sueldo_id');
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->decimal('sueldo_base', 10, 2)->default(0);
            $table->decimal('horas_extras', 10, 2)->default(0);
            $table->decimal('adelantos', 10, 2)->default(0);
            $table->decimal('dias_faltados', 5, 2)->default(0);
            $table->decimal('descuento_faltas', 10, 2)->default(0);
            $table->decimal('cts_planilla', 10, 2)->nullable();
            $table->decimal('cts_sueldo_real', 10, 2)->nullable();
            $table->decimal('total_pagar', 10, 2);
            $table->date('fecha_pago')->nullable();
            $table->date('fecha_pago_original')->nullable();
            $table->string('estado')->default('pendiente');
            $table->timestamp('anulado_at')->nullable();
            $table->unsignedSmallInteger('anulado_por')->nullable();
            $table->string('motivo_anulacion', 500)->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('importe_p', 10, 2)->default(0);
            $table->decimal('importe_d', 10, 2)->default(0);
            $table->decimal('importe_c', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['empleado_id', 'mes', 'anio']);
        });
        Schema::create('planilla_pago_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('planilla_pago_id');
            $table->string('accion');
            $table->string('estado_anterior');
            $table->string('estado_nuevo');
            $table->date('fecha_pago_anterior')->nullable();
            $table->date('fecha_pago_nueva')->nullable();
            $table->string('motivo')->nullable();
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->string('user_nombre')->nullable();
            $table->timestamps();
        });
        Schema::create('planilla_asistencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id');
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->decimal('dias_faltados', 5, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('planilla_inasistencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id');
            $table->date('fecha');
            $table->boolean('medio_dia')->default(false);
            $table->timestamps();
        });
        Schema::create('planilla_adelantos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id');
            $table->decimal('monto', 10, 2)->default(0);
            $table->date('fecha');
            $table->timestamps();
        });
        Schema::create('categoria_gastos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('gasto_tipos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('categoria_gasto_id');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('gastos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_nombre')->nullable();
            $table->dateTime('fecha_gasto');
            $table->string('descripcion')->nullable();
            $table->string('responsable')->nullable();
            $table->string('responsable_dni')->nullable();
            $table->unsignedBigInteger('empleado_id')->nullable();
            $table->unsignedBigInteger('categoria_gasto_id')->nullable();
            $table->unsignedBigInteger('gasto_tipo_id')->nullable();
            $table->string('numero_recibo')->nullable();
            $table->string('numero_interno')->nullable();
            $table->decimal('monto', 10, 2)->default(0);
            $table->decimal('importe_p', 10, 2)->default(0);
            $table->decimal('importe_d', 10, 2)->default(0);
            $table->decimal('importe_c', 10, 2)->default(0);
            $table->unsignedTinyInteger('planilla_mes')->nullable();
            $table->unsignedSmallInteger('planilla_anio')->nullable();
            $table->timestamps();
        });
    }

    public function test_revertir_y_reconfirmar_conserva_la_fecha_original(): void
    {
        [$empleado, $sueldo] = $this->crearEmpleado('Víctor', '71837739', 'activo', '2026-08-18');
        $pago = $this->crearPago($empleado, $sueldo, 'pagado', '2026-08-18');
        $service = app(PlanillaPagoService::class);

        $service->revertirPago($pago);
        $pago->refresh();
        $this->assertSame('pendiente', $pago->estado);
        $this->assertNull($pago->fecha_pago);
        $this->assertSame('2026-08-18', $pago->fecha_pago_original?->toDateString());

        $service->marcarPagado($pago);
        $pago->refresh();
        $this->assertSame('2026-08-18', $pago->fecha_pago?->toDateString());
        $this->assertSame(['reversion', 'confirmacion'], $pago->movimientos()->orderBy('id')->pluck('accion')->all());
    }

    public function test_generacion_completa_faltantes_y_excluye_salida_del_mes_anterior(): void
    {
        [$activo, $sueldoActivo] = $this->crearEmpleado('Activo', '11111111', 'activo');
        [$faltante] = $this->crearEmpleado('Faltante', '22222222', 'activo');
        [$rene] = $this->crearEmpleado('René', '48670033', 'inactivo', '2026-07-30');
        $this->crearPago($activo, $sueldoActivo, 'pendiente');

        $resultado = app(PlanillaPagoService::class)->generarPagosDelMes(8, 2026);

        $this->assertSame(2, $resultado['elegibles']);
        $this->assertSame(1, $resultado['existentes']);
        $this->assertSame(1, $resultado['creados']);
        $this->assertDatabaseHas('planilla_pagos', ['empleado_id' => $faltante->id, 'mes' => 8, 'anio' => 2026]);
        $this->assertDatabaseMissing('planilla_pagos', ['empleado_id' => $rene->id, 'mes' => 8, 'anio' => 2026]);
    }

    public function test_gasto_planilla_usa_total_pagado_y_distribucion_de_caja(): void
    {
        [$empleado, $sueldo] = $this->crearEmpleado('Parcial', '33333333', 'activo');
        $pago = $this->crearPago($empleado, $sueldo, 'pagado', '2026-08-08');
        $pago->update([
            'total_pagar' => 400,
            'importe_p' => 250,
            'importe_d' => 100,
            'importe_c' => 50,
        ]);

        $service = app(PlanillaPagoService::class);
        $service->sincronizarGastoPlanilla(8, 2026);
        $service->sincronizarGastoPlanilla(8, 2026);

        $this->assertDatabaseCount('gastos', 1);
        $this->assertDatabaseHas('gastos', [
            'planilla_mes' => 8,
            'planilla_anio' => 2026,
            'monto' => 400,
            'importe_p' => 250,
            'importe_d' => 100,
            'importe_c' => 50,
        ]);
    }

    private function crearEmpleado(string $nombre, string $dni, string $estado, ?string $salida = null): array
    {
        $empleado = Empleado::create([
            'nombre' => $nombre,
            'dni' => $dni,
            'fecha_ingreso' => '2026-01-01',
            'fecha_salida' => $salida,
            'estado' => $estado,
        ]);
        $sueldo = EmpleadoSueldo::create([
            'empleado_id' => $empleado->id,
            'sueldo_base' => 2000,
            'sueldo_real' => 2000,
            'sueldo_planilla' => 0,
            'vigente_desde' => '2026-01-01',
        ]);

        return [$empleado, $sueldo];
    }

    private function crearPago(Empleado $empleado, EmpleadoSueldo $sueldo, string $estado, ?string $fecha = null): PlanillaPago
    {
        return PlanillaPago::create([
            'empleado_id' => $empleado->id,
            'empleado_sueldo_id' => $sueldo->id,
            'mes' => 8,
            'anio' => 2026,
            'sueldo_base' => 2000,
            'total_pagar' => 2000,
            'estado' => $estado,
            'fecha_pago' => $fecha,
            'importe_p' => 2000,
        ]);
    }
}
