# DISEÑO DEL PROYECTO — CONSORCIOS VILLEGAS

## Migración a Python/PyQt6 Desktop Application

---

## 1. ESTRUCTURA DEL PROYECTO LARAVEL (WEB)

### 1.1 Archivos Principales

```
consorciov/
├── app/
│   ├── Http/Controllers/   # Controladores HTTP
│   │   ├── GastoController.php
│   │   ├── VentaController.php
│   │   ├── CompraController.php
│   │   ├── ProductoController.php
│   │   └── ... (cada módulo tiene su controlador)
│   ├── Models/              # Modelos Eloquent
│   ├── Services/            # Lógica de negocio
│   └── Exports/             # Exportaciones Excel
├── public/js/
│   └── crud.js             # Manager base JavaScript
├── resources/views/
│   ├── plantilla/           # Layout principal
│   │   ├── app.blade.php   # Estructura HTML principal
│   │   ├── header.blade.php
│   │   ├── footer.blade.php
│   │   └── menu.blade.php   # Navegación lateral
│   ├── [modulo]/           # Vistas por módulo
│   │   ├── index.blade.php # Listado DataTables
│   │   ├── action.blade.php # Modal crear/editar
│   │   ├── view.blade.php  # Vista parcial
│   │   ├── ticket.blade.php # Impresión ticket
│   │   └── partials/       # Componentes parciales
│   └── reportes/           # Reportes
├── routes/web.php
└── config/
```

---

## 2. LAYOUT PRINCIPAL

### 2.1 Estructura HTML (`plantilla/app.blade.php`)

```html
<!doctype html>
<html lang="es">
  <head>
    <!-- Scripts de tema ANTES de cargar contenido para evitar FOUC -->
    <script>
      (function() {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
      })();
    </script>

    <!-- Fuentes -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">

    <!-- Bootstrap 5 + AdminLTE + Plugins -->
    <link rel="stylesheet" href="{{asset('bootstrap-icons-1.13.1/bootstrap-icons.min.css')}}" />
    <link rel="stylesheet" href="{{asset('css/adminlte.css')}}" />
    <link rel="stylesheet" href="{{asset('datatables/dataTables.bootstrap5.css')}}">
  </head>

  <body class="layout-fixed sidebar-mini sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">

      <!-- HEADER (Navbar fijo) -->
      <header class="app-header">
        @include('plantilla.header')
      </header>

      <!-- SIDEBAR (Menú lateral fijo) -->
      <aside class="app-sidebar">
        @include('plantilla.menu')
      </aside>

      <!-- CONTENIDO PRINCIPAL -->
      <main class="app-main">
        <div class="app-content-header">
          <!-- Título de la sección -->
        </div>
        <div class="app-content">
          @yield('contenido')
        </div>
      </main>

      <!-- FOOTER -->
      <footer class="app-footer">
        @include('plantilla.footer')
      </footer>

    </div>

    <!-- Scripts -->
    <script src="{{asset('jquery-3.7.1/jquery.min.js')}}"></script>
    <script src="{{asset('bootstrap-5.3.3/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('datatables/dataTables.bootstrap5.min.js')}}"></script>
    <script src="{{asset('js/adminlte.js')}}"></script>
    <script src="{{asset('sweetalert2-11.10.8/sweetalert2.all.min.js')}}"></script>
    @stack('scripts')
  </body>
</html>
```

### 2.2 Clases CSS del Layout

| Clase | Propósito |
|-------|-----------|
| `.app-wrapper` | Contenedor principal |
| `.app-header` | Barra de navegación fija superior |
| `.app-sidebar` | Sidebar fijo izquierdo |
| `.app-main` | Área de contenido principal |
| `.app-content` | Contenedor del contenido con padding |
| `.app-content-header` | Header compacto dentro del contenido |
| `.app-footer` | Pie de página |

### 2.3 Sidebar Configuration

```html
<aside class="app-sidebar sidebar-light" data-bs-theme="light">
  <!-- Brand/logo -->
  <div class="sidebar-brand">
    <a href="/dashboard" class="brand-link">
      <img src="/assets/favicon.ico" class="brand-image" />
      <span class="brand-text">Consorcios Villegas</span>
    </a>
  </div>

  <!-- Menú de navegación -->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
        <!-- Items del menú -->
      </ul>
    </nav>
  </div>
</aside>
```

