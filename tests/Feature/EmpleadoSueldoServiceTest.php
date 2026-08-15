<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Empleado;
use App\Services\EmpleadoService;
use App\Services\EmpleadoSueldoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
}
