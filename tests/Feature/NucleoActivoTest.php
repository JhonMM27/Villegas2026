<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\NucleoController;
use App\Http\Controllers\ProductoController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NucleoActivoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('unidades', function (Blueprint $table) {
            $table->string('codigo')->primary();
            $table->string('descripcion');
        });

        Schema::create('lineas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo')->nullable();
            $table->string('nombre');
            $table->string('unidad_codigo');
            $table->unsignedInteger('linea_id');
            $table->decimal('costo_unitario', 12, 4)->default(0);
            $table->decimal('empaque', 12, 4)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('nucleos', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('nombre');
            $table->string('unidad_codigo');
            $table->string('unidad_nombre')->nullable();
            $table->decimal('empaque', 12, 4)->default(0);
            $table->decimal('cantidad_porcentaje', 12, 4)->default(0);
            $table->unsignedInteger('items')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('nucleo_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('nucleo_id');
            $table->unsignedInteger('producto_id');
            $table->string('producto_nombre');
            $table->string('unidad_codigo');
            $table->decimal('cantidad', 12, 4);
        });

        DB::table('unidades')->insert([
            'codigo' => 'KGM',
            'descripcion' => 'Kilogramo',
        ]);

        DB::table('lineas')->insert([
            ['id' => 1, 'nombre' => 'NUCLEO'],
            ['id' => 2, 'nombre' => 'ADITIVO'],
            ['id' => 3, 'nombre' => 'INSUMO'],
        ]);

        $this->insertarProducto(100, 'NUCLEO ACTIVO', 1, true);
        $this->insertarProducto(101, 'NUCLEO INACTIVO', 1, false);
        $this->insertarProducto(102, 'OTRO NUCLEO ACTIVO', 1, true);
        $this->insertarProducto(103, 'NUCLEO DESHABILITADO', 1, true);
        $this->insertarProducto(200, 'ADITIVO ACTIVO', 2, true);
        $this->insertarProducto(201, 'ADITIVO HISTORICO INACTIVO', 2, false);
        $this->insertarProducto(202, 'ADITIVO NUEVO INACTIVO', 2, false);
        $this->insertarProducto(203, 'BICARBONATO', 3, true);
        $this->insertarProducto(204, 'INSUMO INACTIVO', 3, false);

        $this->insertarNucleo(100, true, 200);
        $this->insertarNucleo(101, true, 200);
        $this->insertarNucleo(103, false, 200);
    }

    public function test_crear_y_editar_persisten_el_estado_activo_enviado_por_el_switch(): void
    {
        $crear = Request::create('/nucleos', 'POST', [
            'producto_id_nucleo' => 102,
            'detalles' => [$this->detalle(200)],
        ]);

        $respuestaCrear = app(NucleoController::class)->store($crear);

        $this->assertTrue($respuestaCrear->getData(true)['success']);
        $this->assertDatabaseHas('nucleos', [
            'id' => 102,
            'activo' => 0,
        ]);

        $editar = Request::create('/nucleos/100', 'PUT', [
            'producto_id_nucleo' => 100,
            'detalles' => [$this->detalle(200)],
        ]);

        $respuestaEditar = app(NucleoController::class)->update($editar, 100);

        $this->assertTrue($respuestaEditar->getData(true)['success']);
        $this->assertDatabaseHas('nucleos', [
            'id' => 100,
            'activo' => 0,
        ]);
    }

    public function test_buscadores_devuelven_solo_productos_y_nucleos_activos(): void
    {
        $productosNucleo = app(ProductoController::class)
            ->buscarProductoNucleo(Request::create('/productos/buscar-nucleo', 'GET', ['q' => 'NUCLEO']));

        $this->assertSame(
            [100, 102, 103],
            $productosNucleo->pluck('id')->sort()->values()->all()
        );

        $aditivos = app(ProductoController::class)
            ->buscarProductoAditivo(Request::create('/productos/buscar-aditivo', 'GET', ['q' => 'ADITIVO']));

        $this->assertSame([200], $aditivos->pluck('id')->all());

        $insumos = app(ProductoController::class)
            ->buscarProductoAditivo(Request::create('/productos/buscar-aditivo', 'GET', ['q' => 'BICARBONATO']));

        $this->assertSame([203], $insumos->pluck('id')->all());

        $insumosInactivos = app(ProductoController::class)
            ->buscarProductoAditivo(Request::create('/productos/buscar-aditivo', 'GET', ['q' => 'INSUMO INACTIVO']));

        $this->assertTrue($insumosInactivos->isEmpty());

        $nucleos = app(NucleoController::class)
            ->buscar(Request::create('/nucleos/buscar', 'GET', ['q' => 'NUCLEO']));

        $this->assertSame([100], $nucleos->pluck('id')->all());
    }

    public function test_edicion_conserva_un_producto_historico_inactivo_y_lo_expone_en_el_json(): void
    {
        DB::table('nucleo_detalles')
            ->where('nucleo_id', 100)
            ->update([
                'producto_id' => 201,
                'producto_nombre' => 'ADITIVO HISTORICO INACTIVO',
            ]);

        $request = Request::create('/nucleos/100', 'PUT', [
            'producto_id_nucleo' => 100,
            'activo' => '1',
            'detalles' => [$this->detalle(201)],
        ]);

        $response = app(NucleoController::class)->update($request, 100);

        $this->assertTrue($response->getData(true)['success']);

        $registro = app(NucleoController::class)
            ->show(100)
            ->getData(true);

        $this->assertSame(0, (int) $registro['detalles'][0]['producto']['activo']);
    }

    public function test_edicion_rechaza_un_producto_inactivo_que_no_pertenecia_al_nucleo(): void
    {
        $request = Request::create('/nucleos/100', 'PUT', [
            'producto_id_nucleo' => 100,
            'activo' => '1',
            'detalles' => [
                $this->detalle(200),
                $this->detalle(202),
            ],
        ]);

        try {
            app(NucleoController::class)->update($request, 100);
            $this->fail('La validación debía rechazar el producto inactivo nuevo.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'detalles.1.producto_id',
                $exception->errors()
            );
        }
    }

    private function insertarProducto(
        int $id,
        string $nombre,
        int $lineaId,
        bool $activo
    ): void {
        DB::table('productos')->insert([
            'id' => $id,
            'codigo' => (string) $id,
            'nombre' => $nombre,
            'unidad_codigo' => 'KGM',
            'linea_id' => $lineaId,
            'costo_unitario' => 1,
            'empaque' => 25,
            'activo' => $activo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertarNucleo(
        int $id,
        bool $activo,
        int $productoDetalleId
    ): void {
        DB::table('nucleos')->insert([
            'id' => $id,
            'nombre' => DB::table('productos')->where('id', $id)->value('nombre'),
            'unidad_codigo' => 'KGM',
            'unidad_nombre' => 'Kilogramo',
            'empaque' => 25,
            'cantidad_porcentaje' => 1,
            'items' => 1,
            'activo' => $activo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('nucleo_detalles')->insert([
            'nucleo_id' => $id,
            'producto_id' => $productoDetalleId,
            'producto_nombre' => DB::table('productos')
                ->where('id', $productoDetalleId)
                ->value('nombre'),
            'unidad_codigo' => 'KGM',
            'cantidad' => 1,
        ]);
    }

    /**
     * @return array{producto_id: int, unidad_codigo: string, cantidad: float}
     */
    private function detalle(int $productoId): array
    {
        return [
            'producto_id' => $productoId,
            'unidad_codigo' => 'KGM',
            'cantidad' => 1.0,
        ];
    }
}
