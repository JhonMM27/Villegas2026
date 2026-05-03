<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuadre_stocks', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('estado', 20)->default('completado');
            $table->text('notas')->nullable();
            $table->unsignedSmallInteger('user_id');
            $table->timestamps();
            $table->index('fecha');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuadre_stocks');
    }
};
