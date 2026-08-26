<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rectificacion_historiales', function (Blueprint $table): void {
            $table->id();
            $table->string('modulo', 50);
            $table->unsignedBigInteger('registro_id');
            $table->unsignedTinyInteger('numero_rectificacion');
            $table->string('registro_referencia', 150);
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->string('user_nombre', 100);
            $table->string('motivo', 500)->nullable();
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');
            $table->json('cambios');
            $table->timestamps();

            $table->unique(
                ['modulo', 'registro_id', 'numero_rectificacion'],
                'rect_hist_modulo_registro_num_unique'
            );
            $table->index(['modulo', 'registro_id'], 'rect_hist_modulo_registro_index');
            $table->index('created_at', 'rect_hist_fecha_index');
            $table->index('user_id', 'rect_hist_usuario_index');
            $table->foreign('user_id', 'rect_hist_usuario_foreign')
                ->references('id')->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('venta_entregas', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rectificacion_count')->default(0)->after('estado');
        });

        Schema::table('cuadre_stocks', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rectificacion_count')->default(0)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('cuadre_stocks', function (Blueprint $table): void {
            $table->dropColumn('rectificacion_count');
        });

        Schema::table('venta_entregas', function (Blueprint $table): void {
            $table->dropColumn('rectificacion_count');
        });

        Schema::dropIfExists('rectificacion_historiales');
    }
};
