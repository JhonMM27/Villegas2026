# Manejo de Unidades en el Sistema

## 1. Estructura de la Tabla Unidades

### Tabla: `unidades`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `codigo` | char(3) | Código único de la unidad (PK) |
| `descripcion` | varchar(50) | Nombre descriptivo |
| `activo` | boolean | Si la unidad está activa |

### Códigos típicos de unidades

| Código | Descripción | Uso |
|--------|-------------|-----|
| `NIU` | Unidad (Normal) | Producto base |
| `KG` / `KGM` | Kilogramo | Productos重量 |
| `SC` / `SCO` | Saco | Productos en presentaciones de saco |
| `ZZ` | Servicio | Servicios (sin stock) |

---

## 2. Relación con Productos

### Modelo: `Producto`

```php
// En app/Models/Producto.php
protected $fillable = [
    'unidad_codigo',  // FK hacia unidades.codigo
    // ... otros campos
];

public function unidad()
{
    return $this->belongsTo(Unidad::class, 'unidad_codigo', 'codigo');
}
```

### Relación en Base de Datos

```
productos.unidad_codigo (FK) → unidades.codigo (PK)
```

Un producto tiene **una unidad base** (unidad_codigo) pero puede tener **múltiples fracciones** (producto_fracciones).

---

## 3. Sistema de Fracciones (Presentaciones)

### Tabla: `producto_fracciones`

Un producto puede venderse en diferentes presentaciones (fracciones):

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `producto_id` | int | FK hacia productos |
| `unidad_codigo` | char(3) | FK hacia unidades |
| `empaque` | int | Cantidad de unidades base |
| `precio_lista` | decimal | Precio de venta |
| `codigo_detalle` | int | Código interno |

### Ejemplo de fracciones

**Producto: Cemento Portland (id: 101)**
- Unidad base: `SC` (Saco) - empaque: 1
- Fracción 1: `SC` x 50 (1 saco = 50kg)
- Fracción 2: `KG` x 1 (venta por kilogramo)

---

## 4. Uso en Ventas

### Flujo de una venta

1. **Selección del producto** → El usuario busca un producto
2. **Selección de presentación** → Puede elegir la unidad base o una fracción
3. **Registro en detalle** → Se guarda `unidad_codigo` del detalle

### En VentaDetalle

```php
// En app/Services/VentaService.php
'detalle' => [
    'producto_id'       => $detalle['producto_id'],
    'producto_nombre'  => $producto->nombre,
    'unidad_codigo'    => $detalle['unidad_codigo'],  // ← Unidad del detalle
    'cantidad'         => $detalle['cantidad'],
    // ...
]
```

### Validación en Controller

```php
// En app/Http/Controllers/VentaController.php
'detalles.*.unidad_codigo' => 'required|exists:unidades,codigo',
```

---

## 5. Uso en Compras

### Flujo de una compra

1. **Selección del producto** → El usuario busca un producto
2. **Ingreso de cantidad** → Define cantidad en la unidad del proveedor
3. **Registro en detalle** → Se guarda `unidad_codigo` del detalle

### En CompraDetalle

```php
// En app/Services/CompraService.php
'detalle' => [
    'producto_id'       => $detalle['producto_id'],
    'producto_nombre'  => $producto->nombre,
    'unidad_codigo'    => $detalle['unidad_codigo'],  // ← Unidad del detalle
    'cantidad'         => $detalle['cantidad'],
    // ...
]
```

---

## 6. Uso en Kardex (Movimientos)

### Tabla: `movimientos`

El kardex registra la unidad de cada movimiento:

```php
// En app/Services/MovimientoService.php
'movimiento' => [
    'producto_id'       => $params['producto_id'],
    'producto_nombre'  => $params['producto_nombre'],
    'unidad_codigo'    => $params['unidad_codigo'] ?? $producto->unidad_codigo,
    'cantidad'         => $params['cantidad'],
    'cantidad_kg'      => $params['cantidad_kg'],
    // ...
]
```

### Cálculo de Stock

El sistema mantiene `stock_almacen` en la tabla `productos`:

- **Unidades**: Cantidad en la unidad base del producto
- **KG**: Cantidad convertida a kilogramos usando el `empaque`

---

## 7. Resumen de Relaciones

```
┌─────────────────┐       ┌──────────────────┐
│    productos    │       │     unidades     │
├─────────────────┤       ├──────────────────┤
│ id (PK)         │◄──────│ codigo (PK)      │
│ nombre          │  1:N  │ descripcion     │
│ unidad_codigo   │──────►│ activo           │
│ empaque         │       └──────────────────┘
│ stock_almacen  │
│ costo_unitario  │
└────────┬────────┘
         │
         │ 1:N
         ▼
┌──────────────────────┐
│ producto_fracciones │
├──────────────────────┤
│ producto_id (FK)    │
│ unidad_codigo (FK)  │
│ empaque            │
│ precio_lista       │
└──────────────────────┘
```

---

## 8. Casos de Uso por Módulo

### Ventas
- El usuario selecciona un producto
- Puede elegir la unidad base o una fracción
- Se registra en `venta_detalles.unidad_codigo`
- El stock se descuenta de `productos.stock_almacen`

### Compras
- El usuario selecciona un producto
- Ingresa cantidad en la unidad de compra
- Se registra en `compra_detalles.unidad_codigo`
- El stock se incrementa en `productos.stock_almacen`

### Inventario (Kardex)
- Cada movimiento registra la unidad utilizada
- Permite valorizar el stock en diferentes unidades
- El `stock_almacen` se actualiza desde MovimientoService

---

## 9. Notas Importantes

1. **Unidad base vs Fracciones**: Cada producto tiene una unidad base pero puede tener múltiples presentaciones (fracciones).

2. **Conversión de unidades**: El sistema usa el campo `empaque` para convertir entre unidades (ej: 1 saco = 50 kg).

3. **Código `ZZ`**: Reserved para servicios (no tiene stock).

4. **Integridad referencial**: La validación `exists:unidades,codigo` asegura que solo se usen unidades válidas.
