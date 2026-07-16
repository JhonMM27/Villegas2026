<?php

namespace App\Services;

use App\Models\FormulaAlimento;
use Illuminate\Support\Facades\DB;

class FormulaAlimentoService
{
    public function calcular(FormulaAlimento $formula): array
    {
        $formula->loadMissing('detalles.ingrediente');

        $totalKg = 0;
        $costoTonelada = 0;

        $nutrientes = [
            'materia_seca' => 0,
            'proteina_cruda' => 0,
            'enl' => 0,
            'fdn' => 0,
            'grasa' => 0,
            'almidon' => 0,
            'azucar' => 0,
        ];

        $ingredientes = [];

        foreach ($formula->detalles as $detalle) {
            $ing = $detalle->ingrediente;
            if (! $ing) {
                continue;
            }

            $cantidadKg = (float) $detalle->cantidad_kg;
            $precioKg = (float) $detalle->precio_kg;

            if ($cantidadKg <= 0) {
                continue;
            }

            $costo = $cantidadKg * $precioKg;
            $totalKg += $cantidadKg;
            $costoTonelada += $costo;

            $nutrientes['materia_seca'] += ($cantidadKg * (float) $ing->materia_seca) / 1000;
            $nutrientes['proteina_cruda'] += ($cantidadKg * (float) $ing->proteina_cruda) / 1000;
            $nutrientes['enl'] += ($cantidadKg * (float) $ing->enl) / 1000;
            $nutrientes['fdn'] += ($cantidadKg * (float) $ing->fdn) / 1000;
            $nutrientes['grasa'] += ($cantidadKg * (float) $ing->grasa) / 1000;
            $nutrientes['almidon'] += ($cantidadKg * (float) $ing->almidon) / 1000;
            $nutrientes['azucar'] += ($cantidadKg * (float) $ing->azucar) / 1000;

            $ingredientes[] = [
                'id' => $ing->id,
                'ingrediente' => $ing->ingrediente,
                'procedencia' => $ing->procedencia,
                'clasificacion' => $ing->clasificacion,
                'nutriente' => $ing->nutriente,
                'aporte' => $ing->aporte,
                'precio_kg' => round($precioKg, 4),
                'cantidad_kg' => round($cantidadKg, 4),
                'costo' => round($costo, 4),
                'materia_seca' => (float) $ing->materia_seca,
                'proteina_cruda' => (float) $ing->proteina_cruda,
                'enl' => (float) $ing->enl,
                'fdn' => (float) $ing->fdn,
                'grasa' => (float) $ing->grasa,
                'almidon' => (float) $ing->almidon,
                'azucar' => (float) $ing->azucar,
            ];
        }

        $costoKg = $costoTonelada / 1000;

        $kgSaco = (float) $formula->kg_saco;
        if ($kgSaco <= 0) {
            $costoSaco = (float) $formula->saco_vacio
                        + (float) $formula->mano_obra
                        + (float) $formula->energia
                        + (float) $formula->merma;
        } else {
            $costoSaco = ($costoKg * $kgSaco)
                        + (float) $formula->saco_vacio
                        + (float) $formula->mano_obra
                        + (float) $formula->energia
                        + (float) $formula->merma;
        }

        $gananciaSaco = (float) $formula->precio_venta - $costoSaco;
        $margen = (float) $formula->precio_venta > 0
            ? ($gananciaSaco / (float) $formula->precio_venta) * 100
            : 0;

        return [
            'formula' => [
                'id' => $formula->id,
                'nombre' => $formula->nombre,
                'descripcion' => $formula->descripcion,
                'fecha' => $formula->fecha?->format('Y-m-d'),
                'activo' => (bool) $formula->activo,
                'kg_saco' => (float) $formula->kg_saco,
                'saco_vacio' => (float) $formula->saco_vacio,
                'mano_obra' => (float) $formula->mano_obra,
                'energia' => (float) $formula->energia,
                'merma' => (float) $formula->merma,
                'precio_venta' => (float) $formula->precio_venta,
            ],
            'ingredientes' => $ingredientes,
            'resumen_costos' => [
                'total_kg' => round($totalKg, 4),
                'costo_tonelada' => round($costoTonelada, 4),
                'costo_kg' => round($costoKg, 4),
                'costo_saco' => round($costoSaco, 4),
                'precio_venta' => (float) $formula->precio_venta,
                'ganancia_saco' => round($gananciaSaco, 4),
                'margen_porcentaje' => round($margen, 4),
            ],
            'resumen_nutricional' => array_map(fn ($v) => round($v, 4), $nutrientes),
        ];
    }

