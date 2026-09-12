<?php

declare(strict_types=1);

namespace Tests\Support;

class TicketFixtures
{
    public static function data(int $count = 1): array
    {
        $person = (object) ['nombre' => 'José Muñoz', 'razon_social' => 'CLIENTE DE PRUEBA', 'documento_numero' => '12345678', 'direccion' => null, 'telefono' => null, 'dni' => '12345678', 'documentoTipo' => (object) ['descripcion' => 'DNI']];
        $detail = (object) [
            'producto_id' => 1, 'producto_nombre' => 'POLVILLO', 'producto_empaque' => 40,
            'producto_linea' => 'ALIMENTOS', 'unidad_codigo' => 'SCO', 'unidad_nombre' => 'SACO',
            'cantidad' => 8, 'cantidad_kgm' => 320, 'salida_saco' => 8, 'salida_kg' => 320,
            'precio_unitario' => 38, 'costo_unitario' => 38, 'valor_unitario' => 38, 'total' => 304, 'saldo' => 6,
        ];
        $details = collect(range(1, $count))->map(function ($i) use ($detail) {
            $item = clone $detail;
            $item->producto_nombre = $i === 1 ? 'POLVILLO' : "PRODUCTO {$i} CON NOMBRE LARGO ÁCIDO Y MAÍZ PARA PREPARACIÓN";
            $item->ventaDetalle = clone $detail;

            return $item;
        });
        $record = (object) [
            'id' => 1, 'serie' => '01', 'correlativo' => 128601, 'numero_interno' => 123,
            'numero_recibo' => 'PRUEBA-001', 'nombre' => 'NÚCLEO DE PRUEBA',
            'comprobante_tipo_codigo' => 'NP', 'comprobante_tipo_nombre' => 'NOTA PEDIDO',
            'cliente_id' => 1, 'cliente_nombre' => 'CLIENTE DE PRUEBA', 'proveedor_nombre' => 'PROVEEDOR DE PRUEBA',
            'cliente_documento' => '12345678', 'proveedor_documento' => '12345678',
            'cliente_direccion' => null, 'proveedor_direccion' => null,
            'cliente' => $person, 'proveedor' => $person, 'clienteOrigen' => $person, 'clienteDestino' => $person,
            'empleado' => $person, 'fecha' => '2026-09-03 14:45:00', 'fecha_venta' => '2026-09-03 14:45:00',
            'fecha_compra' => '2026-09-03', 'fecha_cotizacion' => '2026-09-03', 'fecha_prestamo' => '2026-09-03',
            'fecha_provisional' => '2026-09-03', 'fecha_gasto' => '2026-09-03', 'fecha_costo' => '2026-09-03',
            'fecha_pago' => '2026-09-03', 'pago_forma_nombre' => 'Contado', 'user_nombre' => 'PRUEBA',
            'detalles' => $details, 'total' => 304 * $count, 'monto' => 304, 'monto_original' => 1000,
            'monto_pagado' => 16, 'saldo_pendiente' => 984, 'importe_p' => 0, 'importe_d' => 0, 'importe_c' => 304,
            'impuesto' => 0, 'op_gravada' => 0, 'op_exonerada' => 304,
            'producto_nombre' => 'PREPARADA DE PRUEBA', 'nucleo_nombre' => 'NÚCLEO DE PRUEBA',
            'producto_empaque' => 40, 'salida_kg' => 320, 'ingreso_kg' => 320, 'ingreso_saco' => 8, 'ingreso_soles' => 304,
            'categoriaGasto' => null, 'categoriaCosto' => null, 'gastoTipo' => null, 'costoTipo' => null,
            'responsable' => 'José Muñoz', 'responsable_dni' => null, 'descripcion' => 'Descripción con tildes y ñ',
            'observaciones' => null, 'estado' => 'pendiente', 'pagos' => collect(),
        ];
        $payment = clone $record;
        $payment->prestamo = $record;
        $record->pagos = collect([$payment]);
        $data = array_fill_keys(['venta', 'cotizacion', 'compra', 'preparada', 'nucleoPreparada', 'nucleo', 'formulacion', 'prestamo', 'provisional', 'entrega', 'gasto', 'costo', 'adelanto'], $record);
        $data['pago'] = $payment;
        $data['empresa'] = (object) ['razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.', 'ruc' => '20538937321', 'direccion' => 'DIRECCIÓN DE PRUEBA', 'celular' => '967984895'];
        $data['total_letras'] = 'TRESCIENTOS CUATRO CON 00/100 SOLES';
        $data['saldoAnterior'] = 16;
        $data['totalDeuda'] = 1000;
        $data['clienteNombre'] = 'CLIENTE DE PRUEBA';
        $data['proveedorNombre'] = 'PROVEEDOR DE PRUEBA';
        $data['reportes'] = collect([(object) ['documento' => 'NP 01-128601', 'fecha_venta' => '2026-09-03', 'fecha_compra' => '2026-09-03', 'saldo' => 16]]);

        return $data;
    }

    public static function views(): array
    {
        return ['ventas.ticket', 'cotizaciones.ticket', 'compras.ticket', 'preparadas.ticket', 'nucleo-preparadas.ticket', 'nucleos.ticket', 'formulaciones.ticket', 'prestamos.ticket', 'venta-entregas.ticket', 'venta-provisionales.ticket', 'compra-provisionales.ticket', 'gastos.ticket', 'costos.ticket', 'planilla.adelantos.ticket', 'planilla.prestamos.ticket', 'planilla.prestamos.pago_ticket', 'cuenta-cliente.reportes.saldos', 'cuenta-proveedor.reportes.saldos'];
    }
}
