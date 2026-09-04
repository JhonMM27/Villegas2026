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
        if (! Schema::hasColumn('planilla_pagos', 'fecha_pago_original')) {
            Schema::table('planilla_pagos', fn (Blueprint $table) => $table->date('fecha_pago_original')->nullable()->after('fecha_pago'));
        }
        if (! Schema::hasColumn('planilla_pagos', 'anulado_at')) {
            Schema::table('planilla_pagos', fn (Blueprint $table) => $table->timestamp('anulado_at')->nullable()->after('estado'));
        }
        if (! Schema::hasColumn('planilla_pagos', 'anulado_por')) {
            Schema::table('planilla_pagos', fn (Blueprint $table) => $table->unsignedSmallInteger('anulado_por')->nullable()->after('anulado_at'));
        }
        if (! Schema::hasColumn('planilla_pagos', 'motivo_anulacion')) {
            Schema::table('planilla_pagos', fn (Blueprint $table) => $table->string('motivo_anulacion', 500)->nullable()->after('anulado_por'));
        }

        Schema::table('planilla_pagos', function (Blueprint $table): void {
            $table->enum('estado', ['pendiente', 'pagado', 'anulado'])
                ->default('pendiente')
                ->change();
        });

        DB::table('planilla_pagos')
            ->where('estado', 'pagado')
            ->whereNotNull('fecha_pago')
            ->update(['fecha_pago_original' => DB::raw('fecha_pago')]);

        if (! Schema::hasTable('planilla_pago_movimientos')) {
            Schema::create('planilla_pago_movimientos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('planilla_pago_id')->constrained('planilla_pagos')->cascadeOnDelete();
                $table->string('accion', 30);
                $table->string('estado_anterior', 20);
                $table->string('estado_nuevo', 20);
                $table->date('fecha_pago_anterior')->nullable();
                $table->date('fecha_pago_nueva')->nullable();
                $table->string('motivo', 500)->nullable();
                $table->unsignedSmallInteger('user_id')->nullable();
                $table->string('user_nombre', 100)->default('Sistema');
                $table->timestamps();

                $table->index(['planilla_pago_id', 'created_at'], 'pago_movimientos_pago_fecha_index');
                $table->index(['accion', 'created_at'], 'pago_movimientos_accion_fecha_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_pago_movimientos');

        Schema::table('planilla_pagos', function (Blueprint $table): void {
            $table->dropColumn(['fecha_pago_original', 'anulado_at', 'anulado_por', 'motivo_anulacion']);
        });

        Schema::table('planilla_pagos', function (Blueprint $table): void {
            $table->enum('estado', ['pendiente', 'pagado'])
                ->default('pendiente')
                ->change();
        });
    }
};
