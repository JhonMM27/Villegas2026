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
        Schema::create('cobranza_tipos', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('nombre', 50)->comment('nombre del tipo de cobro');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        DB::table('cobranza_tipos')->insert([
            ['id' => 1, 'nombre' => 'PRINCIPAL'],
            ['id' => 2, 'nombre' => 'DEPOSITO'],
            ['id' => 3, 'nombre' => 'CONSORCIO'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cobranza_tipos');
    }
};