---

## 3. MENÚ DE NAVEGACIÓN COMPLETO

### 3.1 Items del Menú (con ID para estilos)

| Menú / Submenú | Ruta | Icono Bootstrap | ID CSS |
|----------------|------|-----------------|--------|
| **DASHBOARDS** | | | |
| Dashboard | `/dashboard` | `bi-speedometer` | `itemDashboard` |
| **GENERAL** | | | |
| Configuración (padre) | `#` | `bi-gear` | `mnuConfiguracion` |
| > Empresa | `#` | - | `itemEmpresa` |
| > SUNAT | `/sunat-certificados` | - | `itemSunat` |
| > Formas de Pago | `/pago-formas` | - | `itemPagoForma` |
| > Medios de Pago | `/pago-medios` | - | `itemPagoMedio` |
| > Tipo de Operación | `/operacion-tipos` | - | `itemOperacionTipo` |
| > Tipos de Comprobante | `/comprobante-tipos` | - | `itemComprobanteTipo` |
| > Tipos de Documento | `/documento-tipos` | - | `itemDocumentoTipo` |
| > Tipos de Afectación | `/afectacion-tipos` | - | `itemAfectacionTipo` |
| > Correlativos | `/comprobante-series` | - | `itemComprobanteSerie` |
| > Tipos de Cobranza | `/cobranza-tipos` | - | `itemCobranzaTipo` |
| > Tipos de Gastos | `/gasto-tipos` | - | `itemGastoTipo` |
| > Costo servicio preparada | `/configuraciones` | - | `itemCostoServicioPreparada` |
| **MÓDULOS** | | | |
| Caja (padre) | `#` | `bi-currency-dollar` | `mnuCaja` |
| > Reporte Caja | `/reportes/caja` | - | `itemReporteCaja` |
| > Provisional Ventas | `/venta-provisionales` | - | `itemVentaProvisionales` |
| > Provisional Compras | `/compra-provisionales` | - | `itemCompraProvisionales` |
| > Gastos | `/gastos` | - | `itemGastos` |
| > Reporte Gastos | `/reportes/gastos/resumen` | - | `itemReporteGastos` |
| Producción (padre) | `#` | `bi-box-seam` | `mnuProduccion` |
| > Formulaciones | `/formulaciones` | - | `itemFormulaciones` |
| > Preparadas | `/preparadas` | - | `itemPreparadas` |
| > Reporte Formulaciones | `/reportes/formulaciones` | - | `itemReporteFormulaciones` |
| > Reporte Preparadas | `/reportes/preparadas` | - | `itemReportePreparadas` |
| Nucleos (padre) | `#` | `bi-circle-square` | `mnuNucleo` |
| > Nucleos | `/nucleos` | - | `itemNucleos` |
| > Preparación Nucleos | `/nucleo-preparadas` | - | `itemPreparacionNucleos` |
| > Reporte Prep. Nucleos | `/reportes/nucleo_preparadas` | - | `itemReporteNucleoPreparadas` |
| Ingresos (padre) | `#` | `bi-cart` | `mnuIngreso` |
| > Compras | `/compras` | - | `itemCompras` |
| > Proveedores | `/proveedores` | - | `itemProveedores` |
| > Reporte Compras | `/reportes/compras` | - | `itemReporteCompras` |
| Salidas (padre) | `#` | `bi-bag` | `mnuSalida` |
| > Cotizaciones | `/cotizaciones` | - | `itemCotizaciones` |
| > Ventas | `/ventas` | - | `itemVentas` |
| > Entrega Ventas | `/venta-entregas` | - | `itemVentaEntregas` |
| > Clientes | `/clientes` | - | `itemClientes` |
| > Reporte Ventas | `/reportes/ventas` | - | `itemReporteVentas` |
| > Reporte Rentabilidad | `/reportes/rentabilidad` | - | `itemReporteRentabilidad` |
| Préstamo - Devolución (padre) | `#` | `bi-arrow-left-right` | `mnuPrestamos` |
| > Préstamo/Devolución | `/prestamos` | - | `itemPrestamos` |
| > Reportes Prést./Devol. | `/reportes/prestamos` | - | `itemReportePrestamos` |
| Planilla (padre) | `#` | `bi-people` | `mnuPlanilla` |
| > Empleados | `/empleados` | - | `itemEmpleados` |
| > Adelantos | `/planilla-adelantos` | - | `itemAdelantos` |
| > Préstamos | `/planilla-prestamos` | - | `itemPrestamosPlanilla` |
| > Pagos | `/planilla-pagos` | - | `itemPagos` |
| > Inasistencias | `/planilla-inasistencias` | - | `itemInasistencias` |
| > Reportes | `/reportes/planilla` | - | `itemReportes` |
| Kardex (padre) | `#` | `bi-list-columns` | `mnuKardex` |
| > Reporte Kardex | `/kardex` | - | `itemReporteKardex` |
| > Cuadre de Stock | `/cuadre-stock` | - | `itemCuadreStock` |
| Cuentas Corrientes (padre) | `#` | `bi-bank` | `mnuCuentasCorriente` |
| > CTA CTE Clientes | `/cuenta-corriente/cliente` | - | `itemCuentaCorrienteCliente` |
| > CTA CTE Proveedores | `/cuenta-corriente/proveedor` | - | `itemCuentaCorrienteProveedor` |
| Seguridad (padre) | `#` | `bi-shield-lock-fill` | `mnuSeguridad` |
| > Roles | `/roles` | - | `itemRoles` |
| > Usuarios | `/usuarios` | - | `itemUsuarios` |

