<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RectificacionAuditoriaService;
use PHPUnit\Framework\TestCase;

class RectificacionAuditoriaServiceTest extends TestCase
{
    public function test_compara_cabecera_y_detalle_modificado(): void
    {
        $service = new RectificacionAuditoriaService;
        $anterior = $this->snapshot(100, 10);
        $nuevo = $this->snapshot(120, 12);

        $cambios = $service->comparar($anterior, $nuevo);

        $this->assertSame('Total', $cambios['cabecera'][0]['campo']);
        $this->assertSame(100, $cambios['cabecera'][0]['anterior']);
        $this->assertSame(120, $cambios['cabecera'][0]['nuevo']);
        $this->assertSame('ALIMENTO — Costo unitario', $cambios['detalles'][0]['campo']);
        $this->assertSame('modificado', $cambios['detalles'][0]['tipo']);
    }

    public function test_clasifica_detalles_agregados_y_eliminados_por_ocurrencia(): void
    {
        $service = new RectificacionAuditoriaService;
        $anterior = $this->snapshot(100, 10);
        $nuevo = $this->snapshot(100, 10);
        $nuevo['detalles'][0]['clave'] = '20|SAC|1';
        $nuevo['detalles'][0]['producto'] = 'MAÍZ';

        $cambios = $service->comparar($anterior, $nuevo);

        $this->assertSame(['eliminado', 'agregado'], array_column($cambios['detalles'], 'tipo'));
    }

    public function test_ignora_diferencias_solo_de_representacion_numerica(): void
    {
        $service = new RectificacionAuditoriaService;
        $anterior = $this->snapshot('100.0000', '10.00');
        $nuevo = $this->snapshot(100, 10);

        $this->assertSame(['cabecera' => [], 'detalles' => []], $service->comparar($anterior, $nuevo));
    }

    private function snapshot(mixed $total, mixed $costo): array
    {
        return [
            'cabecera' => [
                'total' => ['etiqueta' => 'Total', 'formato' => 'moneda', 'valor' => $total],
            ],
            'detalles' => [[
                'clave' => '10|SAC|1',
                'producto' => 'ALIMENTO',
                'unidad' => 'SAC',
                'ocurrencia' => 1,
                'valores' => [
                    'costo_unitario' => ['etiqueta' => 'Costo unitario', 'formato' => 'costo', 'valor' => $costo],
                ],
            ]],
        ];
    }
}
