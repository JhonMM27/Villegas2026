<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class RectificacionAuditoriaService
{
    public const MODULOS = [
        'compras', 'ventas', 'prestamos', 'preparadas',
        'nucleo_preparadas', 'venta_entregas',
    ];

    private const CONFIG = [
        'compras' => [
            'referencia' => ['comprobante_tipo_nombre', 'serie', 'correlativo'],
            'cabecera' => [
                'proveedor_nombre' => ['Proveedor', 'texto'], 'fecha_compra' => ['Fecha de compra', 'fecha'],
                'fecha_vencimiento' => ['Vencimiento', 'fecha'], 'pago_forma_nombre' => ['Forma de pago', 'texto'],
                'total' => ['Total', 'moneda'], 'importe_p' => ['Caja principal', 'moneda'],
                'importe_d' => ['Depósito', 'moneda'], 'importe_c' => ['Consorcio', 'moneda'],
                'acuenta' => ['Pago inicial', 'moneda'], 'abonos' => ['Abonos', 'moneda'], 'saldo' => ['Saldo', 'moneda'],
            ],
            'detalle' => [
                'unidad_codigo' => ['Unidad', 'texto'], 'cantidad' => ['Cantidad', 'cantidad'],
                'cantidad_kgm' => ['Cantidad kg', 'cantidad'], 'costo_unitario' => ['Costo unitario', 'costo'],
                'costo_unitario_servicio' => ['Costo servicio', 'costo'], 'impuesto' => ['Impuesto', 'moneda'],
                'total' => ['Total de línea', 'moneda'],
            ],
        ],
        'ventas' => [
            'referencia' => ['comprobante_tipo_nombre', 'serie', 'correlativo'],
            'cabecera' => [
                'cliente_nombre' => ['Cliente', 'texto'], 'fecha_venta' => ['Fecha de venta', 'fecha'],
                'fecha_vencimiento' => ['Vencimiento', 'fecha'], 'pago_forma_nombre' => ['Forma de pago', 'texto'],
                'total' => ['Total', 'moneda'], 'importe_p' => ['Caja principal', 'moneda'],
                'importe_d' => ['Depósito', 'moneda'], 'importe_c' => ['Consorcio', 'moneda'],
                'acuenta' => ['Pago inicial', 'moneda'], 'abonos' => ['Abonos', 'moneda'],
                'saldo' => ['Saldo', 'moneda'], 'rentabilidad' => ['Rentabilidad', 'moneda'],
            ],
            'detalle' => [
                'unidad_codigo' => ['Unidad', 'texto'], 'cantidad' => ['Cantidad', 'cantidad'],
                'entregado' => ['Entregado', 'cantidad'], 'saldo' => ['Saldo por entregar', 'cantidad'],
                'precio_unitario' => ['Precio unitario', 'costo'], 'costo_unitario' => ['Costo unitario', 'costo'],
                'costo_total' => ['Costo total', 'moneda'], 'total' => ['Total de línea', 'moneda'],
                'rentabilidad' => ['Rentabilidad', 'moneda'],
            ],
        ],
        'prestamos' => [
            'referencia' => ['comprobante_tipo_nombre', 'serie', 'correlativo'],
            'cabecera' => [
                'movimiento_tipo' => ['Tipo', 'texto'], 'cliente_origen_nombre' => ['Origen', 'texto'],
                'cliente_destino_nombre' => ['Destino', 'texto'], 'fecha_prestamo' => ['Fecha', 'fecha'],
                'total' => ['Total', 'moneda'],
            ],
            'detalle' => [
                'unidad_nombre' => ['Unidad', 'texto'], 'cantidad' => ['Cantidad', 'cantidad'],
                'cantidad_kgm' => ['Cantidad kg', 'cantidad'], 'valor_unitario' => ['Valor unitario', 'costo'],
                'total' => ['Total de línea', 'moneda'],
            ],
        ],
        'preparadas' => [
            'referencia' => ['numero_interno'],
            'cabecera' => [
                'fecha' => ['Fecha', 'fecha'], 'producto_nombre' => ['Producto final', 'texto'],
                'cliente_nombre' => ['Cliente', 'texto'], 'costo_unitario' => ['Costo unitario', 'costo'],
                'ingreso_saco' => ['Ingreso sacos', 'cantidad'], 'ingreso_kg' => ['Ingreso kg', 'cantidad'],
                'ingreso_soles' => ['Costo total', 'moneda'],
            ],
            'detalle' => [
                'salida_saco' => ['Consumo sacos', 'cantidad'], 'salida_kg' => ['Consumo kg', 'cantidad'],
                'precio_unitario' => ['Costo unitario', 'costo'], 'salida_soles' => ['Costo de insumo', 'moneda'],
            ],
        ],
        'nucleo_preparadas' => [
            'referencia' => ['numero_interno'],
            'cabecera' => [
                'fecha' => ['Fecha', 'fecha'], 'nucleo_nombre' => ['Núcleo', 'texto'],
                'cantidad_porcentaje' => ['Porcentaje', 'cantidad'], 'costo_unitario' => ['Costo unitario', 'costo'],
                'ingreso_saco' => ['Ingreso sacos', 'cantidad'], 'ingreso_kg' => ['Ingreso kg', 'cantidad'],
                'ingreso_soles' => ['Costo total', 'moneda'],
            ],
            'detalle' => [
                'unidad_codigo' => ['Unidad', 'texto'], 'cantidad_porcentaje' => ['Porcentaje', 'cantidad'],
                'salida_kg' => ['Consumo', 'cantidad'], 'costo_unitario' => ['Costo unitario', 'costo'],
                'salida_soles' => ['Costo de insumo', 'moneda'],
            ],
        ],
        'venta_entregas' => [
            'referencia' => ['numero_recibo'],
            'cabecera' => [
                'numero_recibo' => ['Recibo', 'texto'], 'fecha_entrega' => ['Fecha de entrega', 'fecha'],
                'comentario' => ['Comentario', 'texto'],
            ],
            'detalle' => [
                'cantidad' => ['Cantidad', 'cantidad'], 'saco_entregado' => ['Sacos entregados', 'cantidad'],
                'salida_kg' => ['Kilos entregados', 'cantidad'],
            ],
        ],
    ];

    public function capturar(string $modulo, Model $registro): array
    {
        $config = $this->config($modulo);
        $relaciones = match ($modulo) {
            'cuadre_stocks' => ['detalles.producto'],
            'prestamos' => ['detalles', 'clienteOrigen', 'clienteDestino'],
            default => ['detalles'],
        };
        $registro->loadMissing($relaciones);

        $cabecera = [];
        foreach ($config['cabecera'] as $campo => [$etiqueta, $formato]) {
            $cabecera[$campo] = [
                'etiqueta' => $etiqueta,
                'formato' => $formato,
                'valor' => $this->valorCabecera($registro, $campo),
            ];
        }
        $cabecera['estado'] = [
            'etiqueta' => 'Estado',
            'formato' => 'texto',
            'valor' => $this->valor($registro->getAttribute('estado')),
        ];

        $ocurrencias = [];
        $detalles = $registro->getRelation('detalles')->map(function (Model $detalle) use ($config, &$ocurrencias): array {
            $productoId = (string) ($detalle->getAttribute('producto_id') ?? 'sin-producto');
            $unidad = (string) ($detalle->getAttribute('unidad_codigo') ?? $detalle->getAttribute('unidad_nombre') ?? '');
            $base = $productoId.'|'.$unidad;
            $ocurrencias[$base] = ($ocurrencias[$base] ?? 0) + 1;
            $producto = (string) ($detalle->getAttribute('producto_nombre') ?? $detalle->producto?->nombre ?? 'Producto '.$productoId);
            $valores = [];
            foreach ($config['detalle'] as $campo => [$etiqueta, $formato]) {
                $valores[$campo] = ['etiqueta' => $etiqueta, 'formato' => $formato, 'valor' => $this->valor($detalle->getAttribute($campo))];
            }

            return [
                'clave' => $base.'|'.$ocurrencias[$base],
                'producto' => $producto,
                'unidad' => $unidad,
                'ocurrencia' => $ocurrencias[$base],
                'valores' => $valores,
            ];
        })->values()->all();

        return ['cabecera' => $cabecera, 'detalles' => $detalles];
    }

    public function comparar(array $anteriores, array $nuevos): array
    {
        $cabecera = [];
        foreach ($anteriores['cabecera'] as $campo => $anterior) {
            $nuevo = $nuevos['cabecera'][$campo] ?? $anterior;
            if ($this->iguales($anterior['valor'], $nuevo['valor'])) {
                continue;
            }
            $cabecera[] = $this->cambio($anterior['etiqueta'], $anterior['valor'], $nuevo['valor'], 'modificado', $anterior['formato']);
        }

        $viejos = collect($anteriores['detalles'])->keyBy('clave');
        $actuales = collect($nuevos['detalles'])->keyBy('clave');
        $detalles = [];

        foreach ($viejos->keys()->merge($actuales->keys())->unique() as $clave) {
            $anterior = $viejos->get($clave);
            $nuevo = $actuales->get($clave);
            if (! $anterior || ! $nuevo) {
                $linea = $anterior ?? $nuevo;
                $detalles[] = $this->cambio(
                    'Detalle: '.$linea['producto'],
                    $anterior ? $this->resumenDetalle($anterior) : null,
                    $nuevo ? $this->resumenDetalle($nuevo) : null,
                    $anterior ? 'eliminado' : 'agregado',
                    'texto'
                );

                continue;
            }

            foreach ($anterior['valores'] as $campo => $valorAnterior) {
                $valorNuevo = $nuevo['valores'][$campo] ?? $valorAnterior;
                if (! $this->iguales($valorAnterior['valor'], $valorNuevo['valor'])) {
                    $detalles[] = $this->cambio(
                        $anterior['producto'].' — '.$valorAnterior['etiqueta'],
                        $valorAnterior['valor'],
                        $valorNuevo['valor'],
                        'modificado',
                        $valorAnterior['formato']
                    );
                }
            }
        }

        return ['cabecera' => $cabecera, 'detalles' => $detalles];
    }

    private function cambio(string $campo, mixed $anterior, mixed $nuevo, string $tipo, string $formato): array
    {
        return compact('campo', 'anterior', 'nuevo', 'tipo', 'formato');
    }

    private function resumenDetalle(array $detalle): string
    {
        return collect($detalle['valores'])
            ->map(fn (array $valor): string => $valor['etiqueta'].': '.($valor['valor'] ?? '—'))
            ->implode(' · ');
    }

    public function referencia(string $modulo, Model $registro): string
    {
        $partes = collect($this->config($modulo)['referencia'])
            ->map(fn (string $campo): mixed => $this->valor($registro->getAttribute($campo)))
            ->filter(fn (mixed $valor): bool => filled($valor));

        return mb_substr($partes->implode(' - ') ?: $modulo.' #'.$registro->getKey(), 0, 150);
    }

    private function config(string $modulo): array
    {
        if (! isset(self::CONFIG[$modulo])) {
            throw new InvalidArgumentException("Módulo de rectificación no permitido: {$modulo}");
        }

        return self::CONFIG[$modulo];
    }

    private function valor(mixed $valor): mixed
    {
        if ($valor instanceof Carbon || $valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        return is_string($valor) ? trim($valor) : $valor;
    }

    private function valorCabecera(Model $registro, string $campo): mixed
    {
        return match ($campo) {
            'cliente_origen_nombre' => $this->valor($registro->clienteOrigen?->razon_social ?? $registro->clienteOrigen?->nombre),
            'cliente_destino_nombre' => $this->valor($registro->clienteDestino?->razon_social ?? $registro->clienteDestino?->nombre),
            default => $this->valor($registro->getAttribute($campo)),
        };
    }

    private function iguales(mixed $anterior, mixed $nuevo): bool
    {
        if (is_numeric($anterior) && is_numeric($nuevo)) {
            return abs((float) $anterior - (float) $nuevo) < 0.00001;
        }

        return $anterior === $nuevo;
    }
}
