<aside class="app-sidebar sidebar-light" data-bs-theme="light">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <!--begin::Brand Link-->
        <a href="{{route('dashboard')}}" class="brand-link d-flex align-items-center">
            <!--begin::Brand Image-->
            <img src="{{asset('assets/favicon.ico')}}" alt="Sistema Logo" class="brand-image" />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text">Consorcios Villegas</span>
            <!--end::Brand Text-->
        </a>
        <!--end::Brand Link-->
    </div>
    <!--end::Sidebar Brand-->
    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-header">DASHBOARDS</li>
                <li class="nav-item">
                    <a href="{{route('dashboard')}}" class="nav-link" id="itemDashboard">
                        <x-icon name="gauge" class="nav-icon" />
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-header">GENERAL</li>
                @canany(['cobranza_tipos_list', 'comprobante_tipos_list','sunat_list','pago_formas_list','pago_medios_list','operacion_tipos_list','comprobante_tipos_list', 'documento_tipos_list', 'afectacion_tipos_list'])
                <li class="nav-item" id="mnuConfiguracion">
                    <a href="#" class="nav-link">                        
                        <i class="bi bi-gear"></i>
                        <p>
                            Configuración
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('empresa_list')
                        <li class="nav-item">
                            <a href="#" class="nav-link" id="itemEmpresa">
                                <p>Empresa</p>
                            </a>
                        </li>
                        @endcan
                        @can('sunat_list')
                        <li class="nav-item">
                            <a href="{{ route('sunat-certificados.index') }}" class="nav-link" id="itemSunat">
                                <p>SUNAT</p>
                            </a>
                        </li>
                        @endcan
                        @can('pago_formas_list')
                        <li class="nav-item">
                            <a href="{{ route('pago-formas.index') }}" class="nav-link" id="itemPagoForma">
                                <p>Formas de Pago</p>
                            </a>
                        </li>
                        @endcan
                        @can('pago_medios_list')
                        <li class="nav-item">
                            <a href="{{ route('pago-medios.index') }}" class="nav-link" id="itemPagoMedio">
                                <p>Medios de Pago</p>
                            </a>
                        </li>
                        @endcan
                        @can('operacion_tipos_list')
                        <li class="nav-item">
                            <a href="{{ route('operacion-tipos.index') }}" class="nav-link" id="itemOperacionTipo">
                                <p>Tipo de Operación</p>
                            </a>
                        </li>
                        @endcan
                        @can('comprobante_tipos_list')
                        <li class="nav-item">
                            <a href="{{route('comprobante-tipos.index')}}" class="nav-link" id="itemComprobanteTipo">
                                <p>Tipos de Comprobante</p>
                            </a>
                        </li>
                        @endcan
                        @can('documento_tipos_list')
                        <li class="nav-item">
                            <a href="{{route('documento-tipos.index')}}" class="nav-link" id="itemDocumentoTipo">
                                <p>Tipos de Documento</p>
                            </a>
                        </li>
                        @endcan
                        @can('afectacion_tipos_list')
                        <li class="nav-item">
                            <a href="{{route('afectacion-tipos.index')}}" class="nav-link" id="itemAfectacionTipo">
                                <p>Tipos de Afectación</p>
                            </a>
                        </li>
                        @endcan
                        @can('comprobante_tipos_list')
                        <li class="nav-item">
                            <a href="{{route('comprobante-series.index')}}" class="nav-link" id="itemComprobanteSerie">
                                <p>Correlativos</p>
                            </a>
                        </li>
                        @endcan
                        @can('cobranza_tipos_list')
                        <li class="nav-item">
                            <a href="{{route('cobranza-tipos.index')}}" class="nav-link" id="itemCobranzaTipo">
                                <p>Tipos de Cobranza</p>
                            </a>
                        </li>
                        @endcan
                        @can('configuraciones_list')
                        <li class="nav-item">
                            <a href="{{route('configuraciones.index')}}" class="nav-link" id="itemCostoServicioPreparada">
                                <p>Costo servicio preparada</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany
                @canany(['unidades_list', 'productos_list', 'lineas_list'])
                <li class="nav-item" id="mnuCatalogo">
                    <a href="#" class="nav-link">                        
                        <x-icon name="package" />
                        <p>
                            Catálogos
                            <x-icon name="chevron-right" class="nav-arrow" />
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('productos_list')
                        <li class="nav-item">
                            <a href="{{route('productos.index')}}" class="nav-link" id="itemProductos">
                                <p>Productos</p>
                            </a>
                        </li>
                        @endcan
                        @can('unidades_list')
                        <li class="nav-item">
                            <a href="{{route('unidades.index')}}" class="nav-link" id="itemUnidades">
                                <p>Unidades</p>
                            </a>
                        </li>
                        @endcan
                        @can('lineas_list')
                        <li class="nav-item">
                            <a href="{{ route('lineas.index') }}" class="nav-link" id="itemLineas">
                                <p>Líneas</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany
                <li class="nav-header">MÓDULOS</li>                
                @canany(['caja_pagos_list','caja_pagos_report','venta_provisionales_list','compra_provisionales_list','gastos_list'])
                <li class="nav-item" id="mnuCaja">
                    <a href="#" class="nav-link">                        
                        <x-icon name="caja" />
                        <p>
                            Caja
                            <x-icon name="chevron-right" class="nav-arrow" />
                        </p>
                    </a>
                    <ul class="nav nav-treeview">                        
                        <!--
                        @can('caja_pagos_list')
                        <li class="nav-item">
                            <a href="{{route('caja-pagos.index')}}" class="nav-link" id="itemCajaPagos">
                                <p>Caja</p>
                            </a>
                        </li>
                        @endcan
                        -->
                        
                        @can('caja_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.caja')}}" class="nav-link" id="itemReporteCaja">
                                <p>Reporte Caja</p>
                            </a>
                        </li>
                        @endcan 
                        @can('venta_provisionales_list')
                        <li class="nav-item">
                            <a href="{{route('venta-provisionales.index')}}" class="nav-link" id="itemVentaProvisionales">
                                <p>Provisional Ventas</p>
                            </a>
                        </li>
                        @endcan
                        @can('compra_provisionales_list')
                        <li class="nav-item">
                            <a href="{{route('compra-provisionales.index')}}" class="nav-link" id="itemCompraProvisionales">
                                <p>Provisional Compras</p>
                            </a>
                        </li>
                        @endcan
                        @can('gastos_list')
                        <li class="nav-item">
                            <a href="{{route('gastos.index')}}" class="nav-link" id="itemGastos">
                                <p>Gastos</p>
                            </a>
                        </li>
                        @endcan
                        @can('gastos_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.gastos.resumen')}}" class="nav-link" id="itemReporteGastos">
                                <p>Reporte Gastos</p>
                            </a>
                        </li>
                        @endcan
                        
                    </ul>
                </li>
                @endcanany
                @canany(['formulaciones_list', 'preparadas_list', 'formulaciones_report', 'preparadas_report'])
                <li class="nav-item" id="mnuProduccion">
                    <a href="#" class="nav-link">                        
                        <i class="bi bi-box-seam"></i>
                        <p>
                            Producción
                            <i class="bi bi-chevron-right nav-arrow"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('formulaciones_list')
                        <li class="nav-item">
                            <a href="{{route('formulaciones.index')}}" class="nav-link" id="itemFormulaciones">
                                <p>Formulaciones</p>
                            </a>
                        </li>
                        @endcan    
                        @can('preparadas_list')
                        <li class="nav-item">
                            <a href="{{route('preparadas.index')}}" class="nav-link" id="itemPreparadas">
                                <p>Preparadas</p>
                            </a>
                        </li>
                        @endcan
                        @can('formulaciones_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.formulaciones')}}" class="nav-link" id="itemReporteFormulaciones">
                                <p>Reporte Formulaciones</p>
                            </a>
                        </li>
                        @endcan
                        @can('preparadas_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.preparadas')}}" class="nav-link" id="itemReportePreparadas">
                                <p>Reporte Preparadas</p>
                            </a>
                        </li>
                        @endcan                   
                    </ul>
                </li>
                @endcanany
                @canany(['nucleos_list', 'nucleo_preparadas_list', 'nucleos_report'])
                <li class="nav-item" id="mnuNucleo">
                    <a href="#" class="nav-link">                        
                        <x-icon name="nucleos" />
                        <p>
                            Núcleos
                            <i class="bi bi-chevron-right nav-arrow"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('nucleos_list')
                        <li class="nav-item">
                            <a href="{{route('nucleos.index')}}" class="nav-link" id="itemNucleos">
                                <p>Núcleos</p>
                            </a>
                        </li>
                        @endcan
                        @can('nucleo_preparadas_list')
                        <li class="nav-item">
                            <a href="{{route('nucleo-preparadas.index')}}" class="nav-link" id="itemPreparacionNucleos">
                                <p>Preparación Núcleos</p>
                            </a>
                        </li>
                        @endcan
                        @can('nucleos_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.nucleo_preparadas')}}" class="nav-link" id="itemReporteNucleoPreparadas">
                                <p>Reporte Prep. Núcleos</p>
                            </a>
                        </li>
                        @endcan                    
                    </ul>
                </li>
                @endcanany
                @canany(['proveedores_list', 'compras_list','compras_report'])
                <li class="nav-item" id="mnuIngreso">
                    <a href="#" class="nav-link">                        
                        <x-icon name="cart" />
                        <p>
                            Ingresos
                            <x-icon name="chevron-right" class="nav-arrow" />
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('compras_list')
                        <li class="nav-item">
                            <a href="{{route('compras.index')}}" class="nav-link" id="itemCompras">
                                <p>Compras</p>
                            </a>
                        </li>
                        @endcan    
                        @can('proveedores_list')
                        <li class="nav-item">
                            <a href="{{route('proveedores.index')}}" class="nav-link" id="itemProveedores">
                                <p>Proveedores</p>
                            </a>
                        </li>
                        @endcan
                        @can('compras_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.compras')}}" class="nav-link" id="itemReporteCompras">
                                <p>Reporte Compras</p>
                            </a>
                        </li>
                        @endcan                      
                    </ul>
                </li>
                @endcanany
                @canany(['clientes_list', 'ventas_list', 'cotizaciones_list','ventas_report','venta_entregas_list','rentabilidad_report'])
                <li class="nav-item" id="mnuSalida">
                    <a href="#" class="nav-link">                        
                        <x-icon name="shopping-bag" />
                        <p>
                            Salidas
                            <x-icon name="chevron-right" class="nav-arrow" />
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('cotizaciones_list')
                        <li class="nav-item">
                            <a href="{{route('cotizaciones.index')}}" class="nav-link" id="itemCotizaciones">
                                <p>Cotizaciones</p>
                            </a>
                        </li>
                        @endcan 
                        @can('ventas_list')
                        <li class="nav-item">
                            <a href="{{route('ventas.index')}}" class="nav-link" id="itemVentas">
                                <p>Ventas</p>
                            </a>
                        </li>
                        @endcan 
                        @can('venta_entregas_list')
                        <li class="nav-item">
                            <a href="{{route('venta-entregas.index')}}" class="nav-link" id="itemVentaEntregas">
                                <p>Entrega Ventas</p>
                            </a>
                        </li>
                        @endcan    
                        @can('clientes_list')
                        <li class="nav-item">
                            <a href="{{route('clientes.index')}}" class="nav-link" id="itemClientes">
                                <p>Clientes</p>
                            </a>
                        </li>
                        @endcan
                        @can('ventas_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.ventas')}}" class="nav-link" id="itemReporteVentas">
                                <p>Reporte Ventas</p>
                            </a>
                        </li>
                        @endcan
                        @can('rentabilidad_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.rentabilidad')}}" class="nav-link" id="itemReporteRentabilidad">
                                <p>Reporte Rentabilidad</p>
                            </a>
                        </li>
                        @endcan                       
                    </ul>
                </li>
                @endcanany
                @canany(['prestamos_list', 'prestamos_report'])
                <li class="nav-item" id="mnuPrestamos">
                    <a href="#" class="nav-link">                        
                        <x-icon name="prestamos_devoluciones" />
                        <p>
                            Préstamo - Devolución
                            <x-icon name="chevron-right" class="nav-arrow" />
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('prestamos_list')
                        <li class="nav-item">
                            <a href="{{route('prestamos.index')}}" class="nav-link" id="itemPrestamos">
                                <p>Préstamo/Devolución</p>
                            </a>
                        </li>
                        @endcan
                        @can('prestamos_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.prestamos')}}" class="nav-link" id="itemReportePrestamos">
                                <p>Reportes Prést./Devol.</p>
                            </a>
                        </li>
                        @endcan                        
                    </ul>
                </li>
                @endcanany
                @canany(['empleados_list', 'planilla_adelantos_list', 'planilla_prestamos_list', 'planilla_pagos_list', 'planilla_report'])
                <li class="nav-item" id="mnuPlanilla">
                    <a href="#" class="nav-link">
                        <i class="bi bi-people"></i>
                        <p>
                            <strong>Planilla</strong>
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('empleados_list')
                        <li class="nav-item">
                            <a href="{{route('empleados.index')}}" class="nav-link" id="itemEmpleados">
                                <p>Empleados</p>
                            </a>
                        </li>
                        @endcan
                        @can('planilla_adelantos_list')
                        <li class="nav-item">
                            <a href="{{route('planilla-adelantos.index')}}" class="nav-link" id="itemAdelantos">
                                <p>Adelantos</p>
                            </a>
                        </li>
                        @endcan
                        @can('planilla_prestamos_list')
                        <li class="nav-item">
                            <a href="{{route('planilla-prestamos.index')}}" class="nav-link" id="itemPrestamosPlanilla">
                                <p>Préstamos</p>
                            </a>
                        </li>
                        @endcan
                        @can('planilla_pagos_list')
                        <li class="nav-item">
                            <a href="{{route('planilla-pagos.index')}}" class="nav-link" id="itemPagos">
                                <p>Pagos</p>
                            </a>
                        </li>
                        @endcan
                        @can('planilla_report')
                        <li class="nav-item">
                            <a href="{{route('reportes.planilla')}}" class="nav-link" id="itemReportes">
                                <p>Reportes</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany
                @canany(['kardex_report'])
                <li class="nav-item" id="mnuKardex">
                    <a href="#" class="nav-link">                        
                        <x-icon name="kardex" />
                        <p>
                            Kardex
                            <x-icon name="chevron-right" class="nav-arrow" />
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('kardex_report')
                        <li class="nav-item">
                            <a href="{{route('kardex')}}" class="nav-link" id="itemReporteKardex">
                                <p>Reporte Kardex</p>
                            </a>
                        </li>
                        @endcan                       
                    </ul>
                </li>
                @endcanany
                @canany(['cuenta_corriente_report'])
                <li class="nav-item" id="mnuCuentasCorriente">
                    <a href="#" class="nav-link">                        
                        <x-icon name="cuentas_corrientes" />
                        <p>
                            Cuentas CTES.
                            <x-icon name="chevron-right" class="nav-arrow" />
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('cuenta_corriente_report')
                        <li class="nav-item">
                            <a href="{{route('cuenta.corriente.cliente')}}" class="nav-link" id="itemCuentaCorrienteCliente">
                                <p>CTA CTE Clientes</p>
                            </a>
                        </li>
                        @endcan 
                        @can('cuenta_corriente_report')
                        <li class="nav-item">
                            <a href="{{route('cuenta.corriente.proveedor')}}" class="nav-link" id="itemCuentaCorrienteProveedor">
                                <p>CTA CTE Proveedores</p>
                            </a>
                        </li>
                        @endcan                       
                    </ul>
                </li>
                @endcanany
                @canany(['users_list', 'roles_permisos_list'])
                <li class="nav-item" id="mnuSeguridad">
                    <a href="#" class="nav-link">                        
                        <i class="bi bi-shield-lock-fill"></i>
                        <p>
                            Seguridad
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('roles_list')
                        <li class="nav-item">
                            <a href="{{route('roles.index')}}" class="nav-link" id="itemRoles">
                                <p>Roles</p>
                            </a>
                        </li>
                        @endcan
                        @can('users_list')
                        <li class="nav-item">
                            <a href="{{route('usuarios.index')}}" class="nav-link" id="itemUsuarios">
                                <p>Usuarios</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany
            </ul>
            <!--end::Sidebar Menu-->
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>