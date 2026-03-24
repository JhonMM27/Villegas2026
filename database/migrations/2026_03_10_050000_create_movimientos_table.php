<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->increments('id');
            $table->datetime('fecha');

            $table->string('tipo', 25);
            $table->string('transaccion_tipo', 30);
            $table->unsignedInteger('transaccion_id');
            $table->unsignedInteger('detalle_id')->nullable();

            $table->unsignedSmallInteger('producto_id');
            $table->string('producto_nombre', 50)->nullable();
            $table->unsignedSmallInteger('empaque')->nullable();
            $table->char('unidad_codigo', 3)->nullable();

            $table->decimal('cantidad', 10, 4)->default(0);
            $table->decimal('cantidad_kg', 10, 4)->default(0);
            $table->decimal('entrada', 10, 4)->default(0);
            $table->decimal('salida', 10, 4)->default(0);

            $table->decimal('costo_unitario', 10, 4)->default(0);
            $table->decimal('costo_total', 12, 4)->default(0);

            $table->decimal('stock_anterior', 10, 4)->default(0);
            $table->decimal('costo_actual', 10, 4)->default(0);
            $table->decimal('valor_anterior', 12, 4)->default(0);

            $table->decimal('stock_nuevo', 10, 4)->default(0);
            $table->decimal('costo_nuevo', 10, 4)->default(0);
            $table->decimal('valor_nuevo', 12, 4)->default(0);

            $table->unsignedSmallInteger('user_id')->nullable();
            $table->string('user_nombre', 100)->nullable();
            $table->string('comentario', 255)->nullable();

            $table->timestamps();

            $table->index('fecha');
            $table->index('producto_id');
            $table->index(['transaccion_tipo', 'transaccion_id'], 'idx_movimientos_transaccion');
            $table->index('tipo');

            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
