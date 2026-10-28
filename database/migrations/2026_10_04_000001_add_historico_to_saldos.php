<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ventas', 'compras'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->decimal('abonos_migrados', 12, 2)->nullable();
            });
        }

        Schema::create('saldo_conciliaciones', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo', 10);
            $table->unsignedBigInteger('documento_id');
            $table->string('lote', 100);
            $table->string('base_origen', 100);
            $table->date('fecha_corte');
            $table->string('huella', 64);
            $table->json('anteriores');
            $table->json('nuevos');
            $table->json('evidencia');
            $table->string('actor', 100);
            $table->timestamps();
            $table->unique(['tipo', 'documento_id', 'lote'], 'saldo_conciliacion_lote_unique');
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('saldo_conciliaciones')->exists()) {
            throw new RuntimeException('Existen conciliaciones aplicadas. Restaure una copia compatible antes de retirar el historico.');
        }

        Schema::dropIfExists('saldo_conciliaciones');
        foreach (['ventas', 'compras'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('abonos_migrados'));
        }
    }
};
