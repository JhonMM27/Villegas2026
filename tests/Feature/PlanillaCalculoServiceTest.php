<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\EmpleadoSueldo;
use App\Services\PlanillaCalculoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlanillaCalculoServiceTest extends TestCase
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
            $table->decimal('sueldo_planilla', 10, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->string('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('registrado_por')->nullable();
            $table->timestamps();
        });
        Schema::create('planilla_adelantos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id');
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function test_calcula_augusto_con_sueldo_planilla_y_una_falta(): void
    {
        [$empleado, $sueldo] = $this->crearEmpleadoYSueldo('2026-03-16', null, 1900, 1130);

        $desglose = app(PlanillaCalculoService::class)->calcular($empleado, $sueldo, 8, 2026, 0, 1);

        $this->assertSame(770.0, $desglose['sueldo_no_planilla']);
        $this->assertSame(63.33, $desglose['descuento_faltas']);
        $this->assertSame(706.67, $desglose['total_pagar']);
    }

    public function test_ingreso_y_salida_se_calculan_una_sola_vez_sobre_base_treinta(): void
    {
        [$empleado, $sueldo] = $this->crearEmpleadoYSueldo('2026-08-03', '2026-08-30', 1800, 0);

        $desglose = app(PlanillaCalculoService::class)->calcular($empleado, $sueldo, 8, 2026);

        $this->assertSame(28, $desglose['dias_trabajados']);
        $this->assertSame(1680.0, $desglose['sueldo_real_periodo']);
        $this->assertSame(1680.0, $desglose['total_pagar']);
    }

    public function test_adelantos_reducen_solo_el_disponible_no_planilla(): void
    {
        [$empleado, $sueldo] = $this->crearEmpleadoYSueldo('2026-03-30', null, 1900, 0);
        DB::table('planilla_adelantos')->insert([
            'empleado_id' => $empleado->id,
            'monto' => 800,
            'fecha' => '2026-08-28',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $desglose = app(PlanillaCalculoService::class)->calcular($empleado, $sueldo, 8, 2026);

        $this->assertSame(800.0, $desglose['adelantos']);
        $this->assertSame(1100.0, $desglose['disponible']);
        $this->assertSame(1100.0, $desglose['total_pagar']);
    }

    private function crearEmpleadoYSueldo(string $ingreso, ?string $salida, float $real, float $planilla): array
    {
        $empleado = Empleado::create([
            'nombre' => 'Empleado Prueba',
            'dni' => fake()->unique()->numerify('########'),
            'fecha_ingreso' => $ingreso,
            'fecha_salida' => $salida,
            'estado' => 'activo',
        ]);
        $sueldo = EmpleadoSueldo::create([
            'empleado_id' => $empleado->id,
            'sueldo_base' => max($real - $planilla, 0),
            'sueldo_real' => $real,
            'sueldo_planilla' => $planilla,
            'vigente_desde' => '2026-01-01',
        ]);

        return [$empleado, $sueldo];
    }
}
