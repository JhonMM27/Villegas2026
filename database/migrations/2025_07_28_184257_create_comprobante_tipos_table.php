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
        Schema::create('comprobante_tipos', function (Blueprint $table) {
            $table->char('codigo', 2)->primary();
            $table->string('descripcion', 50);
            $table->string('modulo', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        DB::table('comprobante_tipos')->insert([
            ['codigo' => '00', 'descripcion' => 'Otros', 'modulo' => 'VENTA'],
            ['codigo' => '01', 'descripcion' => 'Factura', 'modulo' => 'VENTA'],
            ['codigo' => '03', 'descripcion' => 'Boleta de Venta', 'modulo' => 'VENTA'],
            ['codigo' => '07', 'descripcion' => 'Nota de Crédito', 'modulo' => 'VENTA'],
            ['codigo' => '08', 'descripcion' => 'Nota de Débito', 'modulo' => 'VENTA'],
            ['codigo' => '09', 'descripcion' => 'Guía de Remisión - Remitente', 'modulo' => 'VENTA'],
            ['codigo' => 'NP', 'descripcion' => 'Nota de Pedido', 'modulo' => 'VENTA'],
            ['codigo' => 'NC', 'descripcion' => 'Nota de Compra', 'modulo' => 'COMPRA'],
            ['codigo' => 'CZ', 'descripcion' => 'Cotización', 'modulo' => 'VENTA'],
            ['codigo' => 'SP', 'descripcion' => 'Salida Préstamo', 'modulo' => 'PRESTAMO'],
            ['codigo' => 'DP', 'descripcion' => 'Devolución Préstamo', 'modulo' => 'PRESTAMO'],
            ['codigo' => 'IP', 'descripcion' => 'Ingreso Préstamo', 'modulo' => 'PRESTAMO'],
            ['codigo' => 'SD', 'descripcion' => 'Salida Devolución', 'modulo' => 'PRESTAMO'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobante_tipos');
    }
};
