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
        foreach (['venta_provisionales', 'compra_provisionales'] as $modulo) {
            if (DB::table($modulo)->select('numero_recibo')->whereNotNull('numero_recibo')->groupBy('numero_recibo')->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException('Existen numeros repetidos en '.$modulo.'. Revise la evidencia antes de migrar.');
            }
        }

        Schema::create('recibo_contadores', function (Blueprint $t): void {
            $t->string('modulo', 50)->primary();
            $t->unsignedBigInteger('ultimo_numero');
        });
        Schema::create('recibo_solicitudes', function (Blueprint $t): void {
            $t->id();
            $t->string('modulo', 50);
            $t->uuid('clave');
            $t->string('huella', 64);
            $t->unsignedBigInteger('recibo_id');
            $t->timestamp('created_at');
            $t->unique(['modulo', 'clave']);
        });
        foreach (['venta_provisionales', 'compra_provisionales'] as $modulo) {
            $ultimo = 0;
            foreach (DB::table($modulo)->orderBy('id')->pluck('numero_recibo') as $numero) {
                if (ctype_digit((string) $numero)) {
                    $ultimo = max($ultimo, (int) $numero);
                }
            }
            DB::table('recibo_contadores')->insert(['modulo' => $modulo, 'ultimo_numero' => $ultimo]);
            Schema::table($modulo, fn (Blueprint $t) => $t->unique('numero_recibo'));
        }
    }

    public function down(): void
    {
        if (DB::table('recibo_solicitudes')->exists()) {
            throw new RuntimeException('Hay solicitudes protegidas; no retire sus claves ni reutilice numeros.');
        }
        foreach (['venta_provisionales', 'compra_provisionales'] as $modulo) {
            Schema::table($modulo, fn (Blueprint $t) => $t->dropUnique(['numero_recibo']));
        }
        Schema::dropIfExists('recibo_solicitudes');
        Schema::dropIfExists('recibo_contadores');
    }
};
