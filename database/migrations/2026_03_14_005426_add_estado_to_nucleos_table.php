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
        Schema::table('nucleos', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'anulado'])->default('activo')->after('activo');
            $table->text('nota')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nucleos', function (Blueprint $table) {
            $table->dropColumn(['estado', 'nota']);
        });
    }
};
