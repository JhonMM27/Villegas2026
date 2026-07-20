# Reconciliacion de apertura del kardex - 31/05/2026

El archivo autoritativo es `database/data/kardex_apertura_20260531.csv`. Contiene 95 productos fisicos: 78 saldos del sistema anterior y 17 productos confirmados en cero. El servicio de mezclado (ID 77) esta excluido.

## 1. Preflight de solo lectura

```bash
php artisan kardex:reconciliar-apertura database/data/kardex_apertura_20260531.csv --fecha=2026-05-31 --dry-run
```

El resultado esperado sobre la base auditada es 95 aperturas y 29 saldos finales con cambio. Este modo no escribe movimientos ni productos.

## 2. Aplicacion controlada

1. Obtener un respaldo consistente de la base de datos.
2. Pausar temporalmente ventas, compras, preparadas, nucleos, prestamos y cuadres.
3. Ejecutar:

```bash
php artisan kardex:reconciliar-apertura database/data/kardex_apertura_20260531.csv --fecha=2026-05-31 --apply
```

Toda la operacion se ejecuta en una transaccion. Si ocurre un error, se revierte completa. El comando es idempotente y reutiliza los movimientos `APERTURA_LEGADO` existentes.

## 3. Verificacion

```bash
php artisan kardex:validar
```

Generar tambien el reporte de stock al 31/05/2026 y comprobarlo contra el PDF V. El comando antiguo `kardex:corregir-apertura` esta deshabilitado porque utilizaba una apertura incompleta.

El indice opcional para acelerar recorridos cronologicos esta en `database/sql/idx_movimientos_producto_fecha_id.sql`. No se instala automaticamente.