### 3.2 Estados Activos del Menú

```javascript
// Para marcar un item como activo
document.getElementById('mnuCaja').classList.add('menu-open');
document.getElementById('itemGastos').classList.add('active');
```

---

## 4. TEMA Y COLORES

### 4.1 Paleta de Colores

**Light Mode:**
```css
--bs-blue:    #0d6efd   /* Primary */
--bs-green:   #198754   /* Success */
--bs-red:     #dc3545   /* Danger */
--bs-yellow:  #ffc107   /* Warning */
--bs-cyan:    #0dcaf0   /* Info */
--bs-light:   #f8f9fa
--bs-dark:    #212529

/* Header/Footer */
--header-bg:  #ffffff
--border-light: #eef0f2

/* Textos */
--text-primary:   #212529
--text-secondary: #58606a
```

**Dark Mode:**
```css
[data-bs-theme=dark] {
  --bs-body-color: #dee2e6
  --bs-body-bg: #212529
  --header-bg: #2a3140
}
```

### 4.2 Tipografía

**Font Family:**
```
"Source Sans 3", system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif
```

**Tamaños:**
- Base: `1rem` (16px)
- Small: `0.875rem`
- Badge: `0.75rem`
- Modal Title: `1.25rem`

**Weights:**
- Normal: 400
- Medium: 500
- Semibold: 600
- Bold: 700

### 4.3 Toggle de Tema (Header)

```javascript
document.getElementById('themeToggle').addEventListener('click', function(e) {
  e.preventDefault();
  const html = document.documentElement;
  const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);

  const icon = document.getElementById('themeIcon');
  icon.innerHTML = newTheme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
});
```

**Iconos:**
- Light mode (tema oscuro activo): `<i class="bi bi-moon"></i>`
- Dark mode (tema claro activo): `<i class="bi bi-sun"></i>`

---

## 5. ESTRUCTURA DE PÁGINAS DE MÓDULO

### 5.1 Index (Listado con DataTables)

```html
@extends('plantilla.app')

@section('contenido')
<div class="container-fluid">
  <div class="row">
    <div class="col-md-12">
      <div class="card mb-4">
        <!-- Card Header -->
        <div class="card-header d-flex align-items-center">
          <h3 class="card-title flex-grow-1">NOMBRE MÓDULO</h3>
          @can('modulo_create')
            <button type="button" class="btn btn-primary" id="btnCreate">
              <i class="bi bi-plus-circle"></i> Nuevo
            </button>
          @endcan
        </div>

        <!-- Card Body con tabla -->
        <div class="card-body">
          <div class="table-responsive">
            <table id="listadoTable" class="table table-striped table-hover table-sm">
              <thead>
                <tr>
                  <th>Opciones</th>
                  <th>Columna1</th>
                  <th>Columna2</th>
                  <!-- ... más columnas -->
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <!-- Card Footer (opcional) -->
        <div class="card-footer clearfix"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para crear/editar (incluido condicionalmente) -->
@canany(['modulo_create', 'modulo_edit'])
  @include('modulo.action')
@endcanany

<!-- Contenedor para modales dinámicos -->
<div id="modalContainer"></div>
@endsection

@push('scripts')
<script>
class ModuleManager extends CrudManager {
  // Configuración específica del módulo
}
</script>
@endpush
```

