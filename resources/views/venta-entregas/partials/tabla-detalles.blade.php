<div class="table-responsive" style="transform: scale(0.97); transform-origin: top center;">
    <table class="table table-bordered table-sm table-app" id="tablaDetalles">
        <thead class="table-light text-center">
            <tr>
                <th style="width:40px;">#</th>
                <th>Producto</th>
                <th style="width:120px;">Unidad Fracción</th>
                <th style="width:90px;">Empaque</th>
                <th style="width:90px;">Cantidad</th>
                <th style="width:120px;">Precio Unitario</th>
                <th style="width:90px;">Entregado</th>
                <th style="width:90px;">Pendiente</th>
                <th style="width:130px;">Entrega</th>
            </tr>
        </thead>
        <tbody id="tablaDetallesBody">
            <!-- se carga por JS -->
        </tbody>
    </table>
    <div class="form-group">
        <div class="col-md-6">
            <label for="comentario" class="form-label">Comentario <span class="text-danger"></span></label>
            <input type="text" id="comentario" name="comentario" class="form-control form-control-sm">
            <input type="hidden" id="venta_id" name="venta_id" class="form-control form-control-sm">
            <div class="invalid-feedback"></div>
        </div>
    </div>
</div>