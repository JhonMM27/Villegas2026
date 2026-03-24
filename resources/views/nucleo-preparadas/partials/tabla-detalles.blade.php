<div class="col-lg-6">                    
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-app" style="font-size: 0.8rem;" id="tablaNucleos">
            <thead class="table-light text-center">
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Empaque</th>
                    <th>Unidad</th>
                    <th>Costo Producto</th>
                    <th>Cantidad</th>
                    <th>Costo Unitario</th>
                    <th>% Proporción</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="text-end"><strong>Total Kg.</strong></td>
                    <td>
                        <span id="total_salida" class="form-control form-control-sm text-end">0.00</span>
                    </td>
                </tr>
                <!--
                <tr>
                    <td colspan="7" class="text-end"><strong>Total Valorizado</strong></td>
                    <td>
                        <input id="total_costo_unitario" name="costo_unitario" class="form-control form-control-sm text-end" value="0.00" />
                    </td>
                </tr>
                -->
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
                    <th>Unidad</th>
                    <th>Cant. Porc.</th>
                    <th>Total Kg</th>
                    <th>Total Soles</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end"><strong>Kilos por Saco</strong></td>
                    <td>
                        <input type="text" readonly class="form-control form-control-sm text-end" id="kilos_sacof"
                            name="kilos_saco" value="0.00">
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end"><strong>Costo por Saco</strong></td>
                    <td>
                        <input type="text" readonly class="form-control form-control-sm text-end" id="costo_sacof"
                            name="costo_saco" value="0.00">
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end"><strong>Cantidad de Sacos</strong></td>
                    <td>
                        <input type="text" readonly class="form-control form-control-sm text-end" id="total_sacof"
                            name="ingreso_saco" value="0.00">
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end"><strong>Costo Total</strong></td>
                    <td>
                        <input type="text" readonly class="form-control form-control-sm text-end" id="preparada_total"
                            name="ingreso_soles" value="0.00">
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>