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
        Schema::create('compras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();
            $table->unsignedSmallInteger('proveedor_id');
            $table->string('proveedor_nombre', 100)->nullable();
            $table->char('comprobante_tipo_codigo', 2);
            $table->string('comprobante_tipo_nombre', 2)->nullable();          
            $table->char('pago_forma_codigo', 2);
            $table->string('pago_forma_nombre', 20)->nullable();
            $table->string('serie', 4);
            $table->integer('correlativo');
            $table->datetime('fecha_compra');  
            $table->date('fecha_vencimiento')->nullable();  
            $table->string('moneda', 3);     
            $table->decimal('op_gravada', 8, 2);
            $table->decimal('op_exonerada', 8, 2);
            $table->decimal('op_inafecta', 8, 2);
            $table->decimal('impuesto', 8, 2);
            $table->decimal('total', 8, 2);
            $table->decimal('importe_p', 8, 2)->default(0);
            $table->decimal('importe_d', 8, 2)->default(0);
            $table->decimal('importe_c', 8, 2)->default(0);
            $table->decimal('acuenta', 8, 2)->default(0);
            $table->decimal('abonos', 8, 2)->default(0);
            $table->decimal('saldo', 8, 2)->default(0);
            $table->string('estado', 20);
            $table->timestamps();

            $table->index('fecha_compra');

            // Claves foráneas (si existen las tablas relacionadas)
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('proveedor_id')->references('id')->on('proveedores');
            $table->foreign('comprobante_tipo_codigo')->references('codigo')->on('comprobante_tipos');
            $table->foreign('pago_forma_codigo')->references('codigo')->on('pago_formas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
