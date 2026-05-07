# Módulo Planilla - Documentación

## 1. Estructura de Vistas

```
resources/views/plantilla/
├── empleados/
│   ├── index.blade.php          # Lista de empleados
│   └── action.blade.php         # Modal crear/editar empleado
├── adelantos/
│   ├── index.blade.php          # Lista de adelantos
│   ├── action.blade.php         # Modal crear/editar adelanto
│   └── ticket.blade.php         # Ticket de recibo
├── prestamos/
│   ├── index.blade.php           # Lista de préstamos
│   ├── action.blade.php         # Modal crear/editar préstamo
│   ├── pago_ticket.blade.php    # Ticket de pago
│   ├── pagos_index.blade.php    # Lista de pagos del préstamo
│   └── ticket.blade.php         # Ticket del préstamo
├── pagos/
│   ├── index.blade.php           # Lista de pagos de planilla
│   └── action.blade.php         # Modal crear/editar pago
├── inasistencias/
│   ├── index.blade.php          # Lista de inasistencias
│   └── action.blade.php         # Modal crear/editar inasistencia
├── reportes/
│   ├── index.blade.php          # Panel de reportes (2 pestañas)
│   ├── empleado.blade.php       # Reporte individual PDF
│   ├── empleados_pdf.blade.php  # Reporte general empleados
│   ├── inasistencias_pdf.blade.php
│   ├── adelantos.blade.php      # Reporte adelantos
│   ├── prestamos.blade.php      # Reporte préstamos
│   ├── trabajadores_pdf.blade.php
│   ├── trabajadores_sueldo_pdf.blade.php
│   ├── pagos_pendientes.blade.php
│   └── mensual.blade.php
└── partials/
    └── caja-distribution.blade.php  # Distribución de caja (Principal/Depósito/Consorcio)
```

---

## 2. Modelos

| Modelo | Tabla | Descripción |
|--------|-------|-------------|
| `Empleado` | `empleados` | Datos del trabajador (nombre, DNI, sueldo_planilla, sueldo_real, estado) |
| `PlanillaAdelanto` | `planilla_adelantos` | Adelantos de dinero a empleados |
| `PlanillaPrestamo` | `planilla_prestamos` | Préstamos otorgados a empleados (con saldo_pendiente) |
| `PlanillaPrestamoPago` | `planilla_prestamo_pagos` | Pagos realizados sobre un préstamo |
| `PlanillaPago` | `planilla_pagos` | Pagos mensuales de planilla |
| `PlanillaPagoDetalle` | `planilla_pago_detalles` | Detalles adicionales del pago |
| `PlanillaInasistencia` | `planilla_inasistencias` | Registro de inasistencias |
| `PlanillaAsistencia` | `planilla_asistencias` | Asistencias registradas |

---

## 3. Módulos y su Funcionamiento

### 3.1 Empleados (`/planilla-empleados`)

**Datos:**
- Nombre, DNI, Teléfono, Correo
- Sueldo Planilla (referencial)
- Sueldo Real (calculado para pagos)
- Estado: activo / inactivo

**Operaciones:** Crear, Editar, Ver Detalle, Eliminar

---

### 3.2 Adelantos (`/planilla-adelantos`)

**Funcionamiento:**
- Cada adelanto tiene: numero_interno, empleado_id, monto, fecha, observaciones
- **Distribución de caja obligatoria:** `importe_p` (Principal), `importe_d` (Depósito), `importe_c` (Consorcio)
- La suma de distribución NO puede superar el monto total
- El campo `numero_interno` es numérico para control interno

**Flujo:**
1. Seleccionar empleado (búsqueda live-search por nombre o DNI)
2. Ingresar número interno, fecha y monto
3. Distribuir el monto en caja (por defecto el monto completo va a Principal)
4. Guardar

---

### 3.3 Préstamos (`/planilla-prestamos`)

**Funcionamiento:**
- Cada préstamo tiene: numero_interno, empleado_id, monto_original, saldo_pendiente, fecha_prestamo, estado
- Estados: activo, pagado, anulado
- **Sistema de pagos parciales:** cada pago reduce el saldo_pendiente
- El préstamo inicia con `saldo_pendiente = monto_original`

**Flujo:**
1. Crear préstamo con monto original → saldo_pendiente se inicializa igual
2. Registrar pagos desde el modal de detalle → cada pago reduce saldo_pendiente
3. Cuando saldo_pendiente = 0, el préstamo se marca como "pagado"

**Pagos:**
- Se registran con: numero_interno, monto_pagado, fecha_pago, observaciones
- Desde la vista de detalle se puede: Ver Todos los Pagos, Registrar Nuevo Pago

---

### 3.4 Pagos de Planilla (`/planilla-pagos`)

**Funcionamiento:**
- Generados por mes/año
- Cada pago tiene: empleado_id, mes, anio, sueldo_base, horas_extras, descuento_faltas, total_pagar
- El sueldo_base se calcula en base al período seleccionado

**Cálculo del total_pagar:**
```
total_pagar = sueldo_base + horas_extras - descuento_faltas
```

**sueldo_base se obtiene de:**
- Sueldo real del empleado para el mes
- Resta descuentos por inasistencias
- Se calcula vía API `planilla-pagos.disponible`

**Validaciones:**
- No se puede pagar dos veces el mismo mes/año para el mismo empleado
- La distribución de caja no puede superar el total_pagar
- Los campos sueldo_planilla y sueldo_real son readonly (traídos del empleado)