### 5.2 Action (Modal Crear/Editar)

```html
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-lg">  <!-- o modal-xl, modal-dialog -->
    <div class="modal-content">
      <form id="formUpdate" method="post">
        @csrf
        <input type="hidden" id="method_field" name="_method">

        <div class="modal-header">
          <h4 class="modal-title fs-5" id="modalTitle">Nuevo registro</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row">
            <!-- Columnas: col-lg-4 (3 campos), col-lg-6 (2 campos), col-lg-12 (1 campo) -->
            <div class="col-lg-6">
              <div class="form-group mb-3">
                <label for="campo_id" class="form-label">Etiqueta <span class="text-danger">*</span></label>
                <input type="text" id="campo_id" name="campo" class="form-control form-control-sm" required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <!-- Más columnas... -->
          </div>
        </div>

        <div class="modal-footer">
          <span class="me-auto">Usuario: <strong id="usuario_nombre"></strong></span>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="btnSubmit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
```

---

## 6. TABLAS (DataTables)

### 6.1 Configuración Común

```javascript
this.tabla = $(this.elements.table).DataTable({
  processing: true,
  serverSide: true,
  ajax: {
    url: this.baseUrl,  // Ej: "/ventas"
    type: 'GET'
  },
  columns: [
    { data: 'action', name: 'action', orderable: false, searchable: false },
    { data: 'columna1', name: 'columna1' },
    { data: 'columna2', name: 'columna2' },
    // ...
  ],
  columnDefs: [
    { targets: 0, width: '12%', className: 'text-center' },
    { targets: 1, width: '10%' },
    { targets: 2, width: '15%' },
  ],
  responsive: true,
  order: [[1, 'desc']]  // Ordenar por segunda columna, descendente
});
```

### 6.2 Columnas Típicas por Módulo

**Ventas (12 columnas):**
```
Opciones | Usuario | Fecha | Forma de Pago | Cliente | TC | Serie | Correlativo | Total | Abonos | Saldo | Estado
```

**Compras (similar a Ventas):**
```
Opciones | Fecha | Usuario | Proveedor | TC | Serie | Correlativo | Total | Abonos | Saldo | Estado
```

**Productos (8 columnas):**
```
Opciones | Unidad | Línea | Nombre | Empaque | Stock | Costo | Activo
```

**Gastos (8 columnas):**
```
Opciones | Fecha | Tipo | Usuario | Descripción | Responsable | Recibo | Monto
```

### 6.3 Ancho de Columnas Típico

| Propósito | Ancho | Alineación |
|-----------|-------|------------|
| Opciones (botones) | 10-12% | center |
| ID/Código | 5-10% | - |
| Nombres/Descripciones | 15-20% | - |
| Fechas | 10-13% | - |
| Montos/Saldos | 10% | right |
| Estados | 10% | center |

### 6.4 Estilo CSS de Tablas

```css
#listadoTable, .table-app {
  width: 100%;
  border-collapse: collapse !important;
  font-size: 0.90rem;
}

#listadoTable thead th {
  background-color: rgba(var(--bs-primary-rgb), 0.08);
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.3rem 0.45rem;
  border-bottom: 2px solid var(--bs-primary);
}

#listadoTable tbody td {
  padding: 0.28rem 0.45rem;
  border: 1px solid rgba(var(--bs-primary-rgb), 0.35);
}

#listadoTable tbody tr:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.08);
}
```

### 6.5 Botones de Acción en Tablas

