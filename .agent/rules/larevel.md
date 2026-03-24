---
trigger: always_on
---

````markdown
# 📏 Backend Development Rules & Standards (Laravel 12)

> **Arquitectura:** MVC + Service Pattern
> **Enfoque:** Thin Controllers (Controladores Delgados), Fat Services (Servicios Robustos).

Este documento define los estándares de codificación y la arquitectura para el desarrollo del Backend. El objetivo es mantener el código limpio, escalable y desacoplado, centralizando toda la lógica de negocio en la capa de **Servicios**.

---

## 1. 🏗️ Arquitectura General

El flujo de datos debe seguir estrictamente este orden:
`Ruta` -> `Request/Validation` -> `Controller` -> `Service` -> `Model/Database`

### Principios Fundamentales

1.  **Controllers:** Solo manejan la entrada (HTTP Request), validación básica y la respuesta (HTTP Response). **Nunca** deben contener lógica de negocio.
2.  **Services:** Contienen TODA la lógica de negocio, cálculos, transacciones de base de datos y llamadas a APIs externas.
3.  **Models:** Solo definen la estructura de la tabla, relaciones, scopes y accessors/mutators.
4.  **Dependency Injection:** Se debe usar inyección de dependencias para llamar a los Servicios dentro de los Controladores.

---

## 2. 🚦 Rutas (Routes)

- **Ubicación:** `routes/api.php` (o `web.php` según corresponda).
- **Regla:** Las rutas no deben contener lógica (Closures). Deben apuntar siempre a un método de un Controlador.
- **Nombres:** Usar `kebab-case` para las URIs y `name()` para nombrar las rutas.

**✅ Correcto:**

```php
Route::get('/users', [UserController::class, 'index'])->name('users.index');
```
````

**❌ Incorrecto:**

```php
Route::get('/users', function() {
    return User::all(); // NO lógica en rutas
});

```

---

## 3. 🎮 Controladores (Controllers)

Los controladores actúan como "policías de tráfico". Reciben la petición y delegan el trabajo.

- **Responsabilidades:**
- Validar datos (preferiblemente inyectando FormRequests).
- Invocar al método correspondiente del **Service**.
- Retornar una respuesta (JSON Response, View, Redirect).

- **Prohibido:**
- Escribir consultas Eloquent complejas (ej. `User::where(...)->get()`).
- Realizar cálculos matemáticos o lógicos.

**Ejemplo:**

```php
class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function store(StoreProductRequest $request)
    {
        // El controlador solo pasa los datos validados al servicio
        $product = $this->productService->createProduct($request->validated());

        return response()->json($product, 201);
    }
}

```

---

## 4. ⚙️ Servicios (Services)

Aquí reside el núcleo de la aplicación.

- **Ubicación:** `app/Services/{NombreDelModulo}/`.
- **Estructura:** Clases PHP simples (POPO) o clases con interfaces si se requiere polimorfismo.
- **Responsabilidades:**
- Interactuar con Eloquent Models.
- Manejar Transacciones de Base de Datos (`DB::transaction`).
- Enviar correos, notificaciones o llamar APIs de terceros.
- Lanzar Excepciones si algo falla.

- **Retorno:** Deben retornar datos (Modelos, Arrays, Booleans), nunca respuestas HTTP (como `response()->json()`).

**Ejemplo:**

```php
namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // Lógica de negocio aquí
            $product = Product::create([
                'name' => $data['name'],
                'price' => $data['price'],
                'sku'   => $this->generateSku($data['name']),
            ]);

            // Otras acciones de negocio
            if (isset($data['tags'])) {
                $product->tags()->sync($data['tags']);
            }

            return $product;
        });
    }

    private function generateSku(string $name): string
    {
        // Lógica privada auxiliar
        return strtoupper(substr($name, 0, 3)) . '-' . rand(1000, 9999);
    }
}

```

---

## 5. 🗃️ Modelos (Models)

- **Responsabilidades:**
- `$fillable` o `$guarded`.
- Relaciones (`hasMany`, `belongsTo`, etc.).
- Casts (`'is_active' => 'boolean'`).
- Scopes (`scopeActive($query)`).

- **Prohibido:** Lógica que involucre múltiples modelos o flujos de negocio complejos.

---

## 6. 📝 Naming Conventions

| Elemento        | Convención              | Ejemplo                                   |
| --------------- | ----------------------- | ----------------------------------------- |
| **Controlador** | PascalCase + Controller | `UserController`, `AuthSessionController` |
| **Servicio**    | PascalCase + Service    | `UserService`, `PaymentGatewayService`    |
| **Modelo**      | PascalCase (Singular)   | `User`, `ProductCategory`                 |
| **Tabla DB**    | snake_case (Plural)     | `users`, `product_categories`             |
| **Método**      | camelCase               | `store`, `calculateTotalRevenue`          |
| **Variables**   | camelCase               | `$userData`, `$totalAmount`               |

---

## 7. 🛡️ Validación y Manejo de Errores

- **Validación:** Usar siempre **FormRequests** (`php artisan make:request`) para validaciones de `store` y `update`. Evitar `$request->validate([])` dentro del método del controlador para mantenerlo limpio.
- **Errores:** Los Servicios deben lanzar Excepciones (`throw new Exception`), no retornar errores como strings o arrays. El Controlador (o el Handler global) captura la excepción y decide qué código HTTP devolver.

---

## 8. ✨ PHP & Laravel 12 Features

- **Tipado Estricto:** Usar siempre tipos de retorno y tipos en argumentos.
- `public function index(): JsonResponse`
- `public function findUser(int $id): User`

- **Constructor Property Promotion:** Usar la sintaxis corta en constructores para la inyección de servicios.

```php
// ✅ Correcto
public function __construct(protected UserService $userService) {}

```
