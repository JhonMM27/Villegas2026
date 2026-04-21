<?php

use App\Http\Controllers\AfectacionTipoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaPagoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CobranzaTipoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CompraProvisionalController;
use App\Http\Controllers\ComprobanteSerieController;
use App\Http\Controllers\ComprobanteTipoController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CuentaCorrienteClienteController;
use App\Http\Controllers\CuentaCorrienteProveedorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoTipoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\FormulacionController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\LineaController;
use App\Http\Controllers\NucleoController;
use App\Http\Controllers\NucleoPreparadaController;
use App\Http\Controllers\OperacionTipoController;
use App\Http\Controllers\PagoFormaController;
use App\Http\Controllers\PagoMedioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlanillaAdelantoController;
use App\Http\Controllers\PlanillaInasistenciaController;
use App\Http\Controllers\PlanillaPagoController;
use App\Http\Controllers\PlanillaPrestamoController;
use App\Http\Controllers\PreparadaController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteCajaController;
use App\Http\Controllers\ReporteCompraController;
use App\Http\Controllers\ReporteFormulacionController;
use App\Http\Controllers\ReporteNucleoPreparadaController;
use App\Http\Controllers\ReportePlanillaController;
use App\Http\Controllers\ReportePreparadaController;
use App\Http\Controllers\ReportePrestamoController;
use App\Http\Controllers\ReporteRentabilidadController;
use App\Http\Controllers\ReporteVentaController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SunatCertificadoController;
use App\Http\Controllers\UnidadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaEntregaController;
use App\Http\Controllers\VentaProvisionalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return view('landing');
    })->name('home');
    Route::get('login', function () {
        return view('autenticacion.login');
    })->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:3,1');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/afectacion-tipos/select', [AfectacionTipoController::class, 'select'])->name('afectacion-tipos.select');
    Route::resource('afectacion-tipos', AfectacionTipoController::class)->except(['create', 'edit']);
    Route::get('/unidades/select', [UnidadController::class, 'select'])->name('unidades.select');
    Route::get('/permisos/select', [RoleController::class, 'permisos'])->name('permisos.select');
    Route::get('/roles/select', [RoleController::class, 'roles'])->name('roles.select');
    Route::get('/documento-tipos/select', [DocumentoTipoController::class, 'select'])->name('documento-tipos.select');
    Route::resource('documento-tipos', DocumentoTipoController::class)->except(['create', 'edit']);
    Route::get('/comprobante-tipos/select', [ComprobanteTipoController::class, 'select'])->name('comprobante-tipos.select');
    Route::resource('comprobante-tipos', ComprobanteTipoController::class)->except(['create', 'edit']);
    Route::get('/cobranza-tipos/select', [CobranzaTipoController::class, 'select'])->name('cobranza-tipos.select');
    Route::resource('cobranza-tipos', CobranzaTipoController::class)->except(['create', 'edit']);
    Route::get('/pago-formas/select', [PagoFormaController::class, 'select'])->name('pago-formas.select');
    Route::resource('pago-formas', PagoFormaController::class)->except(['create', 'edit']);
    Route::get('/pago-medios/select', [PagoMedioController::class, 'select'])->name('pago-medios.select');
    Route::resource('pago-medios', PagoMedioController::class)->except(['create', 'edit']);
    Route::get('/lineas/select', [LineaController::class, 'select'])->name('lineas.select');
    Route::resource('lineas', LineaController::class)->except(['create', 'edit']);
    Route::get('/operacion-tipos/select', [OperacionTipoController::class, 'select'])->name('operacion-tipos.select');
    Route::resource('operacion-tipos', OperacionTipoController::class)->except(['create', 'edit']);
    Route::get('/sunat-certificados/select', [SunatCertificadoController::class, 'select'])->name('sunat-certificados.select');
    Route::resource('sunat-certificados', SunatCertificadoController::class)->except(['create', 'edit']);
    Route::resource('comprobante-series', ComprobanteSerieController::class)->except(['create', 'edit']);
    Route::get('/configuraciones/json', [ConfiguracionController::class, 'json'])->name('configuraciones.json');
    Route::resource('configuraciones', ConfiguracionController::class)->except(['create', 'edit']);
    Route::get('/productos/buscar', [ProductoController::class, 'buscar'])->name('productos.buscar');
    Route::get('/productos/buscar-formulacion', [ProductoController::class, 'buscarFormulacion'])->name('productos.buscar-formulacion');
    Route::get('/productos/buscar-formulacion-preparada', [ProductoController::class, 'buscarFormulacionPreparada'])->name('productos.buscar-formulacion-preparada');
    Route::get('/productos/buscar-nucleo', [ProductoController::class, 'buscarProductoNucleo'])->name('productos.buscar-nucleo');
    Route::get('/productos/buscar-aditivo', [ProductoController::class, 'buscarProductoAditivo'])->name('productos.buscar-aditivo');
    Route::get('/clientes/buscar', [ClienteController::class, 'buscar'])->name('clientes.buscar');
    Route::get('/proveedores/buscar', [ProveedorController::class, 'buscar'])->name('proveedores.buscar');

    Route::get('/unidades/buscar', [UnidadController::class, 'buscar'])->name('unidades.buscar');
    Route::resource('unidades', UnidadController::class)->except(['create', 'edit']);
    Route::get('/productos/{id}/ver', [ProductoController::class, 'view'])->name('productos.ver');
    Route::resource('productos', ProductoController::class)->except(['create', 'edit']);
    Route::resource('roles', RoleController::class)->except(['create', 'edit']);
    Route::resource('usuarios', UserController::class)->except(['create', 'edit']);
    Route::get('/reportes/clientes/imprimir', [ClienteController::class, 'imprimir'])->name('reportes.clientes.imprimir');
    Route::resource('clientes', ClienteController::class)->except(['create', 'edit']);
    Route::get('/reportes/proveedores/imprimir', [ProveedorController::class, 'imprimir'])->name('reportes.proveedores.imprimir');
    Route::resource('proveedores', ProveedorController::class)->except(['create', 'edit']);

    Route::get('/compras/{id}/imprimir', [CompraController::class, 'printTicket'])->name('compras.imprimir');
    Route::get('/compras/serie-correlativo', [CompraController::class, 'getSerie'])->name('compras.get-serie');
    Route::get('/compras/{id}/ver', [CompraController::class, 'view'])->name('compras.ver');
    Route::post('/compras/{id}/anular', [CompraController::class, 'anular'])->name('compras.anular'); // METODO AGREGADO
    Route::resource('compras', CompraController::class)->except(['create', 'edit', 'update', 'destroy']);

    Route::get('/formulaciones/{id}/imprimir', [FormulacionController::class, 'printTicket'])->name('formulaciones.imprimir');
    Route::get('/formulaciones/{id}/ver', [FormulacionController::class, 'view'])->name('formulaciones.ver');
    Route::get('/formulaciones/buscar-formulacion', [FormulacionController::class, 'buscar'])->name('formulaciones.buscar');
    Route::resource('formulaciones', FormulacionController::class)->except(['create', 'edit']);

    Route::get('/preparadas/{id}/imprimir', [PreparadaController::class, 'printTicket'])->name('preparadas.imprimir');
    Route::get('/preparadas/{id}/ver', [PreparadaController::class, 'view'])->name('preparadas.ver');
    Route::post('/preparadas/{id}/anular', [PreparadaController::class, 'anular'])->name('preparadas.anular');
    Route::resource('preparadas', PreparadaController::class)->except(['create', 'edit', 'update', 'destroy']);

    Route::get('/ventas/{id}/imprimir', [VentaController::class, 'printTicket'])->name('ventas.imprimir');
    Route::get('/ventas/serie-correlativo', [VentaController::class, 'getSerie'])->name('ventas.get-serie');
    Route::get('/ventas/{id}/ver', [VentaController::class, 'view'])->name('ventas.ver');
    Route::post('/ventas/{id}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
    Route::post('/ventas/{id}/rectificar', [VentaController::class, 'rectificar'])->name('ventas.rectificar');
    Route::resource('ventas', VentaController::class)->except(['create', 'edit', 'update', 'destroy']);

    Route::get('/cotizaciones/{id}/imprimir', [CotizacionController::class, 'printTicket'])->name('cotizaciones.imprimir');
    Route::get('/cotizaciones/serie-correlativo', [CotizacionController::class, 'getSerie'])->name('cotizaciones.get-serie');
    Route::get('/cotizaciones/{id}/ver', [CotizacionController::class, 'view'])->name('cotizaciones.ver');
    Route::resource('cotizaciones', CotizacionController::class)->except(['create', 'edit']);

    Route::get('/caja-pagos/{id}/ver', [CajaPagoController::class, 'view'])->name('caja-pagos.ver');
    Route::resource('caja-pagos', CajaPagoController::class)->except(['create', 'edit']);

    Route::get('/venta-provisionales/{id}/imprimir', [VentaProvisionalController::class, 'printTicket'])->name('venta-provisionales.imprimir');
    Route::get('/venta-provisionales/{id}/ver', [VentaProvisionalController::class, 'view'])->name('venta-provisionales.ver');
    Route::get('/ventas-provisionales/ventas-saldo', [VentaProvisionalController::class, 'ventasConSaldo'])->name('venta-provisionales.ventas-con-saldo');
    Route::resource('venta-provisionales', VentaProvisionalController::class)->except(['create', 'edit']);

    Route::get('/venta-entregas/{id}/imprimir', [VentaEntregaController::class, 'printTicket'])->name('venta-entregas.imprimir');
    Route::get('/venta-entregas/{id}/ver', [VentaEntregaController::class, 'view'])->name('venta-entregas.ver');
    Route::get('/venta-entregas/ventas-por-entregar', [VentaEntregaController::class, 'ventasPorEntregar'])->name('venta-entregas.ventas-por-entregar');
    Route::get('/venta-entregas/ventas-por-entregar-detalle/{ventaId}', [VentaEntregaController::class, 'ventasPorEntregarDetalle'])->name('venta-entregas.ventas-por-entregar-detalle');
    Route::put('/venta-entregas/{id}/anular', [VentaEntregaController::class, 'anular'])->name('venta-entregas.anular');
    Route::put('/venta-entregas/{id}/rectificar', [VentaEntregaController::class, 'rectificar'])->name('venta-entregas.rectificar');
    Route::resource('venta-entregas', VentaEntregaController::class)->except(['create', 'edit']);

    Route::get('/compra-provisionales/{id}/imprimir', [CompraProvisionalController::class, 'printTicket'])->name('compra-provisionales.imprimir');
    Route::get('/compra-provisionales/{id}/ver', [CompraProvisionalController::class, 'view'])->name('compra-provisionales.ver');
    Route::get('/compra-provisionales/ventas-saldo', [CompraProvisionalController::class, 'comprasConSaldo'])->name('compra-provisionales.compras-con-saldo');
    Route::resource('compra-provisionales', CompraProvisionalController::class)->except(['create', 'edit']);

    Route::get('/prestamos/{id}/imprimir', [PrestamoController::class, 'printTicket'])->name('prestamos.imprimir');
    Route::get('/prestamos/serie-correlativo', [PrestamoController::class, 'getSerie'])->name('prestamos.get-serie');
    Route::get('/prestamos/{id}/ver', [PrestamoController::class, 'view'])->name('prestamos.ver');
    Route::post('/prestamos/{id}/anular', [PrestamoController::class, 'anular'])->name('prestamos.anular');
    Route::post('/prestamos/{id}/rectificar', [PrestamoController::class, 'rectificar'])->name('prestamos.rectificar');
    Route::resource('prestamos', PrestamoController::class)->except(['create', 'edit']);

    Route::get('/nucleos/{id}/imprimir', [NucleoController::class, 'printTicket'])->name('nucleos.imprimir');
    Route::get('/nucleos/{id}/ver', [NucleoController::class, 'view'])->name('nucleos.ver');
    Route::get('/nucleos/buscar', [NucleoController::class, 'buscar'])->name('nucleos.buscar');
    Route::resource('nucleos', NucleoController::class)->except(['create', 'edit']);

    Route::get('/nucleo-preparadas/{id}/imprimir', [NucleoPreparadaController::class, 'printTicket'])->name('nucleo-preparadas.imprimir');
    Route::get('/nucleo-preparadas/{id}/ver', [NucleoPreparadaController::class, 'view'])->name('nucleo-preparadas.ver');
    Route::post('/nucleo-preparadas/{id}/anular', [NucleoPreparadaController::class, 'anular'])->name('nucleo-preparadas.anular');
    Route::post('/nucleo-preparadas/{id}/rectificar', [NucleoPreparadaController::class, 'rectificar'])->name('nucleo-preparadas.rectificar');
    Route::resource('nucleo-preparadas', NucleoPreparadaController::class)->except(['create', 'edit', 'destroy']);

    Route::get('/gastos/{id}/ver', [GastoController::class, 'view'])->name('gastos.ver');
    Route::get('/gastos/{id}/imprimir', [GastoController::class, 'printTicket'])->name('gastos.imprimir');
    Route::resource('gastos', GastoController::class)->except(['create', 'edit']);

    // Reporte Gastos
    Route::get('reportes/gastos/resumen', [GastoController::class, 'reporteResumen'])->name('reportes.gastos.resumen');
    Route::get('reportes/gastos/resumen/export', [GastoController::class, 'exportarResumen'])->name('reportes.gastos.resumen.export');
    Route::get('reportes/gastos/resumen/imprimir', [GastoController::class, 'imprimirResumen'])->name('reportes.gastos.resumen.imprimir');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/logout', function () {
        Auth::logout();

        return redirect('/login');
    })->name('logout');

    // Reporte compras
    Route::get('reportes/compras', [ReporteCompraController::class, 'index'])->name('reportes.compras');
    Route::get('/reportes/compras/acumuladas', [ReporteCompraController::class, 'comprasAcumuladasProducto'])->name('reportes.compras_acumuladas_producto');
    Route::get('reportes/compras_acumuladas_producto/export', [ReporteCompraController::class, 'exportarComprasAcumuladasProducto'])
        ->name('reportes.compras_acumuladas_producto.export');
    Route::get('/reportes/compras/compras_acumuladas_producto/imprimir', [ReporteCompraController::class, 'imprimirComprasAcumuladasProducto'])->name('reportes.compras_acumuladas_producto.imprimir');

    Route::get('/reportes/compras/fechas', [ReporteCompraController::class, 'comprasPorFecha'])->name('reportes.compras_fecha');
    Route::get('reportes/compras/fecha/export', [ReporteCompraController::class, 'exportarComprasFecha'])
        ->name('reportes.compras_fecha.export');
    Route::get('/reportes/compras/compras_fecha/imprimir', [ReporteCompraController::class, 'imprimirComprasFecha'])->name('reportes.compras_fecha.imprimir');

    Route::get('/reportes/compras/proveedor', [ReporteCompraController::class, 'comprasPorProveedor'])->name('reportes.compras_proveedor');
    Route::get('reportes/compras/proveedor/export', [ReporteCompraController::class, 'exportarComprasProveedor'])
        ->name('reportes.compras_proveedor.export');
    Route::get('/reportes/compras/compras_proveedor/imprimir', [ReporteCompraController::class, 'imprimirComprasProveedor'])->name('reportes.compras_proveedor.imprimir');

    Route::get('/reportes/compras/detallas/producto', [ReporteCompraController::class, 'comprasDetalladasProducto'])->name('reportes.compras_detalladas_producto');
    Route::get('reportes/compras/detalladas/producto/export', [ReporteCompraController::class, 'exportarComprasDetalladasProducto'])
        ->name('reportes.compras_detalladas_producto.export');
    Route::get('/reportes/compras/compras_detalladas_producto/imprimir', [ReporteCompraController::class, 'imprimirComprasDetalladasProducto'])->name('reportes.compras_detalladas_producto.imprimir');

    Route::get('/reportes/compras/detalladas/proveedor', [ReporteCompraController::class, 'comprasDetalladasProveedor'])->name('reportes.compras_detalladas_proveedor');
    Route::get('reportes/compras/detalladas/proveedor/export', [ReporteCompraController::class, 'exportarComprasDetalladasProveedor'])
        ->name('reportes.compras_detalladas_proveedor.export');
    Route::get('/reportes/compras/compras_detalladas_proveedor/imprimir', [ReporteCompraController::class, 'imprimirComprasDetalladasProveedor'])->name('reportes.compras_detalladas_proveedor.imprimir');

    Route::get('/reportes/compras/detalladas/fecha', [ReporteCompraController::class, 'comprasDetalladasFecha'])->name('reportes.compras_detalladas_fecha');
    Route::get('reportes/compras/detalladas/fecha/export', [ReporteCompraController::class, 'exportarComprasDetalladasFecha'])
        ->name('reportes.compras_detalladas_fecha.export');
    Route::get('/reportes/compras/compras_detallas_fecha/imprimir', [ReporteCompraController::class, 'imprimirComprasDetalladasFecha'])->name('reportes.compras_detalladas_fecha.imprimir');

    Route::get('/reportes/compras/top-productos-mes', [ReporteCompraController::class, 'topProductosMes'])
        ->name('reportes.top-productos-mes');
    Route::get('/reportes/compras/compras-ultimos-15', [ReporteCompraController::class, 'comprasUltimos15'])
        ->name('reportes.compras-ultimos-15');

    // Reporte de ventas
    Route::get('reportes/ventas', [ReporteVentaController::class, 'index'])->name('reportes.ventas');
    Route::get('/reportes/ventas/emitidas', [ReporteVentaController::class, 'ventasEmitidas'])->name('reportes.ventas_emitidas');
    Route::get('reportes/ventas/emitidas/export', [ReporteVentaController::class, 'exportarVentasEmitidas'])->name('reportes.ventas_emitidas.export');
    Route::get('/reportes/ventas/emitidas/imprimir', [ReporteVentaController::class, 'imprimirVentasEmitidas'])->name('reportes.ventas_emitidas.imprimir');

    Route::get('/reportes/ventas/tipo-documentos', [ReporteVentaController::class, 'ventasTipoDocumentos'])->name('reportes.tipo_documentos_emitidos');
    Route::get('reportes/ventas/tipo-documentos/export', [ReporteVentaController::class, 'exportarVentasTipoDocumentos'])->name('reportes.ventas_tipos_documentos.export');
    Route::get('/reportes/ventas/tipo-documentos/imprimir', [ReporteVentaController::class, 'imprimirVentasTipoDocumentos'])->name('reportes.ventas_tipos_documentos.imprimir');

    Route::get('/reportes/ventas/acumuladas', [ReporteVentaController::class, 'ventasAcumuladasProducto'])->name('reportes.ventas_acumuladas_producto');
    Route::get('reportes/ventas_acumuladas_producto/export', [ReporteVentaController::class, 'exportarVentasAcumuladasProducto'])
        ->name('reportes.ventas_acumuladas_producto.export');
    Route::get('/reportes/ventas_acumuladas/imprimir', [ReporteVentaController::class, 'imprimirVentasAcumuladasProducto'])->name('reportes.ventas_acumuladas_producto.imprimir');

    Route::get('/reportes/ventas/agrupadas', [ReporteVentaController::class, 'ventasagrupadasProducto'])->name('reportes.ventas_agrupadas_producto');
    /*Route::get('reportes/ventas_agrupadas_producto/export', [ReporteVentaController::class, 'exportarVentasagrupadasProducto'])
    ->name('reportes.ventas_agrupadas_producto.export');*/
    Route::get('/reportes/ventas_agrupadas/imprimir', [ReporteVentaController::class, 'imprimirVentasAgrupadasProducto'])->name('reportes.ventas_agrupadas_producto.imprimir');

    Route::get('/reportes/ventas/por_entregar', [ReporteVentaController::class, 'ventasPorEntregar'])->name('reportes.ventas_por_entregar');
    Route::get('/reportes/ventas/por_entregar/imprimir', [ReporteVentaController::class, 'imprimirVentasPorEntregar'])->name('reportes.ventas_por_entregar.imprimir');

    Route::get('/reportes/ventas/por_entregar_clientes', [ReporteVentaController::class, 'ventasPorEntregarClientes'])->name('reportes.ventas_por_entregar_clientes');
    Route::get('/reportes/ventas/por_entregar_clientes/imprimir', [ReporteVentaController::class, 'imprimirVentasPorEntregarClientes'])->name('reportes.ventas_por_entregar_clientes.imprimir');

    Route::get('/reportes/ventas/top-productos-mes', [ReporteVentaController::class, 'topProductosMes'])
        ->name('reportes.ventas-top-productos-mes');
    Route::get('/reportes/ventas/ventas-ultimos-15', [ReporteVentaController::class, 'ventasUltimos15'])
        ->name('reportes.ventas-ultimos-15');

    Route::get('reportes/formulaciones', [ReporteFormulacionController::class, 'index'])->name('reportes.formulaciones');
    Route::get('/reportes/formulaciones/fechas', [ReporteFormulacionController::class, 'formulacionesFechas'])->name('reportes.formulaciones_fecha');
    Route::get('reportes/formulaciones/fechas/export', [ReporteFormulacionController::class, 'exportarFormulacionesFechas'])
        ->name('reportes.formulaciones_fecha.export');
    Route::get('/reportes/formulaciones/rango', [ReporteFormulacionController::class, 'formulacionesRango'])->name('reportes.formulaciones_rango');
    Route::get('reportes/formulaciones/rango/export', [ReporteFormulacionController::class, 'exportarFormulacionesRango'])
        ->name('reportes.formulaciones_rango.export');
    Route::get('/reportes/formulaciones/rango/imprimir', [ReporteFormulacionController::class, 'imprimirFormulacionesRango'])->name('reportes.formulaciones_rango.imprimir');
    Route::get('/reportes/formulaciones/fecha/imprimir', [ReporteFormulacionController::class, 'imprimirFormulacionesFecha'])->name('reportes.formulaciones_fecha.imprimir');

    Route::get('reportes/preparadas', [ReportePreparadaController::class, 'index'])->name('reportes.preparadas');
    Route::get('/reportes/preparadas/fechas', [ReportePreparadaController::class, 'preparadasFechas'])->name('reportes.preparadas_fechas');
    Route::get('reportes/preparadas/fechas/export', [ReportePreparadaController::class, 'exportarPreparadasFechas'])
        ->name('reportes.preparadas_fechas.export');
    Route::get('/reportes/preparadas/fechas/imprimir', [ReportePreparadaController::class, 'imprimirPreparadasFechas'])->name('reportes.preparadas_fechas.imprimir');
    Route::get('/reportes/preparadas/acumuladas', [ReportePreparadaController::class, 'preparadasAcumuladas'])->name('reportes.preparadas_acumuladas');
    Route::get('reportes/preparadas/acumuladas/export', [ReportePreparadaController::class, 'exportarPreparadasAcumuladas'])
        ->name('reportes.preparadas_acumuladas.export');
    Route::get('/reportes/preparadas/acumuladas/imprimir', [ReportePreparadaController::class, 'imprimirPreparadasAcumuladas'])->name('reportes.preparadas_acumuladas.imprimir');

    Route::get('reportes/nucleo-preparadas', [ReporteNucleoPreparadaController::class, 'index'])->name('reportes.nucleo_preparadas');
    Route::get('/reportes/nucleo-preparadas/fechas', [ReporteNucleoPreparadaController::class, 'nucleoPreparadasFechas'])->name('reportes.nucleo_preparadas_fechas');
    Route::get('/reportes/nucleo-preparadas/fechas/imprimir', [ReporteNucleoPreparadaController::class, 'imprimirNucleoPreparadasFechas'])->name('reportes.nucleo_preparadas_fechas.imprimir');
    Route::get('/reportes/nucleo-preparadas/acumuladas', [ReporteNucleoPreparadaController::class, 'nucleoPreparadasAcumuladas'])->name('reportes.nucleo_preparadas_acumuladas');
    Route::get('/reportes/nucleo-preparadas/acumuladas/imprimir', [ReporteNucleoPreparadaController::class, 'imprimirNucleoPreparadasAcumuladas'])->name('reportes.nucleo_preparadas_acumuladas.imprimir');

    Route::get('reportes/prestamos', [ReportePrestamoController::class, 'index'])->name('reportes.prestamos');
    Route::get('/reportes/prestamos/a_pendientes', [ReportePrestamoController::class, 'prestamoAPendiente'])->name('reportes.prestamos_a_pendientes');
    Route::get('/reportes/prestamos/a_pendientes/imprimir', [ReportePrestamoController::class, 'imprimirPrestamoAPendiente'])->name('reportes.prestamos_a_pendientes.imprimir');
    Route::get('/reportes/prestamos/de_pendientes', [ReportePrestamoController::class, 'prestamoDePendiente'])->name('reportes.prestamos_de_pendientes');
    Route::get('/reportes/prestamos/de_pendientes/imprimir', [ReportePrestamoController::class, 'imprimirPrestamoDePendiente'])->name('reportes.prestamos_de_pendientes.imprimir');

    // Reporte caja
    Route::get('reportes/caja', [ReporteCajaController::class, 'index'])->name('reportes.caja');
    Route::get('/reportes/caja/general', [ReporteCajaController::class, 'general'])->name('reportes.caja.general');
    Route::get('/reportes/caja/detallado', [ReporteCajaController::class, 'detallado'])->name('reportes.caja.detallado');
    Route::get('/reportes/caja/general/pdf', [ReporteCajaController::class, 'generalPdf'])->name('reportes.caja.general.imprimir');

    // Reporte Kardex
    Route::get('kardex', [KardexController::class, 'index'])->name('kardex');
    Route::get('/kardex/stock/general', [KardexController::class, 'stockGeneral'])->name('kardex.stock_general');
    Route::get('kardex/stock/general/export', [KardexController::class, 'exportarStockGeneral'])
        ->name('kardex.stock_general.export');
    Route::get('/kardex/stock/general/imprimir', [KardexController::class, 'imprimirStockGeneral'])->name('kardex.stock_general.imprimir');

    Route::get('/kardex/fechas', [KardexController::class, 'kardexFechasProductos'])->name('kardex.fechas');
    Route::get('/kardex/fechas/pdf', [KardexController::class, 'kardexFechasProductosPdf'])
        ->name('kardex.fechas_productos.imprimir');
    Route::get('/kardex/fechas-productos/excel', [KardexController::class, 'kardexFechasProductosExcel'])
        ->name('kardex.fechas_productos.export');

    Route::get('/kardex/movimientos', [KardexController::class, 'reporteMovimientos'])->name('kardex.reporteMovimientos');
    Route::get('/kardex/movimientos/export', [KardexController::class, 'exportarMovimientosExcel'])->name('kardex.exportarMovimientosExcel');
    Route::get('/kardex/movimientos/pdf', [KardexController::class, 'imprimirMovimientosPdf'])->name('kardex.imprimirMovimientosPdf');

    // Cuenta Corriente Cliente - Individual
    Route::get('cuenta-corriente/cliente', [CuentaCorrienteClienteController::class, 'index'])->name('cuenta.corriente.cliente');
    Route::get('/cuenta-corriente/cliente/ventas-producto/pdf', [CuentaCorrienteClienteController::class, 'ventasAgrupadaProductoClientePdf'])
        ->name('cuenta.corriente.cliente.ventas_producto_cliente_pdf');
    Route::get('/cuenta-corriente/cliente/ventas-detalles/pdf', [CuentaCorrienteClienteController::class, 'ventasDetallePdf'])
        ->name('cuenta.corriente.cliente.ventas_detalle_pdf');
    Route::get('/cuenta-corriente/cliente/creditos_cobrar_detalles/pdf', [CuentaCorrienteClienteController::class, 'detalleCreditosPorCobrarPdf'])
        ->name('cuenta.corriente.cliente.creditos_cobrar_detalles_pdf');
    Route::get('/cuenta-corriente/cliente/creditos_cobrar_cliente_todos/pdf', [CuentaCorrienteClienteController::class, 'creditosPorCobrarClienteTodosPdf'])
        ->name('cuenta.corriente.cliente.creditos_cobrar_cliente_todos_pdf');
    Route::get('/cuenta-corriente/cliente/creditos_cobrar_cliente_fechas/pdf', [CuentaCorrienteClienteController::class, 'creditosPorCobrarClienteFechasPdf'])
        ->name('cuenta.corriente.cliente.creditos_cobrar_cliente_fechas_pdf');
    Route::get('/cuenta-corriente/cliente/ventas_general_fechas/pdf', [CuentaCorrienteClienteController::class, 'ventasGeneralFechasPdf'])
        ->name('cuenta.corriente.cliente.ventas_general_fechas_pdf');
    Route::get('/cuenta-corriente/cliente/saldos/pdf', [CuentaCorrienteClienteController::class, 'saldosTodosPdf'])
        ->name('cuenta.corriente.cliente.saldos_pdf');
    Route::get('/cuenta-corriente/cliente/estado_cuenta/pdf', [CuentaCorrienteClienteController::class, 'estadoCuentaClientePdf'])
        ->name('cuenta.corriente.cliente.estado_cuenta_pdf');
    Route::get('/cuenta-corriente/cliente/estado_cuenta_simplicado/pdf', [CuentaCorrienteClienteController::class, 'estadoCuentaSimplificadoClientePdf'])
        ->name('cuenta.corriente.cliente.estado_cuenta_simplificado_pdf');
    // Cuenta Corriente Cliente - General
    Route::get('/cuenta-corriente/cliente/creditos_cobrar_fechas/pdf', [CuentaCorrienteClienteController::class, 'creditosPorCobrarFechasPdf'])
        ->name('cuenta.corriente.cliente.creditos_cobrar_fechas_pdf');
    Route::get('/cuenta-corriente/cliente/creditos_cobrar_dias/pdf', [CuentaCorrienteClienteController::class, 'creditosPorCobrarDiasPdf'])
        ->name('cuenta.corriente.cliente.creditos_cobrar_dias_pdf');
    Route::get('/cuenta-corriente/cliente/creditos_cobrar_dias_agrupado_clientes/pdf', [CuentaCorrienteClienteController::class, 'creditosPorCobrarDiasAgrupadoClientePdf'])
        ->name('cuenta.corriente.cliente.creditos_cobrar_dias_agrupado_clientes_pdf');
    Route::get('/cuenta-corriente/cliente/creditos-por-cobrar', [CuentaCorrienteClienteController::class, 'creditosPorCobrarIndex'])
        ->name('cuenta.corriente.cliente.creditos_por_cobrar');
    Route::get('/cuenta-corriente/cliente/creditos-por-cobrar/data', [CuentaCorrienteClienteController::class, 'creditosPorCobrarData'])
        ->name('cuenta.corriente.cliente.creditos_por_cobrar_data');
    Route::get('/cuenta-corriente/cliente/saldos_acumuladosdias_cliente/pdf', [CuentaCorrienteClienteController::class, 'saldosAcumuladosClienteDiasPdf'])
        ->name('cuenta.corriente.cliente.saldos_acumuladosdias_cliente_pdf');
    Route::get('/cuenta-corriente/cliente/saldos_acumuladosfechas_cliente/pdf', [CuentaCorrienteClienteController::class, 'saldosAcumuladosClienteFechasPdf'])
        ->name('cuenta.corriente.cliente.saldos_acumuladosfechas_cliente_pdf');

    Route::get('/cuenta-corriente/cliente/saldos_fecha/pdf', [CuentaCorrienteClienteController::class, 'saldoFechaSolicitadaPdf'])
        ->name('cuenta.corriente.saldo_fecha_solicitada_pdf');
    Route::get('/cuenta-corriente/cliente/resumen_creditos_cobrar/pdf', [CuentaCorrienteClienteController::class, 'resumenCreditosPorCobrarPdf'])
        ->name('cuenta.corriente.cliente.resumen_creditos_cobrar_pdf');

    // Cuenta Corriente Proveedor - Individual
    Route::get('cuenta-corriente/proveedor', [CuentaCorrienteProveedorController::class, 'index'])->name('cuenta.corriente.proveedor');

    Route::get('/cuenta-corriente/proveedor/compras-producto/pdf', [CuentaCorrienteProveedorController::class, 'comprasAgrupadaProductoProveedorPdf'])
        ->name('cuenta.corriente.proveedor.compras_producto_proveedor_pdf');

    /*Route::get('/cuenta-corriente/proveedor/ventas-detalles/pdf', [CuentaCorrienteProveedorController::class, 'ventasDetallePdf'])
    ->name('cuenta.corriente.proveedor.ventas_detalle_pdf');*/
    Route::get('/cuenta-corriente/proveedor/creditos_pagar_detalles/pdf', [CuentaCorrienteProveedorController::class, 'detalleCreditosPorPagarPdf'])
        ->name('cuenta.corriente.proveedor.creditos_pagar_detalles_pdf');
    Route::get('/cuenta-corriente/proveedor/creditos_pagar_proveedor_todos/pdf', [CuentaCorrienteProveedorController::class, 'creditosPorPagarProveedorTodosPdf'])
        ->name('cuenta.corriente.proveedor.creditos_pagar_proveedor_todos_pdf');
    Route::get('/cuenta-corriente/proveedor/creditos_pagar_proveedor_fechas/pdf', [CuentaCorrienteProveedorController::class, 'creditosPorPagarProveedorFechasPdf'])
        ->name('cuenta.corriente.proveedor.creditos_pagar_proveedor_fechas_pdf');
    Route::get('/cuenta-corriente/proveedor/estado_cuenta/pdf', [CuentaCorrienteProveedorController::class, 'estadoCuentaProveedorPdf'])
        ->name('cuenta.corriente.proveedor.estado_cuenta_pdf');
    Route::get('/cuenta-corriente/proveedor/estado_cuenta_simplificado/pdf', [CuentaCorrienteProveedorController::class, 'estadoCuentaProveedorPdf'])
        ->name('cuenta.corriente.proveedor.estado_cuenta_simplificado_pdf');
    Route::get('/cuenta-corriente/proveedor/compras_general_fechas/pdf', [CuentaCorrienteProveedorController::class, 'comprasGeneralFechasPdf'])
        ->name('cuenta.corriente.proveedor.compras_general_fechas_pdf');
    /*Route::get('/cuenta-corriente/proveedor/saldos/pdf', [CuentaCorrienteProveedorController::class, 'saldosTodosPdf'])
    ->name('cuenta.corriente.proveedor.saldos_pdf');*/
    // Cuenta Corriente Proveedor - General
    Route::get('/cuenta-corriente/proveedor/creditos_pagar_todos/pdf', [CuentaCorrienteProveedorController::class, 'creditosPorPagarTodosPdf'])
        ->name('cuenta.corriente.proveedor.creditos_pagar_todos_pdf');
    Route::get('/cuenta-corriente/proveedor/creditos_pagar_dias/pdf', [CuentaCorrienteProveedorController::class, 'creditosPorPagarDiasPdf'])
        ->name('cuenta.corriente.proveedor.creditos_pagar_dias_pdf');
    Route::get('/cuenta-corriente/proveedor/creditos_pagar_dias_agrupado_proveedors/pdf', [CuentaCorrienteProveedorController::class, 'creditosPorPagarDiasAgrupadoProveedorPdf'])
        ->name('cuenta.corriente.proveedor.creditos_pagar_dias_agrupado_proveedors_pdf');
    Route::get('/cuenta-corriente/proveedor/saldos_acumuladosdias_proveedor/pdf', [CuentaCorrienteProveedorController::class, 'saldosAcumuladosProveedorDiasPdf'])
        ->name('cuenta.corriente.proveedor.saldos_acumuladosdias_proveedor_pdf');
    Route::get('/cuenta-corriente/proveedor/saldos_fechas/pdf', [CuentaCorrienteProveedorController::class, 'saldoFechasSolicitadaPdf'])
        ->name('cuenta.corriente.proveedor.saldo_fechas_solicitada_pdf');
    Route::get('/cuenta-corriente/proveedor/resumen_creditos_pagar/pdf', [CuentaCorrienteProveedorController::class, 'resumenCreditosPorPagarPdf'])
        ->name('cuenta.corriente.proveedor.resumen_creditos_pagar_pdf');

    Route::get('/cuenta-corriente/proveedor/creditos_pagar_detalles_todos/pdf', [CuentaCorrienteProveedorController::class, 'detalleCreditosPorPagarTodosPdf'])
        ->name('cuenta.corriente.proveedor.creditos_pagar_detalles_todos_pdf');

    // Reporte rentabilidad
    Route::get('reportes/rentabilidad', [ReporteRentabilidadController::class, 'index'])->name('reportes.rentabilidad');
    Route::get('/reportes/rentabilidad/ventas', [ReporteRentabilidadController::class, 'rentabilidadVentasFechas'])->name('reportes.rentabilidad_ventas_fechas');
    Route::get('/reportes/rentabilidad/ventas/imprimir', [ReporteRentabilidadController::class, 'imprimirRentabilidadVentasFechas'])->name('reportes.rentabilidad_ventas_fechas.imprimir');
    Route::get('/reportes/rentabilidad/ventas-detalles', [ReporteRentabilidadController::class, 'rentabilidadDetalleVentasFechas'])->name('reportes.rentabilidad_ventas_detalles_fechas');
    Route::get('/reportes/rentabilidad/ventas-detalles/imprimir', [ReporteRentabilidadController::class, 'imprimirRentabilidadDetalleVentasFechas'])->name('reportes.rentabilidad_ventas_detalles_fechas.imprimir');
    Route::get('/reportes/rentabilidad/productos_ventas', [ReporteRentabilidadController::class, 'rentabilidadProductosVentasFechas'])->name('reportes.rentabilidad_productos_ventas_fechas');
    Route::get('/reportes/rentabilidad/productos_ventas/imprimir', [ReporteRentabilidadController::class, 'imprimirRentabilidadProductosVentasFechas'])->name('reportes.rentabilidad_productos_ventas_fechas.imprimir');
    Route::get('/reportes/rentabilidad/productos', [ReporteRentabilidadController::class, 'rentabilidadProductosFechas'])->name('reportes.rentabilidad_productos_fechas');
    Route::get('/reportes/rentabilidad/productos/imprimir', [ReporteRentabilidadController::class, 'imprimirRentabilidadProductosFechas'])->name('reportes.rentabilidad_productos_fechas.imprimir');

    // Planilla - Empleados
    Route::get('/empleados/buscar', [EmpleadoController::class, 'buscar'])->name('empleados.buscar');
    Route::get('/empleados/{id}/edit', [EmpleadoController::class, 'edit'])->name('empleados.edit');
    Route::resource('empleados', EmpleadoController::class)->except(['create', 'edit']);

    // Planilla - Adelantos
    Route::get('/planilla-adelantos/disponible', [PlanillaAdelantoController::class, 'getDisponible'])->name('planilla-adelantos.disponible');
    Route::get('/planilla-adelantos/{id}/imprimir', [PlanillaAdelantoController::class, 'printTicket'])->name('planilla-adelantos.imprimir');
    Route::resource('planilla-adelantos', PlanillaAdelantoController::class)->except(['create']);

    // Planilla - Prestamos
    Route::get('/planilla-prestamos/{id}/ver', [PlanillaPrestamoController::class, 'view'])->name('planilla-prestamos.ver');
    Route::get('/planilla-prestamos/{id}/cuotas', [PlanillaPrestamoController::class, 'getCuotas'])->name('planilla-prestamos.cuotas');
    Route::get('/planilla-prestamos/{id}/imprimir', [PlanillaPrestamoController::class, 'printTicket'])->name('planilla-prestamos.imprimir');
    Route::get('/planilla-prestamos/{id}/montos', [PlanillaPrestamoController::class, 'getMontos'])->name('planilla-prestamos.montos');
    Route::post('/planilla-prestamos/{id}/pagar', [PlanillaPrestamoController::class, 'registrarPago'])->name('planilla-prestamos.pagar');

    // Pagos de Prestamos
    Route::get('/planilla-prestamos/{prestamoId}/pagos', [PlanillaPrestamoController::class, 'pagosIndex'])->name('planilla-prestamos.pagos-index');
    Route::get('/planilla-prestamos/{prestamoId}/pagos/data', [PlanillaPrestamoController::class, 'pagosData'])->name('planilla-prestamos.pagos-data');
    Route::get('/planilla-prestamos/pagos/{id}', [PlanillaPrestamoController::class, 'showPago'])->name('planilla-prestamos.pagos-show');
    Route::get('/planilla-prestamos/pagos/{id}/editar', [PlanillaPrestamoController::class, 'editPago'])->name('planilla-prestamos.pagos-edit');
    Route::put('/planilla-prestamos/pagos/{id}', [PlanillaPrestamoController::class, 'updatePago'])->name('planilla-prestamos.pagos-update');
    Route::delete('/planilla-prestamos/pagos/{id}', [PlanillaPrestamoController::class, 'destroyPago'])->name('planilla-prestamos.pagos-destroy');
    Route::get('/planilla-prestamos/pagos/{id}/imprimir', [PlanillaPrestamoController::class, 'printPagoTicket'])->name('planilla-prestamos.pagos-imprimir');

    Route::resource('planilla-prestamos', PlanillaPrestamoController::class)->except(['create']);

    // Planilla - Pagos
    Route::get('/planilla-pagos/disponible', [PlanillaPagoController::class, 'getDisponibleEmpleado'])->name('planilla-pagos.disponible');
    Route::post('/planilla-pagos/guardar-faltas', [PlanillaPagoController::class, 'guardarFaltas'])->name('planilla-pagos.guardar-faltas');
    Route::post('/planilla-pagos/generar', [PlanillaPagoController::class, 'generarPagosMes'])->name('planilla-pagos.generar');
    Route::post('/planilla-pagos/confirmar-todos', [PlanillaPagoController::class, 'confirmarPagosMes'])->name('planilla-pagos.confirmar-todos');
    Route::get('/planilla-pagos/estado-mes', [PlanillaPagoController::class, 'estadoPagosMes'])->name('planilla-pagos.estado-mes');
    Route::post('/planilla-pagos/{id}/marcar-pagado', [PlanillaPagoController::class, 'marcarPagado'])->name('planilla-pagos.marcar-pagado');
    Route::put('/planilla-pagos/{id}', [PlanillaPagoController::class, 'update'])->name('planilla-pagos.update');
    Route::resource('planilla-pagos', PlanillaPagoController::class)->except(['create', 'edit', 'update', 'destroy']);

    // Planilla - Inasistencias
    Route::resource('planilla-inasistencias', PlanillaInasistenciaController::class)->except(['create', 'edit']);

    // Planilla - Reportes
    Route::get('/reportes/planilla', [ReportePlanillaController::class, 'index'])->name('reportes.planilla');
    Route::get('/reportes/planilla/mensual', [ReportePlanillaController::class, 'mensual'])->name('reportes.planilla.mensual');
    Route::get('/reportes/planilla/empleado', [ReportePlanillaController::class, 'porEmpleado'])->name('reportes.planilla.empleado');
    Route::get('/reportes/planilla/adelantos/pdf', [ReportePlanillaController::class, 'adelantosPdf'])->name('reportes.planilla.adelantos');
    Route::get('/reportes/planilla/prestamos/pdf', [ReportePlanillaController::class, 'prestamosPdf'])->name('reportes.planilla.prestamos');
    Route::get('/reportes/planilla/pagos-pendientes/pdf', [ReportePlanillaController::class, 'pagosPendientesPdf'])->name('reportes.planilla.pagos_pendientes');
    Route::get('/reportes/planilla/empleado/pdf', [ReportePlanillaController::class, 'empleadoPdf'])->name('reportes.planilla.empleado_pdf');
    Route::get('/reportes/planilla/inasistencias/pdf', [ReportePlanillaController::class, 'inasistenciasPdf'])->name('reportes.planilla.inasistencias');

    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
});