**Generación masiva:**
- Botón "Generar Pagos del Mes" → crea pagos pendientes para todos los empleados activos
- Botón "Confirmar Pagos del Mes" → marca todos los pendientes como pagados

---

### 3.5 Inasistencias (`/planilla-inasistencias`)

**Funcionamiento:**
- Registro diario: empleado_id, fecha, medio_dia (boolean), observacion
- Si `medio_dia = true` se cuenta como 0.5 días faltados
- La inasistencia afecta el cálculo del pago en `PlanillaPago`

**Cálculo descuento_faltas:**
```
descuento = dias_faltados * (sueldo_real / 30)
```

---

## 4. Búsqueda de Empleados

El módulo de Planilla utiliza un sistema de **live-search** para seleccionar empleados:

### Implementación en vistas:

**Método antiguo (empleado en adelantos, prestamos, inasistencias):**
```javascript
setupLiveSearch() {
    // Busca en route('empleados.buscar')?q={query}
    // Renderiza manualmente un <ul class="search-list">
}
```

**Método moderno (pagos - usa CrudManager):**
```javascript
this.setupLiveSearchSelect({
    inputId: 'empleado_nombre',
    hiddenId: 'empleado_id',
    url: "{{ route('empleados.buscar') }}",
    template: (item) => `${item.nombre} (DNI: ${item.dni})`,
    getId: item => item.id,
    minLength: 1,
    delay: 300,
    onSelect: (item) => this.onEmpleadoSelected(item)
});
```

### Ruta para búsqueda
```
GET /empleados/buscar?q={texto}
```

---

## 5. Distribución de Caja

Todos los módulos de planilla usan el componente parcial `planilla.partials.caja-distribution`:

```blade
@include('planilla.partials.caja-distribution')
```

**Campos:**
| Campo | ID | Descripción |
|-------|-----|-------------|
| Principal | `principal` | Importe hacia cuenta principal |
| Depósito | `deposito` | Importe hacia depósito |
| Consortium | `consorcio` | Importe hacia cuenta consorcio |
| Total | `total_caja` | Suma de los tres (validado contra monto total) |

**Validación:** `total_caja <= monto_del_modulo` (mostrado en rojo si excede)

---

## 6. Reportes

### 6.1 Reportes por Empleado (`reportes.planilla.empleado_pdf`)
- Seleccionar empleado + rango de fechas
- Genera PDF individual con toda la info del empleado
- Botón "Descargar PDFs" → descarga ZIP con PDFs de todos los empleados del período

### 6.2 Reportes Generales

| Reporte | Ruta | Descripción |
|---------|------|-------------|
| Adelantos | `reportes.planilla.adelantos` | Adelantos por período |
| Préstamos | `reportes.planilla.prestamos` | Estado de préstamos |
| Pagos Pendientes | `reportes.planilla.pagos_pendientes` | Empleados con pagos sin confirmar |
| Trabajadores | `reportes.planilla.trabajadores` | Listado de empleados |
| Trabajadores c/ Sueldo | `reportes.planilla.trabajadores_sueldo` | Empleados con información salarial |
| Inasistencias | `reportes.planilla.inasistencias` | Resumen de inasistencias |

---

## 7. Permissions (Laravel Gates/Policies)

```
empleados_list
empleados_create
empleados_edit

planilla_adelantos_list
planilla_adelantos_create

planilla_prestamos_list
planilla_prestamos_create

planilla_pagos_list
planilla_pagos_create
planilla_pagos_edit

planilla_inasistencias_list
planilla_inasistencias_create

planilla_report
```

---

## 8. Notas Importantes

### 8.1 Préstamos - Cálculo de Saldo
- El `saldo_pendiente` se calcula manualmente al hacer cada pago
- No se auto-actualiza desde el modelo, el controller recibe el monto_pagado y calcula nuevo saldo
- El estado "pagado" se asigna manualmente cuando saldo_pendiente llega a 0

### 8.2 Pagos - Relación con Adelantos
- Los adelantos NO se restan automáticamente del pago
- El usuario debe considerar el adelantoy afecta el "disponible" manualmente al procesar el pago

### 8.3 Inasistencias - Medio Día
- El checkbox `medio_dia` indica que solo fue media jornada
- Se debe usar en el cálculo del descuento_faltas multiplicando por 0.5

### 8.4 Distribución de Caja
- Todos los módulos (adelantos, prestamos, pagos) usan la misma distribución de caja
- La validación es: `total_caja <= monto_total_del_modulo`
- El campo `total_caja` es readonly y se recalcula en tiempo real

---

## 9. Rutas Principales

```
empleados.index         → GET  /planilla-empleados
empleados.store         → POST /planilla-empleados
empleados.update        → PUT  /planilla-empleados/{id}
empleados.destroy       → DELETE /planilla-empleados/{id}
empleados.buscar        → GET  /empleados/buscar?q=

planilla-adelantos.index → GET  /planilla-adelantos
planilla-prestamos.index → GET  /planilla-prestamos
planilla-pagos.index    → GET  /planilla-pagos
planilla-pagos.generar  → POST /planilla-pagos/generar
planilla-pagos.disponible → GET /planilla-pagos/disponible?empleado_id=&mes=&anio=

reportes.planilla        → GET  /reportes/planilla
reportes.planilla.empleado_pdf → GET /reportes/planilla/empleado/pdf
```