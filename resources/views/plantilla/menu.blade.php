<aside class="app-sidebar sidebar-light" data-bs-theme="light">
    <style>
        .app-sidebar .sidebar-menu .nav-item .nav-link.active > p,
        .app-sidebar .sidebar-menu .nav-item.menu-open > .nav-link > p {
            font-weight: 700;
        }

        .sidebar-search-container {
            transition: all 0.2s ease-in-out;
        }

        /* Ocultar buscador cuando el sidebar está colapsado (modo mini/responsive) */
        .sidebar-collapse .sidebar-search-container,
        body.sidebar-collapse .sidebar-search-container {
            display: none !important;
        }

        .sidebar-search-group .form-control:focus {
            box-shadow: none;
        }

        .sidebar-search-no-results {
            display: none;
            padding: 10px 14px;
            font-size: 0.82rem;
            text-align: center;
            border-radius: 6px;
            margin: 8px 12px 4px 12px;
        }
    </style>

    {{-- Sidebar Brand --}}
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="brand-link d-flex align-items-center">
            <img src="{{ asset('assets/favicon.ico') }}" alt="Sistema Logo" class="brand-image" />
            <span class="brand-text">Consorcios Villegas</span>
        </a>
    </div>

    {{-- Sidebar Search Input --}}
    <div class="sidebar-search-container px-3 pt-2 pb-1">
        <div class="input-group input-group-sm sidebar-search-group">
            <span class="input-group-text bg-body-secondary border-end-0 text-secondary">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" 
                   id="sidebarMenuSearch" 
                   class="form-control form-control-sm bg-body-secondary border-start-0 border-end-0 shadow-none ps-0" 
                   placeholder="Buscar en menú..." 
                   aria-label="Buscar en menú"
                   autocomplete="off">
            <button class="btn btn-sm bg-body-secondary border-start-0 text-secondary d-none" 
                    type="button" 
                    id="btnClearSidebarSearch"
                    title="Limpiar búsqueda">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </div>
    </div>

    {{-- Sidebar Wrapper --}}
    <div class="sidebar-wrapper">
        <div id="sidebarSearchNoResults" class="sidebar-search-no-results bg-warning-subtle text-warning-emphasis border border-warning-subtle">
            <i class="bi bi-exclamation-triangle me-1"></i> No se encontraron apartados
        </div>
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                {{-- DASHBOARD --}}
                <li class="nav-header">DASHBOARD</li>
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link" id="itemDashboard">
                        <x-icon name="gauge" class="nav-icon" />
                        <p>Dashboard</p>
                    </a>
                </li>

                {{-- ADMINISTRACIÓN --}}
                <li class="nav-header">ADMINISTRACIÓN</li>

                @canany(['empresa_list', 'sunat_list', 'pago_formas_list', 'pago_medios_list', 'operacion_tipos_list',
                    'comprobante_tipos_list', 'documento_tipos_list', 'afectacion_tipos_list', 'cobranza_tipos_list',
                    'gasto_categorias_list', 'gasto_tipos_list', 'costo_categorias_list', 'costo_tipos_list',
                    'configuraciones_list'])
                    <li class="nav-item" id="mnuConfiguracion">
                        <a href="#" class="nav-link">
                            <i class="bi bi-gear nav-icon"></i>
                            <p>
                                Configuración
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            {{-- Empresa y facturación electrónica --}}
                            @canany(['empresa_list', 'sunat_list'])
                                <li class="nav-header">EMPRESA Y SUNAT</li>

                                @can('empresa_list')
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" id="itemEmpresa">
                                            <i class="bi bi-building nav-icon"></i>
                                            <p>Empresa</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('sunat_list')
                                    <li class="nav-item">
                                        <a href="{{ route('sunat-certificados.index') }}" class="nav-link" id="itemSunat">
                                            <i class="bi bi-file-earmark-lock nav-icon"></i>
                                            <p>SUNAT</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany

                            {{-- Pagos y cobranza --}}
                            @canany(['pago_formas_list', 'pago_medios_list', 'cobranza_tipos_list'])
                                <li class="nav-header">PAGOS Y COBRANZA</li>

                                @can('pago_formas_list')
                                    <li class="nav-item">
                                        <a href="{{ route('pago-formas.index') }}" class="nav-link" id="itemPagoForma">
                                            <i class="bi bi-cash-stack nav-icon"></i>
                                            <p>Formas de Pago</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('pago_medios_list')
                                    <li class="nav-item">
                                        <a href="{{ route('pago-medios.index') }}" class="nav-link" id="itemPagoMedio">
                                            <i class="bi bi-credit-card nav-icon"></i>
                                            <p>Medios de Pago</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('cobranza_tipos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('cobranza-tipos.index') }}" class="nav-link" id="itemCobranzaTipo">
                                            <i class="bi bi-wallet2 nav-icon"></i>
                                            <p>Tipos de Cobranza</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany

                            {{-- Comprobantes y documentos --}}
                            @canany(['operacion_tipos_list', 'comprobante_tipos_list', 'documento_tipos_list',
                                'afectacion_tipos_list'])
                                <li class="nav-header">COMPROBANTES Y DOCUMENTOS</li>

                                @can('operacion_tipos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('operacion-tipos.index') }}" class="nav-link" id="itemOperacionTipo">
                                            <i class="bi bi-arrow-left-right nav-icon"></i>
                                            <p>Tipos de Operación</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('comprobante_tipos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('comprobante-tipos.index') }}" class="nav-link" id="itemComprobanteTipo">
                                            <i class="bi bi-receipt nav-icon"></i>
                                            <p>Tipos de Comprobante</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('comprobante-series.index') }}" class="nav-link"
                                            id="itemComprobanteSerie">
                                            <i class="bi bi-list-ol nav-icon"></i>
                                            <p>Correlativos</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('documento_tipos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('documento-tipos.index') }}" class="nav-link" id="itemDocumentoTipo">
                                            <i class="bi bi-person-vcard nav-icon"></i>
                                            <p>Tipos de Documento</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('afectacion_tipos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('afectacion-tipos.index') }}" class="nav-link" id="itemAfectacionTipo">
                                            <i class="bi bi-percent nav-icon"></i>
                                            <p>Tipos de Afectación</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany

                            {{-- Gastos y costos --}}
                            @canany(['gasto_categorias_list', 'gasto_tipos_list', 'costo_categorias_list',
                                'costo_tipos_list'])
                                <li class="nav-header">GASTOS Y COSTOS</li>

                                @can('gasto_categorias_list')
                                    <li class="nav-item">
                                        <a href="{{ route('gasto-categorias.index') }}" class="nav-link" id="itemGastoCategoria">
                                            <i class="bi bi-folder-minus nav-icon"></i>
                                            <p>Categorías de Gastos</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('gasto_tipos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('gasto-tipos.index') }}" class="nav-link" id="itemGastoTipo">
                                            <i class="bi bi-tags nav-icon"></i>
                                            <p>Tipos de Gastos</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('costo_categorias_list')
                                    <li class="nav-item">
                                        <a href="{{ route('costo-categorias.index') }}" class="nav-link" id="itemCostoCategoria">
                                            <i class="bi bi-folder-plus nav-icon"></i>
                                            <p>Categorías de Costos</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('costo_tipos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('costo-tipos.index') }}" class="nav-link" id="itemCostoTipo">
                                            <i class="bi bi-tags-fill nav-icon"></i>
                                            <p>Tipos de Costos</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany

                            {{-- Parámetros del sistema --}}
                            @can('configuraciones_list')
                                <li class="nav-header">PARÁMETROS</li>

                                <li class="nav-item">
                                    <a href="{{ route('configuraciones.index') }}" class="nav-link"
                                        id="itemCostoServicioPreparada">
                                        <i class="bi bi-sliders nav-icon"></i>
                                        <p>Costo servicio preparada</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['productos_list', 'unidades_list', 'lineas_list'])
                    <li class="nav-item" id="mnuCatalogo">
                        <a href="#" class="nav-link">
                            <x-icon name="package" class="nav-icon" />
                            <p>
                                Catálogos
                                <x-icon name="chevron-right" class="nav-arrow" />
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('productos_list')
                                <li class="nav-item">
                                    <a href="{{ route('productos.index') }}" class="nav-link" id="itemProductos">
                                        <i class="bi bi-box nav-icon"></i>
                                        <p>Productos</p>
                                    </a>
                                </li>
                            @endcan

                            @can('unidades_list')
                                <li class="nav-item">
                                    <a href="{{ route('unidades.index') }}" class="nav-link" id="itemUnidades">
                                        <i class="bi bi-rulers nav-icon"></i>
                                        <p>Unidades</p>
                                    </a>
                                </li>
                            @endcan

                            @can('lineas_list')
                                <li class="nav-item">
                                    <a href="{{ route('lineas.index') }}" class="nav-link" id="itemLineas">
                                        <i class="bi bi-diagram-3 nav-icon"></i>
                                        <p>Líneas</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['users_list', 'roles_list', 'roles_permisos_list'])
                    <li class="nav-item" id="mnuSeguridad">
                        <a href="#" class="nav-link">
                            <i class="bi bi-shield-lock-fill nav-icon"></i>
                            <p>
                                Seguridad
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('roles_list')
                                <li class="nav-item">
                                    <a href="{{ route('roles.index') }}" class="nav-link" id="itemRoles">
                                        <i class="bi bi-person-gear nav-icon"></i>
                                        <p>Roles</p>
                                    </a>
                                </li>
                            @endcan

                            @can('users_list')
                                <li class="nav-item">
                                    <a href="{{ route('usuarios.index') }}" class="nav-link" id="itemUsuarios">
                                        <i class="bi bi-people nav-icon"></i>
                                        <p>Usuarios</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                {{-- OPERACIONES --}}
                <li class="nav-header">OPERACIONES</li>

                @canany(['proveedores_list', 'compras_list', 'compras_report'])
                    <li class="nav-item" id="mnuIngreso">
                        <a href="#" class="nav-link">
                            <x-icon name="cart" class="nav-icon" />
                            <p>
                                Compras / Ingresos
                                <x-icon name="chevron-right" class="nav-arrow" />
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('compras_list')
                                <li class="nav-item">
                                    <a href="{{ route('compras.index') }}" class="nav-link" id="itemCompras">
                                        <i class="bi bi-cart-plus nav-icon"></i>
                                        <p>Compras</p>
                                    </a>
                                </li>
                            @endcan

                            @can('proveedores_list')
                                <li class="nav-item">
                                    <a href="{{ route('proveedores.index') }}" class="nav-link" id="itemProveedores">
                                        <i class="bi bi-truck nav-icon"></i>
                                        <p>Proveedores</p>
                                    </a>
                                </li>
                            @endcan

                            @can('compras_report')
                                <li class="nav-item">
                                    <a href="{{ route('reportes.compras') }}" class="nav-link" id="itemReporteCompras">
                                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                        <p>Reporte Compras</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['clientes_list', 'ventas_list', 'cotizaciones_list', 'ventas_report', 'venta_entregas_list',
                    'rentabilidad_report'])
                    <li class="nav-item" id="mnuSalida">
                        <a href="#" class="nav-link">
                            <x-icon name="shopping-bag" class="nav-icon" />
                            <p>
                                Ventas / Salidas
                                <x-icon name="chevron-right" class="nav-arrow" />
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('cotizaciones_list')
                                <li class="nav-item">
                                    <a href="{{ route('cotizaciones.index') }}" class="nav-link" id="itemCotizaciones">
                                        <i class="bi bi-file-earmark-text nav-icon"></i>
                                        <p>Cotizaciones</p>
                                    </a>
                                </li>
                            @endcan

                            @can('ventas_list')
                                <li class="nav-item">
                                    <a href="{{ route('ventas.index') }}" class="nav-link" id="itemVentas">
                                        <i class="bi bi-bag-check nav-icon"></i>
                                        <p>Ventas</p>
                                    </a>
                                </li>
                            @endcan

                            @can('venta_entregas_list')
                                <li class="nav-item">
                                    <a href="{{ route('venta-entregas.index') }}" class="nav-link" id="itemVentaEntregas">
                                        <i class="bi bi-box-seam nav-icon"></i>
                                        <p>Entrega Ventas</p>
                                    </a>
                                </li>
                            @endcan

                            @can('clientes_list')
                                <li class="nav-item">
                                    <a href="{{ route('clientes.index') }}" class="nav-link" id="itemClientes">
                                        <i class="bi bi-person-lines-fill nav-icon"></i>
                                        <p>Clientes</p>
                                    </a>
                                </li>
                            @endcan

                            @can('ventas_report')
                                <li class="nav-item">
                                    <a href="{{ route('reportes.ventas') }}" class="nav-link" id="itemReporteVentas">
                                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                        <p>Reporte Ventas</p>
                                    </a>
                                </li>
                            @endcan

                            @can('rentabilidad_report')
                                <li class="nav-item">
                                    <a href="{{ route('reportes.rentabilidad') }}" class="nav-link"
                                        id="itemReporteRentabilidad">
                                        <i class="bi bi-graph-up-arrow nav-icon"></i>
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
                            <x-icon name="prestamos_devoluciones" class="nav-icon" />
                            <p>
                                Préstamos / Devoluciones
                                <x-icon name="chevron-right" class="nav-arrow" />
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('prestamos_list')
                                <li class="nav-item">
                                    <a href="{{ route('prestamos.index') }}" class="nav-link" id="itemPrestamos">
                                        <i class="bi bi-arrow-left-right nav-icon"></i>
                                        <p>Préstamo / Devolución</p>
                                    </a>
                                </li>
                            @endcan

                            @can('prestamos_report')
                                <li class="nav-item">
                                    <a href="{{ route('reportes.prestamos') }}" class="nav-link" id="itemReportePrestamos">
                                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                        <p>Reporte Prést. / Devol.</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                {{-- PRODUCCIÓN --}}

                @canany(['formulaciones_list', 'preparadas_list', 'formulaciones_report', 'preparadas_report'])
                    <li class="nav-header">PRODUCCIÓN</li>
                    <li class="nav-item" id="mnuProduccion">
                        <a href="#" class="nav-link">
                            <i class="bi bi-box-seam nav-icon"></i>
                            <p>
                                Producción
                                <i class="bi bi-chevron-right nav-arrow"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('formulaciones_list')
                                <li class="nav-item">
                                    <a href="{{ route('formulaciones.index') }}" class="nav-link" id="itemFormulaciones">
                                        <i class="bi bi-journal-text nav-icon"></i>
                                        <p>Formulaciones</p>
                                    </a>
                                </li>
                            @endcan

                            @can('preparadas_list')
                                <li class="nav-item">
                                    <a href="{{ route('preparadas.index') }}" class="nav-link" id="itemPreparadas">
                                        <i class="bi bi-box2-heart nav-icon"></i>
                                        <p>Preparadas</p>
                                    </a>
                                </li>
                            @endcan

                            @can('formulaciones_report')
                                <li class="nav-item">
                                    <a href="{{ route('reportes.formulaciones') }}" class="nav-link"
                                        id="itemReporteFormulaciones">
                                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                        <p>Reporte Formulaciones</p>
                                    </a>
                                </li>
                            @endcan

                            @can('preparadas_report')
                                <li class="nav-item">
                                    <a href="{{ route('reportes.preparadas') }}" class="nav-link"
                                        id="itemReportePreparadas">
                                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                        <p>Reporte Preparadas</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                {{-- NUTRICIÓN --}}
                @canany(['ration_datos_list', 'formulas_alimento_list', 'formulas_alimento_cerdo_list'])
                    <li class="nav-header">NUTRICIÓN</li>
                    <li class="nav-item" id="mnuNutricion">
                        <a href="#" class="nav-link">
                            <i class="bi bi-heart-pulse nav-icon"></i>
                            <p>
                                Nutrición Animal
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('ration_datos_list')
                                <li class="nav-item">
                                    <a href="{{ route('ration-formulation.datos') }}" class="nav-link" id="itemRationDatos">
                                        <i class="bi bi-table nav-icon"></i>
                                        <p>Datos Nutricionales</p>
                                    </a>
                                </li>
                            @endcan

                            @can('formulas_alimento_list')
                                <li class="nav-item">
                                    <a href="{{ route('formulas-alimento.index') }}" class="nav-link" id="itemFormulasAlimento">
                                        <i class="bi bi-calculator nav-icon"></i>
                                        <p>Fórmulas de Alimento (Vacunos)</p>
                                    </a>
                                </li>
                            @endcan

                            @can('formulas_alimento_cerdo_list')
                                <li class="nav-item">
                                    <a href="{{ route('formulas-alimento-cerdo.index') }}" class="nav-link" id="itemFormulasAlimentoCerdo">
                                        <i class="bi bi-piggy-bank nav-icon"></i>
                                        <p>Fórmulas de Alimento (Cerdos)</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['nucleos_list', 'nucleo_preparadas_list', 'nucleos_report'])
                    <li class="nav-header">NÚCLEOS</li>
                    <li class="nav-item" id="mnuNucleo">
                        <a href="#" class="nav-link">
                            <x-icon name="nucleos" class="nav-icon" />
                            <p>
                                Núcleos
                                <i class="bi bi-chevron-right nav-arrow"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('nucleos_list')
                                <li class="nav-item">
                                    <a href="{{ route('nucleos.index') }}" class="nav-link" id="itemNucleos">
                                        <i class="bi bi-boxes nav-icon"></i>
                                        <p>Núcleos</p>
                                    </a>
                                </li>
                            @endcan

                            @can('nucleo_preparadas_list')
                                <li class="nav-item">
                                    <a href="{{ route('nucleo-preparadas.index') }}" class="nav-link"
                                        id="itemPreparacionNucleos">
                                        <i class="bi bi-tools nav-icon"></i>
                                        <p>Preparación Núcleos</p>
                                    </a>
                                </li>
                            @endcan

                            @can('nucleos_report')
                                <li class="nav-item">
                                    <a href="{{ route('reportes.nucleo_preparadas') }}" class="nav-link"
                                        id="itemReporteNucleoPreparadas">
                                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                        <p>Reporte Prep. Núcleos</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                {{-- FINANZAS --}}

                @canany(['caja_pagos_list', 'caja_ingresos_list', 'caja_report', 'venta_provisionales_list', 'compra_provisionales_list',
                    'gastos_list', 'costos_list', 'costos_report'])
                    <li class="nav-header">FINANZAS</li>
                    <li class="nav-item" id="mnuCaja">
                        <a href="#" class="nav-link">
                            <x-icon name="caja" class="nav-icon" />
                            <p>
                                Caja y Provisionales
                                <x-icon name="chevron-right" class="nav-arrow" />
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            {{-- 
                            @can('caja_pagos_list')
                                <li class="nav-item">
                                    <a href="{{ route('caja-pagos.index') }}" class="nav-link" id="itemCajaPagos">
                                        <i class="bi bi-cash-coin nav-icon"></i>
                                        <p>Caja</p>
                                    </a>
                                </li>
                            @endcan
                            --}}

                            @can('caja_ingresos_list')
                                <li class="nav-item">
                                    <a href="{{ route('caja-ingresos.index') }}" class="nav-link" id="itemCajaIngresos">
                                        <i class="bi bi-plus-circle-fill nav-icon"></i>
                                        <p>Ingresos Caja</p>
                                    </a>
                                </li>
                            @endcan

                            @can('caja_report')
                                <li class="nav-item">
                                    <a href="{{ route('reportes.caja') }}" class="nav-link" id="itemReporteCaja">
                                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                        <p>Reporte Caja</p>
                                    </a>
                                </li>
                            @endcan

                            @can('venta_provisionales_list')
                                <li class="nav-item">
                                    <a href="{{ route('venta-provisionales.index') }}" class="nav-link"
                                        id="itemVentaProvisionales">
                                        <i class="bi bi-receipt-cutoff nav-icon"></i>
                                        <p>Provisional Ventas</p>
                                    </a>
                                </li>
                            @endcan

                            @can('compra_provisionales_list')
                                <li class="nav-item">
                                    <a href="{{ route('compra-provisionales.index') }}" class="nav-link"
                                        id="itemCompraProvisionales">
                                        <i class="bi bi-receipt nav-icon"></i>
                                        <p>Provisional Compras</p>
                                    </a>
                                </li>
                            @endcan

                            @can('gastos_list')
                                <li class="nav-header">GASTOS</li>

                                <li class="nav-item">
                                    <a href="{{ route('gastos.index') }}" class="nav-link" id="itemGastos">
                                        <i class="bi bi-arrow-down-circle nav-icon"></i>
                                        <p>Gastos</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('reportes.gastos.resumen') }}" class="nav-link"
                                        id="itemReporteGastos">
                                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                        <p>Reporte Gastos</p>
                                    </a>
                                </li>
                            @endcan

                            @canany(['costos_list', 'costos_report'])
                                <li class="nav-header">COSTOS</li>

                                @can('costos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('costos.index') }}" class="nav-link" id="itemCostos">
                                            <i class="bi bi-arrow-up-circle nav-icon"></i>
                                            <p>Costos</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('costos_report')
                                    <li class="nav-item">
                                        <a href="{{ route('reportes.costos.index') }}" class="nav-link" id="itemReporteCostos">
                                            <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                            <p>Reporte Costos</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany
                        </ul>
                    </li>
                @endcanany

                @can('cuenta_corriente_report')
                    <li class="nav-item" id="mnuCuentasCorriente">
                        <a href="#" class="nav-link">
                            <x-icon name="cuentas_corrientes" class="nav-icon" />
                            <p>
                                Cuentas Corrientes
                                <x-icon name="chevron-right" class="nav-arrow" />
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('cuenta.corriente.cliente') }}" class="nav-link"
                                    id="itemCuentaCorrienteCliente">
                                    <i class="bi bi-person-vcard nav-icon"></i>
                                    <p>CTA CTE Clientes</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('cuenta.corriente.proveedor') }}" class="nav-link"
                                    id="itemCuentaCorrienteProveedor">
                                    <i class="bi bi-truck nav-icon"></i>
                                    <p>CTA CTE Proveedores</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                {{-- INVENTARIO --}}

                @canany(['kardex_report', 'cuadre_stock_list'])
                    <li class="nav-header">INVENTARIO</li>
                    <li class="nav-item" id="mnuKardex">
                        <a href="#" class="nav-link">
                            <x-icon name="kardex" class="nav-icon" />
                            <p>
                                Kardex y Stock
                                <x-icon name="chevron-right" class="nav-arrow" />
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('kardex_report')
                                <li class="nav-item">
                                    <a href="{{ route('kardex') }}" class="nav-link" id="itemReporteKardex">
                                        <i class="bi bi-journal-check nav-icon"></i>
                                        <p>Reporte Kardex</p>
                                    </a>
                                </li>
                            @endcan

                            @can('cuadre_stock_list')
                                <li class="nav-item">
                                    <a href="{{ route('cuadre-stock.index') }}" class="nav-link" id="itemCuadreStock">
                                        <i class="bi bi-clipboard-check nav-icon"></i>
                                        <p>Cuadre de Stock</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                {{-- RECURSOS HUMANOS --}}

                @canany(['empleados_list', 'planilla_adelantos_list', 'planilla_prestamos_list', 'planilla_pagos_list',
                    'planilla_inasistencias_list', 'planilla_report', 'empleado_vacaciones_list', 'planilla_list'])
                    <li class="nav-header">RECURSOS HUMANOS</li>
                    <li class="nav-item" id="mnuPlanilla">
                        <a href="#" class="nav-link">
                            <i class="bi bi-people nav-icon"></i>
                            <p>
                                Planilla
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('empleados_list')
                                <li class="nav-item">
                                    <a href="{{ route('empleados.index') }}" class="nav-link" id="itemEmpleados">
                                        <i class="bi bi-person-badge nav-icon"></i>
                                        <p>Empleados</p>
                                    </a>
                                </li>
                            @endcan

                            @canany(['planilla_adelantos_list', 'planilla_prestamos_list'])
                                <li class="nav-header">ADELANTOS Y PRÉSTAMOS</li>

                                @can('planilla_adelantos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('planilla-adelantos.index') }}" class="nav-link" id="itemAdelantos">
                                            <i class="bi bi-cash-stack nav-icon"></i>
                                            <p>Adelantos</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('planilla_prestamos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('planilla-prestamos.index') }}" class="nav-link"
                                            id="itemPrestamosPlanilla">
                                            <i class="bi bi-cash-coin nav-icon"></i>
                                            <p>Préstamos</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany

                            @canany(['planilla_pagos_list', 'planilla_inasistencias_list', 'empleado_vacaciones_list'])
                                <li class="nav-header">CONTROL LABORAL</li>

                                @can('planilla_pagos_list')
                                    <li class="nav-item">
                                        <a href="{{ route('planilla-pagos.index') }}" class="nav-link" id="itemPagos">
                                            <i class="bi bi-wallet2 nav-icon"></i>
                                            <p>Pagos</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('planilla_inasistencias_list')
                                    <li class="nav-item">
                                        <a href="{{ route('planilla-inasistencias.index') }}" class="nav-link"
                                            id="itemInasistencias">
                                            <i class="bi bi-calendar-x nav-icon"></i>
                                            <p>Inasistencias</p>
                                        </a>
                                    </li>
                                @endcan

                                @can('empleado_vacaciones_list')
                                    <li class="nav-item">
                                        <a href="{{ route('empleado-vacaciones.index') }}" class="nav-link" id="itemVacaciones">
                                            <i class="bi bi-calendar-heart nav-icon"></i>
                                            <p>Vacaciones</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany

                            @can('planilla_report')
                                <li class="nav-header">REPORTES</li>

                                <li class="nav-item">
                                    <a href="{{ route('reportes.planilla') }}" class="nav-link" id="itemReportes">
                                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                                        <p>Reportes</p>
                                    </a>
                                </li>
                            @endcan

                            {{-- ASISTENCIA BIOMÉTRICA (deshabilitado temporalmente)
                            <li class="nav-header">ASISTENCIA BIOMÉTRICA</li>

                            <li class="nav-item">
                                <a href="{{ route('zkteco.marcaciones.view') }}" class="nav-link"
                                    id="itemZktecoMarcaciones">
                                    <i class="bi bi-clock-history nav-icon"></i>
                                    <p>Marcaciones</p>
                                </a>
                            </li>
                            --}}
                        </ul>
                    </li>
                @endcanany

            </ul>
        </nav>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('sidebarMenuSearch');
    const clearBtn = document.getElementById('btnClearSidebarSearch');
    const noResultsMsg = document.getElementById('sidebarSearchNoResults');
    const sidebarMenu = document.querySelector('.sidebar-menu');

    if (!searchInput || !sidebarMenu) return;

    let isSearching = false;
    let initialOpenItems = new Set();

    function normalizeText(str) {
        return (str || '')
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .trim();
    }

    function saveInitialState() {
        initialOpenItems.clear();
        sidebarMenu.querySelectorAll('li.nav-item.menu-open').forEach(li => {
            initialOpenItems.add(li);
        });
    }

    function filterMenu() {
        const query = normalizeText(searchInput.value);

        if (query.length > 0) {
            if (!isSearching) {
                saveInitialState();
                isSearching = true;
            }
            clearBtn.classList.remove('d-none');
        } else {
            clearBtn.classList.add('d-none');
            if (isSearching) {
                restoreInitialState();
            }
            return;
        }

        // 1. Resetear visibilidad de todos los nav-item, nav-treeview y remover menu-open
        const allItems = sidebarMenu.querySelectorAll('li.nav-item');
        allItems.forEach(item => {
            item.style.display = 'none';
            item.classList.remove('menu-open');
        });

        const allTreeviews = sidebarMenu.querySelectorAll('ul.nav-treeview');
        allTreeviews.forEach(tv => {
            tv.style.display = 'none';
        });

        let totalMatches = 0;

        // 2. Buscar coincidencias en los textos de los nav-link <p>
        const allLinks = sidebarMenu.querySelectorAll('a.nav-link');
        allLinks.forEach(link => {
            const pEl = link.querySelector('p');
            if (!pEl) return;

            // Extraer solo los nodos de texto principales (omitiendo flechas e iconos de badge)
            const textContent = Array.from(pEl.childNodes)
                .filter(node => node.nodeType === Node.TEXT_NODE)
                .map(node => node.textContent)
                .join(' ') || pEl.innerText;

            const normalizedText = normalizeText(textContent);

            if (normalizedText.includes(query)) {
                totalMatches++;
                const item = link.closest('li.nav-item');
                if (!item) return;

                // Mostrar el ítem encontrado
                item.style.display = 'block';

                // Si el ítem encontrado es un menú padre que contiene submenús (ul.nav-treeview),
                // mostrar TODOS sus sub-ítems hijos para que se vean desplegados en la lista
                if (item.querySelector('ul.nav-treeview')) {
                    item.classList.add('menu-open');
                    item.querySelectorAll('ul.nav-treeview').forEach(tv => tv.style.display = 'block');
                    item.querySelectorAll('li.nav-item').forEach(childLi => childLi.style.display = 'block');
                }

                // Subir por el árbol de ancestros para abrir e integrar los menús contenedores padres
                let parentLi = item.parentElement ? item.parentElement.closest('li.nav-item') : null;
                while (parentLi && sidebarMenu.contains(parentLi)) {
                    parentLi.style.display = 'block';
                    parentLi.classList.add('menu-open');
                    const parentTv = parentLi.querySelector('ul.nav-treeview');
                    if (parentTv) {
                        parentTv.style.display = 'block';
                    }
                    parentLi = parentLi.parentElement ? parentLi.parentElement.closest('li.nav-item') : null;
                }
            }
        });

        // 3. Visibilidad de los encabezados de sección (nav-header)
        const headers = sidebarMenu.querySelectorAll('li.nav-header');
        headers.forEach(header => {
            let hasVisibleChild = false;
            let sibling = header.nextElementSibling;

            while (sibling && !sibling.classList.contains('nav-header')) {
                if (sibling.classList.contains('nav-item') && sibling.style.display === 'block') {
                    hasVisibleChild = true;
                    break;
                }
                sibling = sibling.nextElementSibling;
            }

            header.style.display = hasVisibleChild ? 'block' : 'none';
        });

        // 4. Mostrar u ocultar mensaje "Sin resultados"
        if (noResultsMsg) {
            noResultsMsg.style.display = (totalMatches === 0) ? 'block' : 'none';
        }
    }

    function restoreInitialState() {
        isSearching = false;

        // Restaurar visibilidad de todos los elementos
        sidebarMenu.querySelectorAll('li.nav-item, li.nav-header').forEach(el => {
            el.style.display = '';
        });

        // Restaurar estado previo de los menús colapsables
        sidebarMenu.querySelectorAll('li.nav-item').forEach(li => {
            const treeview = li.querySelector('ul.nav-treeview');
            if (initialOpenItems.has(li)) {
                li.classList.add('menu-open');
                if (treeview) treeview.style.display = 'block';
            } else {
                li.classList.remove('menu-open');
                if (treeview) treeview.style.display = '';
            }
        });

        if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    }

    // Event Listeners
    searchInput.addEventListener('input', filterMenu);

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        filterMenu();
        searchInput.focus();
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            searchInput.value = '';
            filterMenu();
        }
    });
});
</script>