```html
<!-- Editar (solo si tiene permiso) -->
@can('modulo_edit')
  <button class="btn btn-sm btn-primary" data-action="edit" data-id="{{ $row->id }}">
    <i class="bi bi-pencil"></i>
  </button>
@endcan

<!-- Ver (siempre visible) -->
<button class="btn btn-sm btn-info btn-view-venta" data-id="{{ $row->id }}">
  <i class="bi bi-eye"></i>
</button>

<!-- Eliminar (solo si tiene permiso) -->
@can('modulo_delete')
  <button class="btn btn-sm btn-danger" data-action="delete" data-id="{{ $row->id }}" data-texto="{{ $row->nombre }}">
    <i class="bi bi-trash"></i>
  </button>
@endcan

<!-- Anular (especial para ventas/compras) -->
<button class="btn btn-sm btn-danger btn-anular-venta" data-id="{{ $row->id }}">
  <i class="bi bi-x-circle"></i>
</button>

<!-- Imprimir ticket -->
<a href="{{ route('ventas.imprimir', $row->id) }}" target="_blank" class="btn btn-sm btn-secondary">
  <i class="bi bi-printer"></i>
</a>

<!-- Agrupar en btn-group -->
<div class="btn-group">
  <button class="btn btn-sm btn-primary">...</button>
  <button class="btn btn-sm btn-info">...</button>
  <button class="btn btn-sm btn-danger">...</button>
</div>
```

---

## 7. FORMULARIOS

### 7.1 Grid System

- `col-lg-4` → 3 campos por fila (en pantallas grandes)
- `col-lg-6` → 2 campos por fila
- `col-lg-12` → 1 campo por fila (campo completo)

### 7.2 Tipos de Inputs

```html
<!-- Texto -->
<input type="text" class="form-control form-control-sm">

<!-- Número -->
<input type="number" step="any" class="form-control form-control-sm">

<!-- Select -->
<select class="form-select form-select-sm"></select>

<!-- Checkbox/Switch -->
<div class="form-check form-switch">
  <input class="form-check-input" type="checkbox">
</div>

<!-- Fecha y Hora -->
<input type="datetime-local" class="form-control form-control-sm">

<!-- Solo Fecha -->
<input type="date" class="form-control form-control-sm">

<!-- Hidden -->
<input type="hidden" id="method_field" name="_method">
```

### 7.3 Estructura de Form Group

```html
<div class="form-group mb-3">
  <label for="campo_id" class="form-label">
    Etiqueta <span class="text-danger">*</span>
  </label>
  <input type="text" id="campo_id" name="campo" class="form-control form-control-sm" required>
  <div class="invalid-feedback"></div>
</div>
```

### 7.4 Live Search Select

```javascript
this.setupLiveSearchSelect({
  inputId: 'producto_nombre',
  hiddenId: 'producto_id',
  url: "{{ route('productos.buscar') }}",
  template: (item) => {
    return item.id
      ? `${item.id} - ${item.nombre} (S/ ${item.costo_unitario})`
      : `${item.nombre} (S/ ${item.costo_unitario})`;
  },
  getId: item => item.id,
  minLength: 1,
  delay: 300,
  onSelect: (item) => {
    // Callback al seleccionar
    this.addProductoToTable(item);
  }
});
```

---

## 8. BOTONES

### 8.1 Estilos

| Tipo | Clase | Uso |
|------|-------|-----|
| Primario | `btn btn-primary` | Guardar, Nuevo |
| Secundario | `btn btn-secondary` | Cancelar |
| Éxito | `btn btn-success` | Exportar Excel |
| Peligro | `btn btn-danger` | PDF, Eliminar |
| Info | `btn btn-info` | Ver detalles |
| Advertencia | `btn btn-warning` | Rectificar |
| Outline | `btn btn-outline-primary` | Acciones secundarias |

### 8.2 Tamaños

- Grande: `btn btn-lg`
- Normal: `btn`
- Pequeño: `btn btn-sm`

### 8.3 Iconos (Bootstrap Icons)

```html
<i class="bi bi-plus-circle"></i>  Nuevo
<i class="bi bi-pencil"></i>       Editar
<i class="bi bi-trash"></i>         Eliminar
<i class="bi bi-eye"></i>           Ver
<i class="bi bi-printer"></i>       Imprimir
<i class="bi bi-arrow-repeat"></i> Rectificar
<i class="bi bi-x-circle"></i>      Anular
<i class="bi bi-check-circle"></i>  Confirmar
```

---

## 9. REPORTES

### 9.1 Estructura de Reporte

