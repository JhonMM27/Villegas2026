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
        Schema::table('movimientos', function (Blueprint $table) {
            // Optimizar cantidades a DECIMAL(9,4)
            $table->decimal('cantidad', 9, 4)->default(0)->change();
            $table->decimal('cantidad_kg', 9, 4)->default(0)->change();
            $table->decimal('entrada', 9, 4)->default(0)->change();
            $table->decimal('salida', 9, 4)->default(0)->change();
            $table->decimal('stock_anterior', 9, 4)->default(0)->change();
            $table->decimal('stock_nuevo', 9, 4)->default(0)->change();

            // Optimizar valores monetarios a DECIMAL(10,4)
            $table->decimal('costo_unitario', 10, 4)->default(0)->change();
            $table->decimal('costo_total', 10, 4)->default(0)->change();
            $table->decimal('costo_actual', 10, 4)->default(0)->change();
            $table->decimal('valor_anterior', 10, 4)->default(0)->change();
            $table->decimal('costo_nuevo', 10, 4)->default(0)->change();
            $table->decimal('valor_nuevo', 10, 4)->default(0)->change();

            // Eliminar columna redundante
            $table->dropColumn('user_nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            // Revertir cantidades a DECIMAL(10,4)
            $table->decimal('cantidad', 10, 4)->default(0)->change();
            $table->decimal('cantidad_kg', 10, 4)->default(0)->change();
            $table->decimal('entrada', 10, 4)->default(0)->change();
            $table->decimal('salida', 10, 4)->default(0)->change();
            $table->decimal('stock_anterior', 10, 4)->default(0)->change();
            $table->decimal('stock_nuevo', 10, 4)->default(0)->change();

            // Revertir valores monetarios
            $table->decimal('costo_unitario', 10, 4)->default(0)->change();
            $table->decimal('costo_total', 12, 4)->default(0)->change();
            $table->decimal('costo_actual', 10, 4)->default(0)->change();
            $table->decimal('valor_anterior', 12, 4)->default(0)->change();
            $table->decimal('costo_nuevo', 10, 4)->default(0)->change();
            $table->decimal('valor_nuevo', 12, 4)->default(0)->change();

            // Restaurar columna
            $table->string('user_nombre', 100)->nullable();
        });
    }
};
