<?php

namespace App\Services;

use App\Models\FormulaAlimentoCerdo;
use Illuminate\Support\Facades\DB;

class FormulaAlimentoCerdoService
{
    public function calcular(FormulaAlimentoCerdo $formula): array
    {
        $formula->loadMissing('detalles.ingrediente');

        $totalKg = 0;
        $costoTonelada = 0;

        $nutrientes = [
            'materia_seca' => 0,
            'proteina_cruda' => 0,
            'em' => 0,
            'fdn' => 0,
            'fibra' => 0,
            'fda' => 0,
            'grasa' => 0,
            'calcio' => 0,
            'fosforo' => 0,
            'magnesio' => 0,
            'almidon' => 0,
            'azucar' => 0,
            'ceniza' => 0,
            'lactosa' => 0,
            'lisina' => 0,
            'metionina' => 0,
            'treonina' => 0,
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

            foreach (array_keys($nutrientes) as $campo) {
                $nutrientes[$campo] += ($cantidadKg * (float) ($ing->{$campo} ?? 0)) / 1000;
            }

            $ingredientes[] = [
                'id' => $ing->id,
                'ingrediente' => $ing->ingrediente,
                'procedencia' => $ing->procedencia,
                'clasificacion' => $ing->clasificacion,
                'nutriente' => $ing->nutriente,
                'aporte' => (float) $ing->aporte,
                'precio_kg' => round($precioKg, 4),
                'cantidad_kg' => round($cantidadKg, 4),
                'costo' => round($costo, 4),
                'materia_seca' => (float) $ing->materia_seca,
                'proteina_cruda' => (float) $ing->proteina_cruda,
                'em' => (float) $ing->em,
                'fdn' => (float) $ing->fdn,
                'fibra' => (float) $ing->fibra,
                'fda' => (float) $ing->fda,
                'grasa' => (float) $ing->grasa,
                'calcio' => (float) $ing->calcio,
                'fosforo' => (float) $ing->fosforo,
                'magnesio' => (float) $ing->magnesio,
                'almidon' => (float) $ing->almidon,
                'azucar' => (float) $ing->azucar,
                'ceniza' => (float) $ing->ceniza,
                'lactosa' => (float) $ing->lactosa,
                'lisina' => (float) $ing->lisina,
                'metionina' => (float) $ing->metionina,
                'treonina' => (float) $ing->treonina,
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

    public function getFormulaData(FormulaAlimentoCerdo $formula): array
    {
        return $this->calcular($formula);
    }

    public function create(array $data): FormulaAlimentoCerdo
    {
        return DB::transaction(function () use ($data) {
            if (($data['kg_saco'] ?? 0) <= 0) {
                throw new \Exception('El campo Kg/Saco debe ser mayor a 0.');
            }
            $data['activo'] = $this->normalizarActivo($data['activo'] ?? null);

            return FormulaAlimentoCerdo::create($data);
        });
    }

    public function update(FormulaAlimentoCerdo $formula, array $data): FormulaAlimentoCerdo
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

    public function createWithDetalles(array $data, array $detalles): FormulaAlimentoCerdo
    {
        return DB::transaction(function () use ($data, $detalles) {
            if (($data['kg_saco'] ?? 0) <= 0) {
                throw new \Exception('El campo Kg/Saco debe ser mayor a 0.');
            }
            $data['activo'] = $this->normalizarActivo($data['activo'] ?? null);

            $formula = FormulaAlimentoCerdo::create($data);

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

    public function updateWithDetalles(FormulaAlimentoCerdo $formula, array $data, array $detalles): FormulaAlimentoCerdo
    {
        return DB::transaction(function () use ($formula, $data, $detalles) {
            if (($data['kg_saco'] ?? 0) <= 0) {
                throw new \Exception('El campo Kg/Saco debe ser mayor a 0.');
            }
            $data['activo'] = $this->normalizarActivo($data['activo'] ?? $formula->activo);

            $formula->update($data);

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

            if (! empty($ingredienteIds)) {
                $formula->detalles()->whereNotIn('ingrediente_id', $ingredienteIds)->delete();
            } else {
                $formula->detalles()->delete();
            }

            return $formula->fresh()->load('detalles.ingrediente');
        });
    }

    public function delete(FormulaAlimentoCerdo $formula): bool
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
