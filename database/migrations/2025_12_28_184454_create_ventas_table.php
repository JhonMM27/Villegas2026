<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 100)->nullable();
            $table->unsignedSmallInteger('cliente_id');
            $table->string('cliente_nombre', 100)->nullable();
            $table->unsignedSmallInteger('items')->nullable();
            $table->char('comprobante_tipo_codigo', 2);
            $table->string('comprobante_tipo_nombre', 2)->nullable();
            $table->string('serie', 4);
            $table->integer('correlativo');
            $table->string('docpagoi', 10)->nullable();
            $table->datetime('fecha_venta');
            $table->char('pago_forma_codigo', 2);
            $table->string('pago_forma_nombre', 20);
            $table->date('fecha_vencimiento')->nullable();
            $table->string('moneda', 3);
            $table->decimal('op_gravada', 8, 2);
            $table->decimal('op_exonerada', 8, 2);
            $table->decimal('op_inafecta', 8, 2);
            $table->decimal('impuesto', 8, 2);
            $table->decimal('total', 8, 2);
            $table->decimal('importe_p', 8, 2);
            $table->decimal('importe_d', 8, 2);
            $table->decimal('importe_c', 8, 2);

            $table->decimal('acuenta', 8, 2);
            $table->decimal('abonos', 8, 2);
            $table->decimal('saldo', 8, 2);
            $table->decimal('rentabilidad', 8, 2)->default(0);

            $table->string('estado', 20);
            $table->timestamps();

            // Índices
            $table->index('fecha_venta');

            // Claves foráneas (si existen las tablas relacionadas)
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('comprobante_tipo_codigo')->references('codigo')->on('comprobante_tipos');
            $table->foreign('pago_forma_codigo')->references('codigo')->on('pago_formas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
