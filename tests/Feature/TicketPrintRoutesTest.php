<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketPrintRoutesTest extends TestCase
{
    public function test_sales_and_quotations_print_integer_correlatives_through_the_controller(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        foreach (['ventas' => 'venta', 'cotizaciones' => 'cotizacion'] as $table => $domain) {
            Schema::create($table, function (Blueprint $blueprint) use ($domain): void {
                $blueprint->id();
                $blueprint->integer('correlativo');
                $blueprint->string('serie');
                $blueprint->unsignedBigInteger('cliente_id')->nullable();
                $blueprint->string('cliente_nombre');
                $blueprint->dateTime('fecha_'.$domain);
                $blueprint->decimal('total', 12, 2);
                $blueprint->decimal('saldo', 12, 2)->default(0);
                $blueprint->string('estado')->default('registrada');
            });
            Schema::create($domain.'_detalles', function (Blueprint $blueprint) use ($domain): void {
                $blueprint->id();
                $blueprint->unsignedBigInteger($domain.'_id');
            });
            DB::table($table)->insert([
                'id' => 1, 'correlativo' => 167, 'serie' => '01',
                'cliente_nombre' => 'PRUEBA', 'fecha_'.$domain => '2026-09-03 14:45:00', 'total' => 16,
            ]);

            $response = $this->withoutMiddleware()->get(route($table.'.imprimir', 1));
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertStringContainsString('01-00000167.pdf', $response->headers->get('content-disposition'));
        }
    }
}
