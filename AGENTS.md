# AGENTS.md - Guidelines for AI Agents

This file provides guidelines for AI agents working in this Laravel project.

## Project Overview

- **Framework**: Laravel 12.0
- **PHP Version**: ^8.2
- **Database**: MySQL (villegas2026)
- **Frontend**: Blade templates + TailwindCSS 4.0 + Vite

## 1. Build / Lint / Test Commands

### Running the Application

```bash
# Start development server
php artisan serve

# Start Vite dev server
npm run dev

# Run both concurrently
composer run dev
```

### Testing

```bash
# Run all tests
php artisan test

# Run a single test file
php artisan test tests/Unit/ExampleTest.php

# Run a single test method
php artisan test --filter=testMethodName

# Run tests with coverage (if installed)
php artisan test --coverage
```

### Code Quality

```bash
# Clear all caches
php artisan optimize:clear

# Run Laravel Pint (code style fixer)
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test
```

### Database

```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Rollback and re-migrate
php artisan migrate:fresh --seed
```

### Frontend Build

```bash
# Build assets for production
npm run build
```

## 2. Code Style Guidelines

### PHP Standards

- Follow PSR-12 coding standards
- Use PHP 8.2+ features (typed properties, named arguments, readonly classes)
- Use strict typing: `declare(strict_types=1);` at the top of PHP files

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Models | PascalCase | `Compra`, `VentaDetalle` |
| Controllers | PascalCase + Controller suffix | `CompraController` |
| Services | PascalCase + Service suffix | `MovimientoService` |
| Tables | snake_case plural | `compras`, `venta_detalles` |
| Columns | snake_case | `fecha_compra`, `producto_id` |
| Methods | camelCase | `createCompra()`, `registrarIngreso()` |
| Variables | camelCase | `$compraData`, `$detallesCreados` |
| Constants | UPPER_SNAKE_CASE | `TIPO_COMPRA`, `TIPO_VENTA` |

### File Structure

```
app/
├── Http/Controllers/   # HTTP layer
├── Models/            # Eloquent models
├── Services/          # Business logic
├── Exports/           # Excel exports
└── View/Components/   # Blade components
```

### Imports

- Use fully qualified class names
- Group imports: Laravel contracts, external packages, app classes
- Sort alphabetically within groups

```php
use App\Models\Compra;
use App\Models\Proveedor;
use App\Services\MovimientoService;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
```

### Controllers

- Use dependency injection in constructor
- Keep controllers thin; delegate to Services
- Use request validation methods
- Return JSON responses for AJAX

```php
class CompraController extends Controller
{
    public function __construct(
        protected CompraService $compraService
    ) {
        $this->middleware('can:compras_list')->only(['index', 'view']);
    }
}
```

### Services (Business Logic)

- One service per domain (CompraService, VentaService, MovimientoService)
- Use transactions for multi-step operations
- Throw exceptions with meaningful messages

```php
public function createCompra(array $data, string $comprobanteTipoCodigo, ...): Compra
{
    return DB::transaction(function () use ($data, $comprobanteTipoCodigo, ...) {
        // Business logic here
    });
}
```

### Models

- Define `$table`, `$fillable`, `$casts`, `$hidden`
- Define relationships using method syntax
- Use scopes for common queries

```php
class Compra extends Model
{
    protected $table = 'compras';
    
    protected $fillable = ['user_id', 'proveedor_id', 'total', ...];
    
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
```

### Routes (web.php)

- Use Route::resource for CRUD operations
- Exclude unused methods: `->except(['create', 'edit', 'update', 'destroy'])`
- Group routes with middleware

```php
Route::middleware(['auth'])->group(function () {
    Route::resource('compras', CompraController::class)
        ->except(['create', 'edit', 'update', 'destroy']);
});
```

### Error Handling

- Use try-catch for operations that may fail
- Return meaningful error messages
- Use appropriate HTTP status codes

```php
try {
    $compra = $this->compraService->createCompra(...);
    return response()->json(['success' => true, 'compra_id' => $compra->id]);
} catch (\Exception $e) {
    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
}
```

### Database Transactions

Always wrap multi-step database operations in transactions:

```php
DB::transaction(function () use ($data) {
    $compra = Compra::create($data);
    $compra->detalles()->createMany($data['detalles']);
    // More operations...
});
```

