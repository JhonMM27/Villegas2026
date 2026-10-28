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
        Schema::create('telefonos_ticket', function (Blueprint $table): void {
            $table->id();
            $table->string('telefono_1', 25);
            $table->string('telefono_2', 25)->nullable();
            $table->timestamps();
        });
        DB::table('telefonos_ticket')->insert([
            'id' => 1, 'telefono_1' => '967984895', 'telefono_2' => '978431737',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('telefonos_ticket');
    }
};
