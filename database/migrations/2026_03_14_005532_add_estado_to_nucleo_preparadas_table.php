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
        Schema::table('nucleo_preparadas', function (Blueprint $table) {
            $table->enum('estado', ['registrada', 'anulada', 'rectificada'])->default('registrada')->after('items');
            $table->text('nota')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nucleo_preparadas', function (Blueprint $table) {
            $table->dropColumn(['estado', 'nota']);
        });
    }
};