### Blade Templates

- Use component syntax for reusable UI
- Use `@csrf` for forms
- Use route() helper for URLs

### Comments

- Use docblocks for public methods in Services/Controllers
- Avoid unnecessary comments; let code be self-documenting
- Comment complex business logic

### Key Services

- **MovimientoService**: Handles all inventory (kardex) operations
  - `registrarIngreso()`: Records purchase/input transactions
  - `registrarSalida()`: Records sale/output transactions
  - Uses Cost Average (CPP) for valuation

### Known Constraints

- compras, ventas, prepared records are immutable (update/destroy disabled)
- Use anulación + rectificación instead of edit/delete

### Common Issues to Avoid

1. Don't add update/destroy methods to controllers unless explicitly needed
2. Don't bypass MovimientoService when modifying stock
3. Always use `$fillable` to protect mass assignment
4. Use database transactions for operations that modify multiple tables

## 3. Kardex / Inventory Movements

### Core Concept

The kardex (inventory ledger) is maintained via the `movimientos` table. Every stock change is recorded as a `Movimiento` with `stock_anterior` and `stock_nuevo` to maintain a complete chain. All inventory operations MUST go through `MovimientoService` to preserve chain integrity.

### Key Methods

- `MovimientoService::registrarIngreso()` - Records inputs (purchases, prepared outputs)
- `MovimientoService::registrarSalida()` - Records outputs (sales, prepared inputs)
- `MovimientoService::recalcularKardexProducto($productoId, $desdeMovimientoId)` - Recalculates from a movement ID (orders by id)
- `MovimientoService::recalcularKardexProductoDesdeFecha($productoId, $fechaDesde)` - Recalculates from a date (orders by fecha, id)
- `MovimientoService::recalcularKardexExcluyendo($productoId, $excluirMovIds)` - Recalculates neutralizing excluded movements

### Retroactive Movement Detection (CRITICAL)

`registrarIngreso()` and `registrarSalida()` automatically detect if the new movement's `fecha` is BEFORE the last existing movement for the product. If so:

1. The movement is created normally
2. `productos.stock_almacen` is NOT updated directly
3. `recalcularKardexProductoDesdeFecha()` is called automatically to recalculate the entire chain in chronological order

This prevents the "backdated movement" bug where creating a movement with a past fecha would otherwise use the current `stock_almacen` (which already includes future movements) as the `stock_anterior`, corrupting the chain.

### Diagnostic & Repair Commands

```bash
# Validate kardex chain integrity (detects broken chains)
php artisan kardex:validar [--producto_id=29]

# Recalculate kardex for a product from a specific date
php artisan kardex:recalcular {producto_id} {fecha}

# Fix apertura (opening stock) for specific products (CALCIO=29, SAL=39, BICARBONATO=43)
php artisan kardex:corregir-apertura
```

### Rectification Flow

`NucleoPreparadaService::rectificarNucleoPreparada()` (and `PreparadaService::rectificarPreparada()`) use `recalcularKardexProductoDesdeFecha()` when the rectification date is in the past. This is critical because:

- `recalcularKardexProducto()` orders by `id` (insertion order)
- `recalcularKardexProductoDesdeFecha()` orders by `fecha, id` (chronological order)

When rectifying a past-dated record, chronological order MUST be used to maintain chain integrity.

### Stock Units

- `productos.stock_almacen` is stored in **SACOS** (bags/packages), NOT kilograms
- `nucleo_preparada_detalles.salida_kg` is misleadingly named — it actually stores **SACOS** for these products (despite the column name)
- Conversion: `sacos = kg / empaque` where `empaque` is per-product (e.g., CALCIO=50kg/saco, BICARBONATO=25kg/saco)

### When Discrepancies Appear

If a user reports stock discrepancies between the Laravel system and an old system:

1. **Run `kardex:validar`** to detect chain breaks
2. **Check for rectificaciones** — nucleo_preparadas with `estado = 'rectificada'` may explain differences (the old report may show pre-rectification values)
3. **Check rounding** — Laravel uses 4 decimals, old systems may use 2 decimals (differences of 0.005-0.03 per day are normal)
4. **DO NOT manually adjust `stock_almacen`** — this breaks the chain. Use `kardex:recalcular` instead.
