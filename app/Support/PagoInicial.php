<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class PagoInicial
{
    /**
     * Obtiene la distribución normalizada y su total autoritativo.
     *
     * @return array{principal: float, deposito: float, consorcio: float, total: float}
     */
    public static function calcular(array $data): array
    {
        $principal = round((float) ($data['principal'] ?? 0), 2);
        $deposito = round((float) ($data['deposito'] ?? 0), 2);
        $consorcio = round((float) ($data['consorcio'] ?? 0), 2);

        return [
            'principal' => $principal,
            'deposito' => $deposito,
            'consorcio' => $consorcio,
            'total' => round($principal + $deposito + $consorcio, 2),
        ];
    }

    /**
     * Valida la distribución y reemplaza total_cobranza por el total calculado.
     */
    public static function normalizarYValidar(array $data, string $documento): array
    {
        $distribucion = self::calcular($data);
        $totalDocumento = round((float) $data['total'], 2);

        if ($distribucion['total'] > $totalDocumento) {
            throw ValidationException::withMessages([
                'total_cobranza' => "El pago inicial no puede ser mayor al total de la {$documento}.",
            ]);
        }

        if ((string) $data['pago_forma_codigo'] === '1'
            && abs($distribucion['total'] - $totalDocumento) > 0.009) {
            throw ValidationException::withMessages([
                'total_cobranza' => "Una {$documento} al contado debe quedar pagada completamente.",
            ]);
        }

        $data['principal'] = $distribucion['principal'];
        $data['deposito'] = $distribucion['deposito'];
        $data['consorcio'] = $distribucion['consorcio'];
        $data['total_cobranza'] = $distribucion['total'];

        return $data;
    }
}
