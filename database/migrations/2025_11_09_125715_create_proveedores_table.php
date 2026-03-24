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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->char('documento_tipo_codigo', 2);
            $table->string('documento_numero', 20)->nullable();
            $table->string('razon_social', 100);
            $table->string('direccion', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('representante', 50)->nullable();
            $table->string('representante_telefono', 20)->nullable();
            $table->string('cuenta_bancaria', 20)->nullable();
            $table->timestamps();

            $table->foreign('documento_tipo_codigo')
                    ->references('codigo')
                    ->on('documento_tipos')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
