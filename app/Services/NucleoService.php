<?php

/**
 * Servicio de Núcleos.
 *
 * Concentra toda la lógica de negocio asociada a la creación
 * de núcleos (recetas):
 * - Cálculo de porcentajes y totales
 * - Registro de movimientos de SALIDA (insumos) e INGRESO (producto final)
 *   en el kardex valorizado mediante MovimientoService
 *
 * NOTA: Los métodos updateNucleo() y deleteNucleo() han sido COMENTADOS
 * porque ahora el sistema usa kardex valorizado.
 */

namespace App\Services;

use App\Models\Nucleo;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class NucleoService
{
    /**
     * Inyección del servicio de movimientos para registrar
     * salidas e ingresos en el kardex valorizado.
     */
    public function __construct(
        protected MovimientoService $movimientoService
    ) {}

    /**
     * Crea un núcleo (receta) dentro de una transacción:
     * 1. Procesa cabecera y detalles calculados
     * 2. Crea el registro Nucleo + NucleoDetalles
     *
     * @param  array  $data  Datos validados del request
     * @return Nucleo El núcleo recién creado (con detalles cargados)
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function createNucleo(array $data): Nucleo
    {
        return DB::transaction(function () use ($data) {

            // 1) Procesar datos (cabecera + detalles calculados)
            $nucleoData = $this->processNucleoData($data, true);

            // 2) Persistir núcleo y detalles
            $nucleo = Nucleo::create($nucleoData['nucleo']);
            $nucleo->detalles()->createMany($nucleoData['detalles']);
            $nucleo->load('detalles');

            return $nucleo;
        });
    }

    /**
     * Anula un núcleo existente dentro de una transacción:
     * 1. Valida que no esté ya anulado
     * 2. Cambia el estado a 'anulado'
     *
     * @param  int  $id  ID del núcleo a anular
     * @return Nucleo El núcleo anulado
     *
     * @throws \Exception Si el núcleo ya está anulado o si ocurre error
     */
    public function anularNucleo(int $id): Nucleo
    {
        return DB::transaction(function () use ($id) {
            $nucleo = Nucleo::findOrFail($id);

            // Validar estado
            if ($nucleo->estado === 'anulado') {
                throw new \Exception('Este núcleo ya fue anulado.');
            }

            // Cambiar estado a 'anulado'
            $nucleo->update(['estado' => 'anulado']);

            return $nucleo;
        });
    }

    /**
     * Rectifica un núcleo previamente anulado (Actualización in-situ).
     * Modifica el registro existente en lugar de crear uno nuevo.
     *
     * @param  int  $nucleoId  ID del núcleo anulado
     * @param  array  $data  Datos validados del request
     */
    public function rectificarNucleo(int $nucleoId, array $data): array
    {
        return DB::transaction(function () use ($nucleoId, $data) {
            $nucleo = Nucleo::findOrFail($nucleoId);

            if ($nucleo->estado !== 'anulado') {
                throw new \Exception('Solo se pueden rectificar núcleos en estado anulado.');
            }

            // 1) Procesar datos (cabecera + detalles calculados)
            $nucleoDataRaw = $this->processNucleoData($data, false);
            $nucleoData = $nucleoDataRaw['nucleo'];

            // Forzamos estado y nota
            $nucleoData['estado'] = 'activo';
            $nucleoData['nota'] = trim(($data['nota'] ?? $nucleo->nota ?? '').' | Rectificado el '.now()->format('d/m/Y H:i'));

            // 2) Actualizar la cabecera del registro existente
            $nucleo->update($nucleoData);

            // 3) Reemplazar detalles
            $nucleo->detalles()->delete();
            $detallesNuevos = $nucleo->detalles()->createMany($nucleoDataRaw['detalles']);
            $nucleo->load('detalles');

            return [
                'nucleo' => $nucleo,
                'detalles' => $detallesNuevos,
            ];
        });
    }

    /**
     * Procesa y construye los datos del núcleo (cabecera + detalles calculados).
     *
     * @param  array  $data  Datos validados del request
     * @param  bool  $isNew  true=nuevo registro, false=edición
     * @return array ['nucleo' => [...], 'detalles' => [...]]
     */
    private function processNucleoData(array $data, bool $isNew = true): array
    {
        // Obtener producto núcleo
        $productoNucleo = Producto::find($data['producto_id_nucleo']);

        // Cargar productos involucrados en los detalles
        $productos = Producto::whereIn('id', collect($data['detalles'])->pluck('producto_id'))
            ->get()
            ->keyBy('id');

        // Calcular detalles
        $detallesCalculados = [];
        $cantidadTotal = 0;

        foreach ($data['detalles'] as $detalle) {
            $producto = $productos[$detalle['producto_id']];
            $detallesCalculados[] = $this->calculateDetail(
                $producto,
                $detalle['unidad_codigo'],
                $detalle['cantidad']
            );

            $cantidadTotal += (float) $detalle['cantidad'];
        }

        // Construir array de la cabecera
        $nucleoData = [
            'id' => $productoNucleo->id,
            'nombre' => $productoNucleo->nombre ?? '',
            'unidad_codigo' => $productoNucleo->unidad_codigo ?? '',
            'unidad_nombre' => $productoNucleo->unidad->descripcion ?? '',
            'empaque' => $productoNucleo->empaque ?? 0,
            'cantidad_porcentaje' => $cantidadTotal,
            'items' => count($detallesCalculados),
            'estado' => 'activo',
        ];

        // Campo exclusivo de creación
        if ($isNew) {
            $nucleoData['activo'] = true;
        }

        return [
            'nucleo' => $nucleoData,
            'detalles' => $detallesCalculados,
        ];
    }

    /**
     * Calcula los valores de un detalle de núcleo individual.
     *
     * @param  Producto  $producto  Producto componente
     * @param  string  $unidad_codigo  Código de unidad
     * @param  float  $cantidad  Cantidad
     * @return array Detalle listo para createMany()
     */
    private function calculateDetail($producto, $unidad_codigo, $cantidad): array
    {
        return [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'unidad_codigo' => $unidad_codigo,
            'cantidad' => $cantidad,
        ];
    }
}
