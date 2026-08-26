<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditoriaEvento;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
            $table->string('motivo', 500);
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

    public function test_solo_el_rol_admin_puede_consultar_un_evento(): void
    {
        $evento = $this->crearEvento();
        $admin = User::query()->create($this->datosUsuario('admin@example.test'));
        $operador = User::query()->create($this->datosUsuario('operador@example.test'));
        Role::query()->create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole('admin');

        $this->actingAs($operador)->getJson(route('auditoria.show', $evento))->assertForbidden();

        $this->actingAs($admin)
            ->getJson(route('auditoria.show', $evento))
            ->assertOk()
            ->assertJsonPath('accion', 'Rectificación')
            ->assertJsonPath('motivo', 'Corrección del costo registrado');
    }

    public function test_las_tres_rutas_estan_protegidas_por_el_rol_admin(): void
    {
        foreach (['auditoria.index', 'auditoria.data', 'auditoria.show'] as $nombre) {
            $middleware = Route::getRoutes()->getByName($nombre)?->gatherMiddleware() ?? [];
            $this->assertContains('auth', $middleware);
            $this->assertContains('role:admin', $middleware);
        }
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
            'cambios' => ['cabecera' => [], 'detalles' => []],
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
