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
