<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingrediente_datos_nutricionales', function (Blueprint $table) {
            $table->decimal('aporte', 10, 4)->nullable()->after('nutriente');
        });
    }

    public function down(): void
    {
        Schema::table('ingrediente_datos_nutricionales', function (Blueprint $table) {
            $table->dropColumn('aporte');
        });
    }
};
