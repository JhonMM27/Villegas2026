<div class="table-responsive">
    <table class="table table-sm table-striped table-bordered table-hover mb-0" id="tablaMovimientos" style="font-size: 0.85rem;">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Producto</th>
                <th>Empaque</th>
                <th>Cant.</th>
                <th>Cant. kg</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Costo Unit.</th>
                <th>Costo Total</th>
                <th>Stock Nuevo</th>
                <th>Costo Nuevo</th>
                <th>Comentario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportes as $r)
                <tr>
                    <td>{{ $r->fecha->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="badge {{ strpos($r->tipo, 'ANULACION') !== false ? 'bg-danger' : (strpos($r->tipo, 'INGRESO') !== false || $r->tipo === 'COMPRA' ? 'bg-success' : 'bg-primary') }}">
                            {{ $r->tipo }}
                        </span>
                    </td>
                    <td>{{ $r->producto_nombre }}</td>
                    <td class="text-end">{{ number_format($r->empaque, 2) }}</td>
                    <td class="text-end">{{ number_format($r->cantidad, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($r->cantidad_kg, 2) }}</td>
                    <td class="text-end text-success">{{ number_format($r->entrada, 2) }}</td>
                    <td class="text-end text-danger">{{ number_format($r->salida, 2) }}</td>
                    <td class="text-end">{{ number_format($r->costo_unitario, 4) }}</td>
                    <td class="text-end">{{ number_format($r->costo_total, 4) }}</td>
                    <td class="text-end fw-bold">{{ number_format($r->stock_nuevo, 2) }}</td>
                    <td class="text-end text-primary fw-bold">{{ number_format($r->costo_nuevo, 4) }}</td>
                    <td><small>{{ $r->comentario }}</small></td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center">No se encontraron movimientos en el rango seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
