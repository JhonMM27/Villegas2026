<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\EmpleadoSueldo;
use App\Services\EmpleadoService;
use App\Services\EmpleadoSueldoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class EmpleadoSueldoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('empleados', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('dni')->unique();
            $table->string('estado')->default('activo');
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->timestamps();
        });

        Schema::create('empleado_sueldos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id');
            $table->decimal('sueldo_base', 10, 2);
            $table->decimal('sueldo_real', 10, 2);
            $table->decimal('sueldo_planilla', 10, 2);
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
            $table->foreignId('empleado_sueldo_id')->nullable();
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->decimal('sueldo_base', 10, 2)->default(0);
            $table->decimal('horas_extras', 10, 2)->default(0);
            $table->decimal('adelantos', 10, 2)->default(0);
            $table->decimal('dias_faltados', 10, 2)->default(0);
            $table->decimal('descuento_faltas', 10, 2)->default(0);
            $table->decimal('cts_planilla', 10, 2)->nullable();
            $table->decimal('cts_sueldo_real', 10, 2)->nullable();
            $table->decimal('total_pagar', 10, 2)->default(0);
            $table->string('estado')->default('pendiente');
            $table->date('fecha_pago')->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('importe_p', 10, 2)->default(0);
            $table->decimal('importe_d', 10, 2)->default(0);
            $table->decimal('importe_c', 10, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('planilla_adelantos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id');
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->timestamps();
        });
        Schema::create('planilla_asistencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id');
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->decimal('dias_faltados', 10, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('auditoria_eventos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_evento');
            $table->string('modulo');
            $table->unsignedBigInteger('registro_id');
            $table->unsignedTinyInteger('numero_rectificacion')->nullable();
            $table->string('registro_referencia')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_nombre');
            $table->text('motivo')->nullable();
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');
            $table->json('cambios');
            $table->timestamps();
        });
    }

    public function test_registra_cambio_y_conserva_la_vigencia_anterior(): void
    {
        $empleado = Empleado::create([
            'nombre' => 'Empleado Prueba',
            'dni' => '12345678',
            'estado' => 'activo',
        ]);
        $service = app(EmpleadoSueldoService::class);

        $service->crearInicial($empleado, [
            'sueldo_base' => 1070,
            'sueldo_real' => 2200,
            'sueldo_planilla' => 1130,
            'vigente_desde' => '2026-01-01',
            'motivo' => 'Ingreso',
        ]);
        $service->registrarCambio($empleado, [
            'sueldo_base' => 1270,
            'sueldo_real' => 2400,
            'sueldo_planilla' => 1130,
            'vigente_desde' => '2026-08-01',
            'motivo' => 'Aumento',
        ]);

        $anterior = $empleado->sueldos()->where('sueldo_real', 2200)->firstOrFail();
        $this->assertSame('2026-07-31', $anterior->vigente_hasta?->format('Y-m-d'));
        $vigente = $empleado->sueldos()->where('sueldo_real', 2400)->firstOrFail();
        $this->assertSame('2026-08-01', $vigente->vigente_desde->format('Y-m-d'));
        $this->assertNull($vigente->vigente_hasta);
        $this->assertSame(2200.0, (float) $service->vigenteEn($empleado, '2026-07-15')?->sueldo_real);
        $this->assertSame(2400.0, (float) $service->vigenteEn($empleado, '2026-08-15')?->sueldo_real);
    }

    public function test_crear_empleado_guarda_los_importes_solo_en_el_historial(): void
    {
        $empleado = app(EmpleadoService::class)->create([
            'nombre' => 'Nuevo Empleado',
            'dni' => '11223344',
            'estado' => 'activo',
            'sueldo_base' => 1070,
            'sueldo_real' => 2200,
            'sueldo_planilla' => 1130,
            'vigente_desde' => '2026-08-01',
            'motivo' => 'Ingreso',
        ]);

        $this->assertDatabaseHas('empleados', [
            'id' => $empleado->id,
            'nombre' => 'Nuevo Empleado',
        ]);
        $this->assertDatabaseHas('empleado_sueldos', [
            'empleado_id' => $empleado->id,
            'sueldo_base' => 1070,
            'sueldo_real' => 2200,
            'sueldo_planilla' => 1130,
        ]);
        $this->assertSame(2200.0, (float) $empleado->sueldo_real);
    }

    public function test_no_crea_historial_si_los_tres_importes_no_cambian(): void
    {
        $empleado = Empleado::create([
            'nombre' => 'Empleado Prueba',
            'dni' => '87654321',
            'estado' => 'activo',
        ]);
        $service = app(EmpleadoSueldoService::class);
        $datos = [
            'sueldo_base' => 870,
            'sueldo_real' => 2000,
            'sueldo_planilla' => 1130,
            'vigente_desde' => '2026-01-01',
        ];

        $service->crearInicial($empleado, $datos);
        $service->registrarCambio($empleado, $datos + ['vigente_desde' => '2026-08-01']);

        $this->assertSame(1, $empleado->sueldos()->count());
    }

    public function test_sueldo_no_planilla_siempre_se_deriva_y_la_vigencia_inicia_el_primer_dia(): void
    {
        $empleado = Empleado::create([
            'nombre' => 'Empleado Derivado',
            'dni' => '44332211',
            'estado' => 'activo',
        ]);

        $sueldo = app(EmpleadoSueldoService::class)->crearInicial($empleado, [
            'sueldo_base' => 9999,
            'sueldo_real' => 1900,
            'sueldo_planilla' => 1130,
            'vigente_desde' => '2026-08-31',
        ]);

        $this->assertSame(770.0, (float) $sueldo->sueldo_base);
        $this->assertSame('2026-08-01', $sueldo->vigente_desde->format('Y-m-d'));
    }

    public function test_rechaza_sueldo_planilla_mayor_al_real(): void
    {
        $empleado = Empleado::create([
            'nombre' => 'Empleado Inválido',
            'dni' => '55667788',
            'estado' => 'activo',
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(EmpleadoSueldoService::class)->crearInicial($empleado, [
            'sueldo_real' => 1000,
            'sueldo_planilla' => 1200,
            'vigente_desde' => '2026-08-01',
        ]);
    }

    public function test_rectificacion_recalcula_solo_el_pago_pendiente_y_registra_auditoria(): void
    {
        $empleado = Empleado::create([
            'nombre' => 'Augusto Prueba',
            'dni' => '10101010',
            'fecha_ingreso' => '2026-03-16',
            'estado' => 'activo',
        ]);
        $anterior = EmpleadoSueldo::create([
            'empleado_id' => $empleado->id,
            'sueldo_base' => 1900,
            'sueldo_real' => 1900,
            'sueldo_planilla' => 0,
            'vigente_desde' => '2026-03-16',
            'vigente_hasta' => '2026-08-30',
        ]);
        $erroneo = EmpleadoSueldo::create([
            'empleado_id' => $empleado->id,
            'sueldo_base' => 1900,
            'sueldo_real' => 1900,
            'sueldo_planilla' => 1130,
            'vigente_desde' => '2026-08-31',
        ]);
        DB::table('planilla_asistencias')->insert([
            'empleado_id' => $empleado->id, 'mes' => 8, 'anio' => 2026, 'dias_faltados' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('planilla_pagos')->insert([
            'empleado_id' => $empleado->id, 'empleado_sueldo_id' => $erroneo->id,
            'mes' => 8, 'anio' => 2026, 'sueldo_base' => 1900, 'dias_faltados' => 1,
            'descuento_faltas' => 63.33, 'total_pagar' => 1836.67, 'estado' => 'pendiente',
            'importe_p' => 1836.67, 'created_at' => now(), 'updated_at' => now(),
        ]);

        app(EmpleadoSueldoService::class)->rectificarMes($empleado, 8, 2026, [
            'sueldo_real' => 1900,
            'sueldo_planilla' => 1130,
            'motivo' => 'Corrección de prueba',
        ]);

        $this->assertSame(770.0, (float) $erroneo->refresh()->sueldo_base);
        $this->assertSame('2026-08-01', $erroneo->vigente_desde->format('Y-m-d'));
        $this->assertSame('2026-07-31', $anterior->refresh()->vigente_hasta?->format('Y-m-d'));
        $this->assertDatabaseHas('planilla_pagos', [
            'empleado_sueldo_id' => $erroneo->id, 'sueldo_base' => 770,
            'descuento_faltas' => 63.33, 'total_pagar' => 706.67, 'importe_p' => 706.67,
        ]);
        $this->assertDatabaseHas('auditoria_eventos', [
            'modulo' => 'empleado_sueldos', 'registro_id' => $erroneo->id,
            'tipo_evento' => 'rectificacion', 'motivo' => 'Corrección de prueba',
        ]);
    }

    public function test_rectificacion_bloquea_un_periodo_pagado(): void
    {
        $empleado = Empleado::create([
            'nombre' => 'Empleado Pagado', 'dni' => '20202020', 'estado' => 'activo',
        ]);
        $sueldo = EmpleadoSueldo::create([
            'empleado_id' => $empleado->id, 'sueldo_base' => 1900,
            'sueldo_real' => 1900, 'sueldo_planilla' => 0, 'vigente_desde' => '2026-08-01',
        ]);
        DB::table('planilla_pagos')->insert([
            'empleado_id' => $empleado->id, 'empleado_sueldo_id' => $sueldo->id,
            'mes' => 8, 'anio' => 2026, 'sueldo_base' => 1900,
            'total_pagar' => 1900, 'estado' => 'pagado', 'importe_p' => 1900,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(EmpleadoSueldoService::class)->rectificarMes($empleado, 8, 2026, [
            'sueldo_real' => 1800, 'sueldo_planilla' => 0, 'motivo' => 'No permitido',
        ]);
    }
}