```html
<!-- Filtros -->
<div class="d-flex flex-nowrap overflow-auto mb-2">
  <input type="date" id="fecha_inicio" class="form-control form-control-sm me-2">
  <input type="date" id="fecha_fin" class="form-control form-control-sm me-2">
  <button id="btnFiltrar" class="btn btn-primary btn-sm me-2">Filtrar</button>
  <a href="#" id="btnPdf" target="_blank" class="btn btn-danger btn-sm me-2">PDF</a>
  <button id="btnExportar" class="btn btn-success btn-sm">Exportar</button>
</div>

<!-- Tabla de reporte -->
<div class="table-responsive">
  <table class="table table-hover table-bordered table-striped table-sm table-app">
    <thead>
      <tr>
        <th>Columna1</th>
        <th>Columna2</th>
      </tr>
    </thead>
    <tbody>
      <!-- Con subtotales por grupo -->
    </tbody>
    <tfoot>
      <!-- Totales generales -->
    </tfoot>
  </table>
</div>
```

### 9.2 Filas de Subtotal

```html
<tr style="font-weight:bold; background:#f8f9fa;">
  <td colspan="4" class="text-end">SUBTOTAL {{ $tipoActual }}:</td>
  <td class="text-end">{{ number_format($subtotal, 2) }}</td>
  <td colspan="3"></td>
</tr>
```

### 9.3 Filas de Total General

```html
<tr style="font-weight:bold; background:#dee2e6;">
  <td colspan="4" class="text-end">TOTAL GENERAL:</td>
  <td class="text-end">{{ number_format($totalGeneral, 2) }}</td>
  <td colspan="3"></td>
</tr>
```

---

## 10. NOTIFICACIONES Y DIALOGS

### 10.1 Toast con SweetAlert2

```javascript
Swal.fire({
  icon: 'success',  // success, error, warning, info
  title: 'Mensaje de éxito',
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true
});
```

### 10.2 Confirmación de Eliminación

```javascript
Swal.fire({
  title: '¿Confirmar eliminación?',
  text: 'Esta acción no se puede deshacer.',
  icon: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#dc3545',
  cancelButtonColor: '#6c757d',
  confirmButtonText: 'Sí, eliminar',
  cancelButtonText: 'Cancelar'
}).then((result) => {
  if (result.isConfirmed) {
    // Ejecutar eliminación
  }
});
```

### 10.3 Validación de Errores en Formularios

```css
.is-invalid {
  border-color: var(--bs-form-invalid-border-color) !important;
}
.invalid-feedback {
  display: block;
  color: var(--bs-form-invalid-color);
  font-size: 0.875rem;
}
```

---

## 11. CLASE BASE CrudManager (JavaScript)

### 11.1 Métodos Públicos

```javascript
class CrudManager {
  constructor(baseUrl) { }       // Inicializar con URL base
  cacheElements() { }            // Cachear elementos DOM
  bindEvents() { }               // Vincular eventos click/submit

  // CRUD Operations
  showCreateModal() { }           // Mostrar modal vacío
  showEditModal(id) { }          // Fetch y mostrar registro
  handleSubmit(e) { }             // Submit form vía AJAX
  submitForm(formData) { }       // Fetch POST/PUT
  fetchData(url) { }              // Fetch GET

  // Delete
  confirmDelete(id, texto) { }     // SweetAlert confirmar
  deleteRecord(id) { }            // DELETE request

  // Utilities
  resetForm() { }                 // Limpiar formulario
  clearFormErrors() { }           // Quitar estilos de error
  handleFormErrors(error) { }     // Mostrar errores del servidor
  setSubmitButtonState(loading) { } // Spinner en botón

  showNotification(type, message) { } // Toast

  // Search/Select
  setupLiveSearchSelect(options) { }   // Autocomplete
  populateSelect(selectId, url, getOption) { } // Llenar select
}
```

### 11.2 Cada módulo extiende CrudManager

```javascript
class VentaManager extends CrudManager { }
class CompraManager extends CrudManager { }
class ProductoManager extends CrudManager { }
class GastoManager extends CrudManager { }
class ClienteManager extends CrudManager { }
// etc.
```

---

## 12. MÓDULOS PRINCIPALES

### 12.1 Módulo Ventas

