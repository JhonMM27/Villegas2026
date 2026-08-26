<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_eventos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_evento', 20);
            $table->string('modulo', 50);
            $table->unsignedBigInteger('registro_id');
            $table->unsignedTinyInteger('numero_rectificacion')->nullable();
            $table->string('registro_referencia', 150);
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->string('user_nombre', 100);
            $table->string('motivo', 500)->nullable();
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');
            $table->json('cambios');
            $table->timestamps();

            $table->unique(
                ['tipo_evento', 'modulo', 'registro_id', 'numero_rectificacion'],
                'audit_tipo_modulo_registro_num_unique'
            );
            $table->index(['tipo_evento', 'created_at'], 'audit_tipo_fecha_index');
            $table->index(['modulo', 'registro_id'], 'audit_modulo_registro_index');
            $table->index('user_id', 'audit_usuario_index');
            $table->index('registro_referencia', 'audit_referencia_index');
            $table->foreign('user_id', 'audit_usuario_foreign')
                ->references('id')->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        if (Schema::hasTable('rectificacion_historiales')) {
            DB::statement(<<<'SQL'
                INSERT INTO auditoria_eventos
                    (tipo_evento, modulo, registro_id, numero_rectificacion, registro_referencia,
                     user_id, user_nombre, motivo, datos_anteriores, datos_nuevos, cambios, created_at, updated_at)
                SELECT 'rectificacion', rh.modulo, rh.registro_id, rh.numero_rectificacion, rh.registro_referencia,
                       rh.user_id, rh.user_nombre, NULLIF(TRIM(rh.motivo), ''),
                       rh.datos_anteriores, rh.datos_nuevos, rh.cambios, rh.created_at, rh.updated_at
                FROM rectificacion_historiales rh
                WHERE NOT EXISTS (
                    SELECT 1 FROM auditoria_eventos ae
                    WHERE ae.tipo_evento = 'rectificacion'
                      AND ae.modulo = rh.modulo
                      AND ae.registro_id = rh.registro_id
                      AND ae.numero_rectificacion = rh.numero_rectificacion
                )
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_eventos');
    }
};
