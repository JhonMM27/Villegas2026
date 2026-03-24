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
        Schema::create('afectacion_tipos', function (Blueprint $table) {
            $table->char('codigo', 2)->primary();
            $table->char('nombre', 3);
            $table->string('descripcion', 50);
            $table->char('letra', 1);
            $table->decimal('porcentaje', 4, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('afectacion_tipos');
    }
};