**Index columns:** Opciones, Usuario, Fecha, Forma de Pago, Cliente, TC, Serie, Correlativo, Total, Abonos, Saldo, Estado

**Modal fields:** Cliente, Comprobante, Serie, Correlativo, Fecha, Forma de Pago, Fecha Vencimiento, Tabla Detalles (productos), Totales, Cobranza

**Special features:**
- Live search para productos con tabla de detalles
- Cálculo automático de totales (IGV, gravada, exonerada)
- Selección de unidad/empaque por producto
- Validación de stock antes de registrar
- Alerta de stock sobrepasado en registro
- Impresión de ticket PDF
- Anulación y rectificación de ventas

### 12.2 Módulo Compras

**Estructura similar a Ventas** con cambios menores:
- Proveedor en vez de Cliente
- Orden de columnas diferente

### 12.3 Módulo Productos

**Columns:** Opciones, Unidad, Línea, Nombre, Empaque, Stock Almacén, Costo Unitario, Activo

**Modal fields:** Unidad, Línea, Nombre, Código, Afectación IGV, Empaque, Stock Mínimo, Costo Unitario, Precio Lista, Activo/Inactivo

**Special features:** Múltiples fracciones (unidades de venta) por producto

### 12.4 Módulo Gastos

**Columns:** Opciones, Fecha, Tipo, Usuario, Descripción, Responsable, Recibo, Monto

**Modal fields:** Fecha, Número Interno, Descripción, Tipo (live search), Responsable, Tabla cobranza (Principal/Depósito/Consorcio)

---

## 13. MIGRACIÓN A PYTHON (Recomendaciones)

### 13.1 Biblioteca Recomendada

**PyQt6** es más moderno y completo que Tkinter. Permite:
- UI más rica y personalizable
- Mejor manejo de estilos (QSS similar a CSS)
- Gráficos profesionales con PyQtGraph
- Completo set de widgets

### 13.2 Estructura Sugerida en Python

```
consorcios_villegas/
├── main.py                 # Entry point, MainWindow
├── ui/
│   ├── main_window.py      # Layout principal (sidebar, header, content)
│   ├── widgets/
│   │   ├── sidebar.py     # Menú lateral
│   │   ├── header.py      # Barra superior
│   │   ├── table_widget.py # DataTable equivalente
│   │   └── dialog.py     # Base dialog
│   └── themes/
│       └── dark_theme.json # Paleta dark mode
├── screens/
│   ├── ventas/
│   │   ├── list_view.py
│   │   └── form_dialog.py
│   ├── compras/
│   └── ...
├── services/
│   └── api_client.py      # Cliente HTTP similar a CrudManager
└── models/
    └── data_models.py     # Modelos de datos
```

### 13.3 Paleta de Colores (Python/Qt)

**Light Theme:**
```python
LIGHT_THEME = {
    'primary': '#0d6efd',
    'success': '#198754',
    'danger': '#dc3545',
    'warning': '#ffc107',
    'info': '#0dcaf0',
    'bg_body': '#ffffff',
    'text_primary': '#212529',
    'border': '#dee2e6',
    'header_bg': '#ffffff',
}
```

**Dark Theme:**
```python
DARK_THEME = {
    'primary': '#6ea8fe',
    'success': '#75b798',
    'danger': '#ea868f',
    'warning': '#ffda6a',
    'info': '#6edff6',
    'bg_body': '#212529',
    'text_primary': '#dee2e6',
    'border': '#495057',
    'header_bg': '#2a3140',
}
```

### 13.4 Widgets Equivalentes

| Laravel/Web | PyQt6 |
|------------|-------|
| `<table>` DataTables | `QTableWidget` + modelo personalizado |
| `<div class="card">` | `QFrame` con estilo |
| Modal Bootstrap | `QDialog` |
| `<input>` | `QLineEdit` |
| `<select>` | `QComboBox` |
| `<button>` | `QPushButton` |
| SweetAlert toast | `QSystemTrayIcon` + notification |
| Live Search | `QCompleter` |
| Tabs | `QTabWidget` |
| Checkbox/Switch | `QCheckBox` / `QPushButton` con checkable |

### 13.5 API Client Pattern