    public function getFormulaData(FormulaAlimento $formula): array
    {
        return $this->calcular($formula);
    }

    public function create(array $data): FormulaAlimento
    {
        return DB::transaction(function () use ($data) {
            if (($data['kg_saco'] ?? 0) <= 0) {
                throw new \Exception('El campo Kg/Saco debe ser mayor a 0.');
            }
            $data['activo'] = $this->normalizarActivo($data['activo'] ?? null);

            return FormulaAlimento::create($data);
        });
    }

    public function update(FormulaAlimento $formula, array $data): FormulaAlimento
    {
        return DB::transaction(function () use ($formula, $data) {
            if (($data['kg_saco'] ?? 0) <= 0) {
                throw new \Exception('El campo Kg/Saco debe ser mayor a 0.');
            }
            $data['activo'] = $this->normalizarActivo($data['activo'] ?? $formula->activo);

            $formula->update($data);

            return $formula->fresh();
        });
    }

    /**
     * Crea una fórmula junto con sus detalles (ingredientes) en una sola transacción.
     * Cada detalle debe tener: ingrediente_id, cantidad_kg, precio_kg
     */
    public function createWithDetalles(array $data, array $detalles): FormulaAlimento
    {
        return DB::transaction(function () use ($data, $detalles) {
            if (($data['kg_saco'] ?? 0) <= 0) {
                throw new \Exception('El campo Kg/Saco debe ser mayor a 0.');
            }
            $data['activo'] = $this->normalizarActivo($data['activo'] ?? null);

            $formula = FormulaAlimento::create($data);

            // Crear los detalles (ingredientes de la fórmula)
            foreach ($detalles as $detalle) {
                if (empty($detalle['ingrediente_id']) || empty($detalle['cantidad_kg'])) {
                    continue;
                }
                $formula->detalles()->updateOrCreate(
                    ['ingrediente_id' => $detalle['ingrediente_id']],
                    [
                        'cantidad_kg' => $detalle['cantidad_kg'],
                        'precio_kg' => $detalle['precio_kg'] ?? 0,
                    ]
                );
            }

            return $formula->load('detalles.ingrediente');
        });
    }

    /**
     * Actualiza una fórmula y sincroniza sus detalles (ingredientes).
     * - Los detalles enviados se actualizan/crean (upsert por ingrediente_id)
     * - Los detalles que ya no están en el array se eliminan
     */
    public function updateWithDetalles(FormulaAlimento $formula, array $data, array $detalles): FormulaAlimento
    {
        return DB::transaction(function () use ($formula, $data, $detalles) {
            if (($data['kg_saco'] ?? 0) <= 0) {
                throw new \Exception('El campo Kg/Saco debe ser mayor a 0.');
            }
            $data['activo'] = $this->normalizarActivo($data['activo'] ?? $formula->activo);

            $formula->update($data);

            // IDs de ingredientes que vienen en el request
            $ingredienteIds = [];

            foreach ($detalles as $detalle) {
                if (empty($detalle['ingrediente_id']) || empty($detalle['cantidad_kg'])) {
                    continue;
                }
                $ingredienteIds[] = $detalle['ingrediente_id'];

                $formula->detalles()->updateOrCreate(
                    ['ingrediente_id' => $detalle['ingrediente_id']],
                    [
                        'cantidad_kg' => $detalle['cantidad_kg'],
                        'precio_kg' => $detalle['precio_kg'] ?? 0,
                    ]
                );
            }

            // Eliminar detalles que ya no están en la lista
            if (! empty($ingredienteIds)) {
                $formula->detalles()->whereNotIn('ingrediente_id', $ingredienteIds)->delete();
            } else {
                $formula->detalles()->delete();
            }

            return $formula->fresh()->load('detalles.ingrediente');
        });
    }

    public function delete(FormulaAlimento $formula): bool
    {
        return DB::transaction(function () use ($formula) {
            $formula->detalles()->delete();

            return $formula->delete();
        });
    }

    private function normalizarActivo(mixed $valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }
        if (is_string($valor)) {
            return in_array(strtolower($valor), ['1', 'true', 'on', 'yes'], true);
        }
        if (is_numeric($valor)) {
            return (int) $valor === 1;
        }

        return false;
    }
}
