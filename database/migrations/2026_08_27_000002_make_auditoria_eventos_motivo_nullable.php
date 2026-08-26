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
        Schema::table('auditoria_eventos', function (Blueprint $table): void {
            $table->string('motivo', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('auditoria_eventos')->whereNull('motivo')->update(['motivo' => '']);

        Schema::table('auditoria_eventos', function (Blueprint $table): void {
            $table->string('motivo', 500)->nullable(false)->change();
        });
    }
};
