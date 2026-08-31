<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditoriaEvento;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditoriaControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->smallIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('auditoria_eventos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_evento', 20);
            $table->string('modulo', 50);
            $table->unsignedBigInteger('registro_id');
            $table->unsignedTinyInteger('numero_rectificacion')->nullable();
            $table->string('registro_referencia', 150);
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->string('user_nombre', 100);
            $table->string('motivo', 500)->nullable();
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');
            $table->json('cambios');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_solo_el_usuario_con_permiso_puede_consultar_un_evento(): void
    {
        $evento = $this->crearEvento();
        $admin = User::query()->create($this->datosUsuario('admin@example.test'));
        $operador = User::query()->create($this->datosUsuario('operador@example.test'));
        $this->asignarPermisoAuditoria($admin);

        $this->actingAs($operador)->getJson(route('auditoria.show', $evento))->assertForbidden();

        $this->actingAs($admin)
            ->getJson(route('auditoria.show', $evento))
            ->assertOk()
            ->assertJsonPath('accion', 'Rectificación')
            ->assertJsonPath('motivo', 'Corrección del costo registrado');
    }

    public function test_el_admin_puede_generar_el_pdf_del_evento(): void
    {
        $evento = $this->crearEvento();
        $admin = User::query()->create($this->datosUsuario('admin-pdf@example.test'));
        $this->asignarPermisoAuditoria($admin);

        $response = $this->actingAs($admin)->get(route('auditoria.pdf', $evento));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_un_usuario_sin_permiso_no_puede_generar_el_pdf(): void
    {
        $evento = $this->crearEvento();
        $operador = User::query()->create($this->datosUsuario('operador-pdf@example.test'));

        $this->actingAs($operador)
            ->get(route('auditoria.pdf', $evento))
            ->assertForbidden();
    }

    public function test_el_pdf_se_genera_cuando_el_motivo_es_nulo(): void
    {
        $evento = $this->crearEvento();
        $evento->update(['motivo' => null]);
        $admin = User::query()->create($this->datosUsuario('admin-sin-motivo@example.test'));
        $this->asignarPermisoAuditoria($admin);

        $this->actingAs($admin)
            ->get(route('auditoria.pdf', $evento))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_la_vista_pdf_incluye_fecha_usuario_motivo_y_cambios(): void
    {
        $evento = $this->crearEvento();
        $evento->created_at = '2026-08-28 14:35:22';

        $html = view('auditoria.evento_pdf', [
            'evento' => $evento,
            'accion' => 'Rectificación',
            'moduloNombre' => 'Compras',
            'empresa' => (object) [
                'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
                'direccion' => 'Carretera Pomalca KM 3',
                'ruc' => '20538937321',
            ],
        ])->render();

        $this->assertStringContainsString('28/08/2026', $html);
        $this->assertStringContainsString('14:35:22', $html);
        $this->assertStringContainsString('Administrador', $html);
        $this->assertStringContainsString('Corrección del costo registrado', $html);
        $this->assertStringContainsString('S/ 100.00', $html);
        $this->assertStringContainsString('S/ 120.00', $html);
    }

    public function test_las_cuatro_rutas_estan_protegidas_por_el_permiso_de_auditoria(): void
    {
        foreach (['auditoria.index', 'auditoria.data', 'auditoria.show', 'auditoria.pdf'] as $nombre) {
            $middleware = Route::getRoutes()->getByName($nombre)?->gatherMiddleware() ?? [];
            $this->assertContains('auth', $middleware);
            $this->assertContains('can:auditoria_list', $middleware);
        }
    }

    private function asignarPermisoAuditoria(User $usuario): void
    {
        $permiso = Permission::query()->firstOrCreate([
            'name' => 'auditoria_list',
            'guard_name' => 'web',
        ]);
        $rol = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $rol->givePermissionTo($permiso);
        $usuario->assignRole($rol);
    }

    private function crearEvento(): AuditoriaEvento
    {
        return AuditoriaEvento::query()->create([
            'tipo_evento' => AuditoriaEvento::TIPO_RECTIFICACION,
            'modulo' => 'compras',
            'registro_id' => 15,
            'numero_rectificacion' => 1,
            'registro_referencia' => 'FC - F001 - 15',
            'user_nombre' => 'Administrador',
            'motivo' => 'Corrección del costo registrado',
            'datos_anteriores' => ['cabecera' => [], 'detalles' => []],
            'datos_nuevos' => ['cabecera' => [], 'detalles' => []],
            'cambios' => [
                'cabecera' => [
                    [
                        'campo' => 'Costo total',
                        'anterior' => 100,
                        'nuevo' => 120,
                        'tipo' => 'modificado',
                        'formato' => 'moneda',
                    ],
                ],
                'detalles' => [],
            ],
        ]);
    }

    private function datosUsuario(string $email): array
    {
        return [
            'name' => 'Usuario',
            'email' => $email,
            'password' => 'secret123',
            'activo' => true,
        ];
    }
}
