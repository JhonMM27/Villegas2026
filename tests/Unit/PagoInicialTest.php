<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\PagoInicial;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PagoInicialTest extends TestCase
{
    public function test_credito_con_abono_en_consorcio_ignora_total_del_navegador(): void
    {
        $data = PagoInicial::normalizarYValidar([
            'pago_forma_codigo' => '5',
            'total' => 9000,
            'principal' => 0,
            'deposito' => 0,
            'consorcio' => 7000,
            'total_cobranza' => 0,
        ], 'compra');

        $this->assertSame(7000.0, $data['consorcio']);
        $this->assertSame(7000.0, $data['total_cobranza']);
        $this->assertSame(2000.0, $data['total'] - $data['total_cobranza']);
    }

    #[DataProvider('distribucionesCredito')]
    public function test_credito_admite_distribuciones_validas(array $importes, float $esperado): void
    {
        $data = PagoInicial::normalizarYValidar([
            'pago_forma_codigo' => '4',
            'total' => 9000,
            ...$importes,
        ], 'venta');

        $this->assertSame($esperado, $data['total_cobranza']);
    }

    public static function distribucionesCredito(): array
    {
        return [
            'sin pago inicial' => [['principal' => 0, 'deposito' => 0, 'consorcio' => 0], 0.0],
            'principal' => [['principal' => 7000, 'deposito' => 0, 'consorcio' => 0], 7000.0],
            'deposito' => [['principal' => 0, 'deposito' => 7000, 'consorcio' => 0], 7000.0],
            'mixto' => [['principal' => 2000, 'deposito' => 1500, 'consorcio' => 3500], 7000.0],
            'pago completo' => [['principal' => 0, 'deposito' => 0, 'consorcio' => 9000], 9000.0],
        ];
    }

    public function test_rechaza_pago_inicial_mayor_al_total(): void
    {
        $this->expectException(ValidationException::class);

        PagoInicial::normalizarYValidar([
            'pago_forma_codigo' => '5',
            'total' => 9000,
            'principal' => 0,
            'deposito' => 0,
            'consorcio' => 9000.01,
        ], 'compra');
    }

    public function test_contado_debe_quedar_pagado_completamente(): void
    {
        $this->expectException(ValidationException::class);

        PagoInicial::normalizarYValidar([
            'pago_forma_codigo' => '1',
            'total' => 9000,
            'principal' => 7000,
            'deposito' => 0,
            'consorcio' => 0,
        ], 'venta');
    }
}
