<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingrediente_datos_nutricionales', function (Blueprint $table) {
            $table->decimal('em', 6, 3)->nullable()->after('enl')->comment('Energía Metabolizable Mcal/kg');
            $table->decimal('fibra', 5, 2)->nullable()->after('fdn')->comment('Fibra bruta %');
            $table->decimal('fda', 5, 2)->nullable()->after('fibra')->comment('FDA %');
            $table->decimal('calcio', 5, 2)->nullable()->after('fda')->comment('Calcio %');
            $table->decimal('fosforo', 5, 2)->nullable()->after('calcio')->comment('Fósforo %');
            $table->decimal('magnesio', 5, 2)->nullable()->after('fosforo')->comment('Magnesio %');
            $table->decimal('ceniza', 5, 2)->nullable()->after('azucar')->comment('Ceniza %');
            $table->decimal('lactosa', 5, 2)->nullable()->after('ceniza')->comment('Lactosa %');
            $table->decimal('lisina', 5, 3)->nullable()->after('lactosa')->comment('Lisina %');
            $table->decimal('metionina', 5, 3)->nullable()->after('lisina')->comment('Metionina %');
            $table->decimal('treonina', 5, 3)->nullable()->after('metionina')->comment('Treonina %');

            $table->index('clasificacion', 'idx_idn_clasificacion');
        });
    }

    public function down(): void
    {
        Schema::table('ingrediente_datos_nutricionales', function (Blueprint $table) {
            $table->dropIndex('idx_idn_clasificacion');
            $table->dropColumn([
                'em', 'fibra', 'fda', 'calcio', 'fosforo', 'magnesio',
                'ceniza', 'lactosa', 'lisina', 'metionina', 'treonina',
            ]);
        });
    }
};
