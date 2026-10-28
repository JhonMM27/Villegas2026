<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CompraProvisionalService;
use App\Services\VentaProvisionalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SaldoHistoricoProvisionalTest extends TestCase
{
    private ?string $manifiesto = null;

    protected function tearDown(): void
    {
        if ($this->manifiesto !== null && is_file($this->manifiesto)) {
            unlink($this->manifiesto);
        }
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction('regexp', fn ($pattern, $value) => preg_match('/'.$pattern.'/', (string) $value));

        foreach (['venta' => 'cliente', 'compra' => 'proveedor'] as $prefix => $owner) {
            $owners = $owner === 'cliente' ? 'clientes' : 'proveedores';
            Schema::create($owners, function (Blueprint $t): void {
                $t->id();
                $t->string('razon_social');
                $t->timestamps();
            });
            DB::table($owners)->insert(['id' => 1, 'razon_social' => 'Titular prueba']);
            Schema::create($prefix.'s', function (Blueprint $t) use ($owner, $prefix): void {
                $t->id();
                $t->unsignedInteger($owner.'_id');
                $t->string('comprobante_tipo_codigo')->default('NP');
                $t->string('serie')->default('1');
                $t->integer('correlativo')->default(1);
                $t->dateTime('fecha_'.$prefix)->default('2026-01-01 00:00:00');
                $t->string('estado')->default('registrada');
                foreach (['total', 'acuenta', 'abonos', 'saldo'] as $field) {
                    $t->decimal($field, 12, 2)->default(0);
                }
                $t->timestamps();
            });
            Schema::create($prefix.'_provisionales', function (Blueprint $t) use ($owner): void {
                $t->id();
                $t->unsignedInteger('user_id')->nullable();
                $t->string('user_nombre')->nullable();
                $t->unsignedInteger($owner.'_id');
                $t->string($owner.'_nombre')->default('Titular prueba');
                $t->dateTime('fecha_provisional')->default('2026-09-29 00:00:00');
                $t->string('numero_recibo')->default('1');
                $t->string('numero_interno')->nullable();
                $t->string('tipo')->default('APLICADO');
                foreach (['monto', 'libre', 'importe_p', 'importe_d', 'importe_c'] as $field) {
                    $t->decimal($field, 12, 2)->default(0);
                }
                $t->timestamps();
            });
            Schema::create($prefix.'_provisional_detalles', function (Blueprint $t) use ($prefix): void {
                $t->id();
                $t->unsignedInteger($prefix.'_id')->nullable();
                $t->unsignedInteger($prefix.'_provisional_id');
                $t->decimal('monto', 12, 2);
                $t->string('comprobante_tipo_codigo')->nullable();
                $t->string('serie')->nullable();
                $t->integer('correlativo')->nullable();
                $t->string('comentario')->nullable();
                $t->timestamps();
            });
        }
        (require database_path('migrations/2026_10_04_000001_add_historico_to_saldos.php'))->up();
        (require database_path('migrations/2026_10_05_000001_protect_provisional_receipts.php'))->up();
        Schema::create('auditoria_eventos', function (Blueprint $t): void {
            $t->id();
            $t->string('tipo_evento');
            $t->string('modulo');
            $t->integer('registro_id');
            $t->integer('numero_rectificacion')->nullable();
            $t->string('registro_referencia');
            $t->integer('user_id')->nullable();
            $t->string('user_nombre');
            $t->string('motivo')->nullable();
            $t->json('datos_anteriores');
            $t->json('datos_nuevos');
            $t->json('cambios');
            $t->timestamps();
        });
    }

    public static function dominios(): array
    {
        return [['venta', 'cliente', VentaProvisionalService::class], ['compra', 'proveedor', CompraProvisionalService::class]];
    }

    #[DataProvider('dominios')]
    public function test_crear_editar_reasignar_y_eliminar_conserva_historico(string $prefix, string $owner, string $service): void
    {
        $this->documento($prefix, $owner, 1);
        $this->documento($prefix, $owner, 2);
        $recibo = app($service)->createProvisional($this->datos($prefix, $owner, 1, 100));
        $this->assertDatabaseHas($prefix.'s', ['id' => 1, 'abonos' => 6880, 'saldo' => 520, 'abonos_migrados' => 6780]);

        app($service)->updateProvisional($recibo->id, $this->datos($prefix, $owner, 1, 200));
        $this->assertDatabaseHas($prefix.'s', ['id' => 1, 'abonos' => 6980, 'saldo' => 420]);

        app($service)->updateProvisional($recibo->id, $this->datos($prefix, $owner, 2, 200));
        $this->assertDatabaseHas($prefix.'s', ['id' => 1, 'abonos' => 6780, 'saldo' => 620]);
        $this->assertDatabaseHas($prefix.'s', ['id' => 2, 'abonos' => 6980, 'saldo' => 420]);

        app($service)->deleteProvisional($recibo->id);
        $this->assertDatabaseHas($prefix.'s', ['id' => 2, 'abonos' => 6780, 'saldo' => 620, 'abonos_migrados' => 6780]);
        $this->assertDatabaseCount($prefix.'_provisionales', 0);
        $this->assertDatabaseCount($prefix.'_provisional_detalles', 0);
    }

    #[DataProvider('dominios')]
    public function test_historico_no_conciliado_bloquea_sin_escribir(string $prefix, string $owner, string $service): void
    {
        $this->documento($prefix, $owner, 1);
        DB::table($prefix.'s')->update(['abonos_migrados' => null]);
        try {
            app($service)->createProvisional($this->datos($prefix, $owner, 1, 100));
            $this->fail('Debe impedir que se pierdan abonos sin evidencia.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('requieren conciliacion', $e->getMessage());
        }
        $this->assertDatabaseHas($prefix.'s', ['id' => 1, 'abonos' => 6780, 'saldo' => 620, 'abonos_migrados' => null]);
        $this->assertDatabaseCount($prefix.'_provisionales', 0);
    }

    #[DataProvider('dominios')]
    public function test_sobrepago_revierte_y_recibos_duplicados_previos_se_suman(string $prefix, string $owner, string $service): void
    {
        $this->documento($prefix, $owner, 1);
        $recibo = app($service)->createProvisional($this->datos($prefix, $owner, 1, 620));
        DB::table($prefix.'_provisional_detalles')->where($prefix.'_provisional_id', $recibo->id)->update(['monto' => 310]);
        DB::table($prefix.'_provisional_detalles')->insert([$prefix.'_provisional_id' => $recibo->id, $prefix.'_id' => 1, 'monto' => 310]);
        app($service)->updateProvisional($recibo->id, $this->datos($prefix, $owner, 1, 620));
        $this->assertDatabaseCount($prefix.'_provisional_detalles', 1);
        try {
            app($service)->updateProvisional($recibo->id, $this->datos($prefix, $owner, 1, 620.01));
            $this->fail('Debe rechazar sobrepago.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('supera su saldo', $e->getMessage());
        }
        $this->assertDatabaseHas($prefix.'s', ['id' => 1, 'abonos' => 7400, 'saldo' => 0]);
        $this->assertDatabaseHas($prefix.'_provisionales', ['id' => $recibo->id, 'monto' => 620]);
    }

    #[DataProvider('dominios')]
    public function test_eliminar_o_reasignar_no_oculta_inconsistencia_anterior(string $prefix, string $owner, string $service): void
    {
        $this->documento($prefix, $owner, 1);
        $this->documento($prefix, $owner, 2);
        $recibo = app($service)->createProvisional($this->datos($prefix, $owner, 1, 100));
        DB::table($prefix.'s')->where('id', 1)->update(['abonos' => 6881]);
        foreach (['delete', 'update'] as $operation) {
            try {
                $operation === 'delete'
                    ? app($service)->deleteProvisional($recibo->id)
                    : app($service)->updateProvisional($recibo->id, $this->datos($prefix, $owner, 2, 100));
                $this->fail('Debe bloquear toda la operacion.');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('requieren conciliacion', $e->getMessage());
            }
        }
        $this->assertDatabaseHas($prefix.'_provisional_detalles', [$prefix.'_id' => 1, 'monto' => 100]);
        $this->assertDatabaseHas($prefix.'s', ['id' => 2, 'saldo' => 620]);
    }

    #[DataProvider('dominios')]
    public function test_documento_nuevo_sin_historico_admite_cobranza(string $prefix, string $owner, string $service): void
    {
        $this->documento($prefix, $owner, 1);
        DB::table($prefix.'s')->update(['abonos_migrados' => null, 'abonos' => 0, 'saldo' => 7400]);
        app($service)->createProvisional($this->datos($prefix, $owner, 1, 7400));
        $this->assertDatabaseHas($prefix.'s', ['id' => 1, 'saldo' => 0, 'abonos' => 7400]);
    }

    public function test_conciliacion_simula_aplica_y_es_idempotente_incluso_despues_de_un_pago(): void
    {
        $archivo = $this->prepararConciliacion();
        $this->artisan('saldos:conciliar-historico', ['archivo' => $archivo])->assertExitCode(0);
        $this->assertDatabaseHas('ventas', ['id' => 1, 'abonos_migrados' => null]);
        $this->assertDatabaseCount('saldo_conciliaciones', 0);
        $this->artisan('saldos:conciliar-historico', ['archivo' => $archivo, '--apply' => true])->assertExitCode(0);
        $this->assertDatabaseHas('ventas', ['id' => 1, 'abonos_migrados' => 6780, 'saldo' => 620]);
        $this->assertDatabaseCount('saldo_conciliaciones', 2);
        app(VentaProvisionalService::class)->createProvisional($this->datos('venta', 'cliente', 1, 100));
        $this->artisan('saldos:conciliar-historico', ['archivo' => $archivo, '--apply' => true])->assertExitCode(0);
        $this->assertDatabaseHas('ventas', ['id' => 1, 'abonos' => 6880, 'saldo' => 520]);
        $this->assertDatabaseCount('saldo_conciliaciones', 2);
        $this->assertDatabaseCount('venta_provisionales', 1);
    }

    #[DataProvider('dominios')]
    public function test_reintento_no_duplica_y_clave_conflictiva_o_eliminada_es_rechazada(string $prefix, string $owner, string $service): void
    {
        $this->documento($prefix, $owner, 1);
        $data = $this->datos($prefix, $owner, 1, 100);
        $data['idempotencia_clave'] = (string) \Illuminate\Support\Str::uuid();
        $recibo = app($service)->createProvisional($data);
        $reintento = $data;
        $reintento['principal'] = '100.00';
        $this->assertSame($recibo->id, app($service)->createProvisional($reintento)->id);
        $this->assertDatabaseCount($prefix.'_provisionales', 1);
        $this->assertDatabaseCount('recibo_solicitudes', 1);
        $this->assertDatabaseCount('auditoria_eventos', 1);
        $conflicto = $this->datos($prefix, $owner, 1, 101);
        $conflicto['idempotencia_clave'] = $data['idempotencia_clave'];
        try {
            app($service)->createProvisional($conflicto);
            $this->fail('No se puede reutilizar la clave con otro pago.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('otros datos', $e->getMessage());
        }
        app($service)->deleteProvisional($recibo->id);
        try {
            app($service)->createProvisional($data);
            $this->fail('El reintento no puede recrear el recibo borrado.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('fue eliminado', $e->getMessage());
        }
        $nuevo = app($service)->createProvisional($this->datos($prefix, $owner, 1, 100));
        $this->assertSame('2', (string) $nuevo->numero_recibo);
        $this->assertDatabaseHas($prefix.'s', ['id' => 1, 'saldo' => 520]);
        $evento = \App\Models\AuditoriaEvento::where('tipo_evento', 'eliminacion')->firstOrFail();
        $this->assertSame(100.0, (float) $evento->datos_anteriores['cabecera']['monto']['valor']);
        $this->assertCount(1, $evento->datos_anteriores['detalles']);
        $this->assertSame([], $evento->datos_nuevos['detalles']);
    }

    #[DataProvider('dominios')]
    public function test_edicion_obsoleta_es_bloqueada_y_auditoria_muestra_importes(string $prefix, string $owner, string $service): void
    {
        $this->documento($prefix, $owner, 1);
        $r = app($service)->createProvisional($this->datos($prefix, $owner, 1, 100));
        $version = app(\App\Services\ReciboControlService::class)->version($r);
        app($service)->updateProvisional($r->id, $this->datos($prefix, $owner, 1, 200) + ['version_recibo' => $version]);
        try {
            app($service)->updateProvisional($r->id, $this->datos($prefix, $owner, 1, 300) + ['version_recibo' => $version]);
            $this->fail('Debe proteger la edicion del otro usuario.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Otro usuario', $e->getMessage());
        }
        $this->assertDatabaseHas($prefix.'_provisionales', ['id' => $r->id, 'monto' => 200]);
        $this->assertDatabaseCount('auditoria_eventos', 2);
        $evento = \App\Models\AuditoriaEvento::where('tipo_evento', 'modificacion')->firstOrFail();
        $this->assertSame(100.0, (float) $evento->datos_anteriores['cabecera']['monto']['valor']);
        $this->assertSame(200.0, (float) $evento->datos_nuevos['cabecera']['monto']['valor']);
        $this->assertSame('Documento 1', $evento->datos_nuevos['detalles'][0]['producto']);
    }

    #[DataProvider('dominios')]
    public function test_fallo_de_auditoria_revierte_recibo_saldo_contador_y_clave(string $prefix, string $owner, string $service): void
    {
        $this->documento($prefix, $owner, 1);
        $auditoria = \Mockery::mock(\App\Services\AuditoriaService::class, [app(\App\Services\RectificacionAuditoriaService::class)])->makePartial();
        $auditoria->shouldReceive('registrarOperacionRecibo')->once()->andThrow(new \RuntimeException('Fallo de auditoria simulado'));
        $this->app->instance(\App\Services\AuditoriaService::class, $auditoria);
        try {
            app($service)->createProvisional($this->datos($prefix, $owner, 1, 100) + ['idempotencia_clave' => (string) \Illuminate\Support\Str::uuid()]);
            $this->fail('Debe revertir la transaccion completa.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo de auditoria simulado', $e->getMessage());
        }
        $this->assertDatabaseCount($prefix.'_provisionales', 0);
        $this->assertDatabaseCount('recibo_solicitudes', 0);
        $this->assertDatabaseCount('auditoria_eventos', 0);
        $this->assertDatabaseHas('recibo_contadores', ['modulo' => $prefix.'_provisionales', 'ultimo_numero' => 0]);
        $this->assertDatabaseHas($prefix.'s', ['id' => 1, 'saldo' => 620, 'abonos' => 6780]);
    }

    public function test_conciliacion_fallida_revierte_todo_el_lote(): void
    {
        $archivo = $this->prepararConciliacion();
        DB::table('ventas')->where('id', 2)->update(['saldo' => 619]);
        $this->artisan('saldos:conciliar-historico', ['archivo' => $archivo, '--apply' => true])->assertExitCode(1);
        $this->assertDatabaseHas('ventas', ['id' => 1, 'abonos_migrados' => null, 'saldo' => 620]);
        $this->assertDatabaseCount('saldo_conciliaciones', 0);
    }

    public function test_auditoria_detecta_historico_y_recibo_inconsistente_sin_reparar(): void
    {
        $this->documento('venta', 'cliente', 1);
        $this->artisan('saldos:auditar', ['--tipo' => 'ventas', '--titular' => 1])->assertExitCode(0);
        $r = app(VentaProvisionalService::class)->createProvisional($this->datos('venta', 'cliente', 1, 100));
        DB::table('venta_provisionales')->where('id', $r->id)->update(['libre' => -1]);
        DB::table('ventas')->update(['abonos_migrados' => null]);
        $code = \Illuminate\Support\Facades\Artisan::call('saldos:auditar', ['--tipo' => 'ventas', '--json' => true]);
        $this->assertSame(1, $code);
        $informe = json_decode(\Illuminate\Support\Facades\Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $informe['resumen']['historico_sin_conciliar']);
        $this->assertSame(1, $informe['resumen']['recibo_sobreaplicado_o_negativo']);
        $this->assertDatabaseHas('ventas', ['id' => 1, 'abonos_migrados' => null, 'abonos' => 6880]);
        $this->assertDatabaseHas('venta_provisionales', ['id' => $r->id, 'libre' => -1]);
    }

    public function test_conciliacion_rechaza_evidencia_y_destino_incompatibles(): void
    {
        $archivo = $this->prepararConciliacion();
        $this->artisan('saldos:conciliar-historico', ['archivo' => $archivo, '--apply' => true, '--ensayo' => true])->assertExitCode(1);
        DB::table('respaldo.ventas')->where('id', 2)->update(['abonos' => 6779]);
        $this->artisan('saldos:conciliar-historico', ['archivo' => $archivo, '--apply' => true])->assertExitCode(1);
        $this->assertDatabaseHas('ventas', ['id' => 1, 'abonos_migrados' => null]);
        $this->assertDatabaseCount('saldo_conciliaciones', 0);
    }

    private function prepararConciliacion(): string
    {
        $documentos = [];
        foreach ([1, 2] as $id) {
            $this->documento('venta', 'cliente', $id);
            $documentos[] = ['tipo' => 'ventas', 'id' => $id, 'titular_id' => 1, 'total' => 7500, 'acuenta' => 100,
                'abonos_antes' => 6780, 'saldo_antes' => 620, 'historico' => 6780, 'saldo_despues' => 620];
        }
        DB::table('ventas')->update(['abonos_migrados' => null]);
        DB::statement("ATTACH DATABASE ':memory:' AS respaldo");
        DB::statement('CREATE TABLE respaldo.ventas AS SELECT * FROM ventas');
        DB::statement('CREATE TABLE respaldo.venta_provisional_detalles AS SELECT * FROM venta_provisional_detalles');
        $this->manifiesto = tempnam(sys_get_temp_dir(), 'saldos_test_');
        file_put_contents($this->manifiesto, json_encode(['base_destino' => ':memory:', 'base_origen' => 'respaldo',
            'fecha_corte' => '2026-09-21', 'lote' => 'prueba', 'documentos' => $documentos], JSON_THROW_ON_ERROR));

        return $this->manifiesto;
    }

    private function documento(string $prefix, string $owner, int $id): void
    {
        DB::table($prefix.'s')->insert(['id' => $id, $owner.'_id' => 1, 'total' => 7500, 'acuenta' => 100, 'abonos' => 6780, 'abonos_migrados' => 6780, 'saldo' => 620]);
    }

    private function datos(string $prefix, string $owner, int $id, float $monto): array
    {
        return [$owner.'_id' => 1, 'numero_interno' => null, 'fecha_provisional' => '2026-09-29 12:00:00', 'principal' => $monto,
            'total_cobranza' => $monto, $prefix.'s' => [[$prefix.'_id' => $id, 'monto' => $monto, 'serie' => '1', 'correlativo' => $id, 'comprobante_tipo_codigo' => 'NP']]];
    }
}
