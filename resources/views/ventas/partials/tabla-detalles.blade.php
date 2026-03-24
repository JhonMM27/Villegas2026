<div class="table-responsive" style="transform: scale(0.97); transform-origin: top center;">
    <table class="table table-bordered table-sm table-app" id="tablaDetalles">
        <thead class="table-light text-center">
            <tr>
                <th>Acción</th>
                <th>#</th>
                <th>Producto</th>
                <th>Costo</th>
                <th>Stock actual</th>
                <th>Unidad Fracción</th>
                <th>Empaque</th>
                <th>Cantidad</th>
                <th>Cantidad kg.</th>
                <th>Precio Unitario</th>
                <th>Entrega</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
        <tfoot>
            <tr>
                <td colspan="11" class="text-end"><strong>OP. Gravada</strong></td>
                <td>
                    <input type="text" readonly class="form-control form-control-sm text-end" id="op_gravada"
                        name="op_gravada" value="0.00">
                </td>
            </tr>
            <tr>
                <td colspan="11" class="text-end"><strong>OP. Exonerada</strong></td>
                <td>
                    <input type="text" readonly class="form-control form-control-sm text-end" id="op_exonerada"
                        name="op_exonerada" value="0.00">
                </td>
            </tr>
            <tr>
                <td colspan="11" class="text-end"><strong>OP. Inafecta</strong></td>
                <td>
                    <input type="text" readonly class="form-control form-control-sm text-end" id="op_inafecta"
                        name="op_inafecta" value="0.00">
                </td>
            </tr>
            <tr>
                <td colspan="11" class="text-end"><strong>Impuesto</strong></td>
                <td>
                    <input type="text" readonly class="form-control form-control-sm text-end" id="impuesto"
                        name="impuesto" value="0.00">
                </td>
            </tr>
            <tr>
                <td colspan="11" class="text-end"><strong>Total</strong></td>
                <td>
                    <input type="text" readonly class="form-control form-control-sm text-end" id="total" name="total"
                        value="0.00" data-error-field="detalles">
                        <div class="invalid-feedback"></div>
                </td>
            </tr>
        </tfoot>
    </table>
</div>