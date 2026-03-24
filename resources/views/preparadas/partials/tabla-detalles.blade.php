<div class="col-lg-6">                    
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-app" style="font-size: 0.8rem;" id="tablaFormulaciones">
            <thead class="table-light text-center">
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Empaque</th>
                    <th>Costo producto</th>
                    <th>Kg.</th>
                    <th>Línea</th>
                    <th>% Proporción</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end"><strong>Total Kg.</strong></td>
                    <td>
                        <span id="total_salida_kg" class="form-control form-control-sm text-end">0.00</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<div class="col-lg-6">                    
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-app" style="font-size: 0.8rem;" id="tablaDetalles">
            <thead class="table-light text-center">
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Empaque</th>
                    <th>Costo</th>
                    <th>Total Saco</th>
                    <th>Total Kg</th>
                    <th>Total Soles</th>
                    <th>Línea</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="text-end"><strong>Kilos por Saco</strong></td>
                    <td>
                        <input type="text" readonly class="form-control form-control-sm text-end" id="kilos_saco"
                             value="0.00">
                    </td>
                </tr>
                <tr>
                    <td colspan="7" class="text-end"><strong>Costo por Saco</strong></td>
                    <td>
                        <input type="text" readonly class="form-control form-control-sm text-end" id="costo_saco"
                             value="0.00">
                    </td>
                </tr>
                <tr>
                    <td colspan="7" class="text-end"><strong>Cantidad de Sacos</strong></td>
                    <td>
                        <input type="text" readonly class="form-control form-control-sm text-end" id="total_salida_saco"
                            name="salida_saco" value="0.00">
                    </td>
                </tr>
                <tr>
                    <td colspan="7" class="text-end"><strong>Costo total</strong></td>
                    <td>
                        <input type="text" readonly class="form-control form-control-sm text-end" id="costo_total"
                            value="0.00">

                        <input type="hidden" readonly class="form-control form-control-sm text-end" id="total_ingreso_saco"
                            name="ingreso_saco" value="0.00">
                        <input type="hidden" readonly class="form-control form-control-sm text-end" id="total_ingreso_kg"
                            name="ingreso_kg" value="0.00">
                        <input type="hidden" readonly class="form-control form-control-sm text-end" id="total_ingreso_soles"
                            name="ingreso_soles" value="0.00">
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>