<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_ingresos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->decimal('monto', 10, 2)->default(0);
            $table->enum('caja_destino', ['P', 'D', 'C'])
                ->comment('P=Principal, D=Depósito, C=Consorcio');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_nombre', 150)->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->index('fecha', 'idx_caja_ingresos_fecha');
            $table->index('caja_destino', 'idx_caja_ingresos_caja');
            $table->index('user_id', 'idx_caja_ingresos_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_ingresos');
    }
};
