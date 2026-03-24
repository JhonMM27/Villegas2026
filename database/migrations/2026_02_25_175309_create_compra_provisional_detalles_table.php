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
        Schema::create('compra_provisional_detalles', function (Blueprint $table) {
            // 🔑 Identificación
            $table->increments('id');
            $table->unsignedInteger('compra_provisional_id');
            // Comprobante
            $table->unsignedInteger('compra_id')->nullable();
            $table->char('comprobante_tipo_codigo', 2)->nullable(); // tip_docr
            $table->string('serie', 4)->nullable();                 // prefijor
            $table->integer('correlativo')->nullable();   
            $table->decimal('monto', 9, 2)->default(0);
            $table->string('comentario', 100)->nullable();

            // FK (opcional, si user_id siempre existe en users)
            $table->foreign('compra_provisional_id')->references('id')->on('compra_provisionales')->cascadeOnDelete();
            $table->foreign('compra_id')->references('id')->on('compras')->nullOnDelete();;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_provisional_detalles');
    }
};