```python
class ApiClient:
    def __init__(self, base_url: str):
        self.base_url = base_url

    def get_list(self, params: dict = None) -> list:
        # GET con pagination
        pass

    def get_one(self, id: int) -> dict:
        # GET single record
        pass

    def create(self, data: dict) -> dict:
        # POST
        pass

    def update(self, id: int, data: dict) -> dict:
        # PUT
        pass

    def delete(self, id: int) -> bool:
        # DELETE
        pass
```

### 13.6 Configuración de DataTable en Python

```python
class TableWidget(QTableWidget):
    def __init__(self, columns: list, parent=None):
        super().__init__(parent)
        self.columns = columns
        self.setColumnCount(len(columns))
        self.setHorizontalHeaderLabels([c['title'] for c in columns])
        self.setAlternatingRowColors(True)
        self.setSelectionBehavior(QAbstractItemView.SelectRows)
        self.horizontalHeader().setStretchLastSection(True)

        # Server-side pagination similar a DataTables
        self.page = 1
        self.page_size = 25

    def load_data(self, api_client):
        data = api_client.get_list({'page': self.page})
        self.setRowCount(len(data))
        for row_idx, row in enumerate(data):
            for col_idx, col in enumerate(self.columns):
                item = QTableWidgetItem(str(row.get(col['data'], '')))
                if col.get('align') == 'right':
                    item.setTextAlignment(Qt.AlignRight | Qt.AlignVCenter)
                elif col.get('align') == 'center':
                    item.setTextAlignment(Qt.AlignCenter | Qt.AlignVCenter)
                self.setItem(row_idx, col_idx, item)
```

---

## 14. FUNCIONALIDADES ESPECIALES A MIGRAR

### 14.1 Validación de Stock en Registro

Al registrar venta, verificar stock antes de enviar:

```python
def check_stock_alerts(self, rows: list) -> list:
    alertas = []
    for row in rows:
        cantidad = float(row['cantidad'])
        empaque = float(row['empaque'])
        stock = float(row['stock_almacen'])

        cantidad_kg = cantidad * empaque
        stock_kg = stock * empaque_base

        if cantidad_kg > stock_kg:
            alertas.append(f"Stock sobrepasado: {row['nombre']}")

    return alertas
```

### 14.2 Tema Oscuro/Claro

```python
class ThemeManager:
    def __init__(self):
        self.current_theme = 'light'

    def toggle(self):
        if self.current_theme == 'light':
            self.apply_theme('dark')
        else:
            self.apply_theme('light')

    def apply_theme(self, theme: str):
        if theme == 'dark':
            self.main_window.setStyleSheet(DARK_QSS)
        else:
            self.main_window.setStyleSheet(LIGHT_QSS)
        self.current_theme = theme
```

### 14.3 Live Search / Autocomplete

```python
class LiveSearchLineEdit(QLineEdit):
    def __init__(self, api_url: str, parent=None):
        super().__init__(parent)
        self.api_url = api_url
        completer = QCompleter()
        completer.setFilterMode(Qt.MatchContains)
        completer.activated.connect(self.on_select)
        self.setCompleter(completer)
        self.textChanged.connect(self.fetch_suggestions)

    def fetch_suggestions(self, text: str):
        if len(text) < 1:
            return
        data = requests.get(f"{self.api_url}?q={text}").json()
        model = QStringListModel([item['nombre'] for item in data])
        self.completer().setModel(model)

    def on_select(self, text: str):
        # Emit signal with selected item
        pass
```

---

## 15. RESUMEN DE CONVERSIÓN

| Elemento Web | Equivalente PyQt6 |
|-------------|-------------------|
| Bootstrap 5 + AdminLTE | Qt Stylesheets (QSS) |
| DataTables server-side | QTableWidget + modelo paginado |
| SweetAlert dialogs | QMessageBox + custom dialogs |
| Bootstrap Icons | QtAwesome o QIcon |
| Live Search | QCompleter |
| Dark/Light theme toggle | QPalette + theme manager |
| Form validation | QValidator |
| Toast notifications | QSystemTrayIcon |
| Modal dialogs | QDialog |
| Responsive layout | QSizePolicy + layouts |

---

*Documento generado automáticamente para migración del proyecto Laravel a Python/PyQt6*
*Fecha: 2026-05-12*