# Model Factories en Cronos Framework

> **AVISO CRÍTICO PARA DESARROLLADORES E INTELIGENCIAS ARTIFICIALES (IAs)**
>
> Las Factories de Cronos están inspiradas en la ergonomía de Laravel, pero **operan bajo la arquitectura nativa de Cronos Framework**.
> - **NUNCA uses namespaces de Illuminate** (`Illuminate\Database\Eloquent\Factories\*`).
> - Usa exclusivamente las clases de Cronos: `Cronos\Model\Factory` y el trait `Cronos\Model\HasFactory`.
> - La persistencia interactúa directamente con el ORM de Cronos: respeta la regla de que los campos en `$fillable` son requeridos para la inserción en base de datos.
> - Cuando generas múltiples registros (`count(N)`), el resultado es una instancia de [`Cronos\Model\ModelCollection`](../../System/Model/ModelCollection.php), NO una colección de Illuminate ni un array estándar.

---

## 1. ¿Qué es una Model Factory?

Una **Model Factory** es una clase especializada encargada de generar registros de prueba o sembrado estructurados con valores por defecto para tus modelos ORM. 

Son fundamentales para:
1. **Pruebas Automatizadas (`tests/`)**: Crear usuarios, pedidos o publicaciones en memoria o en la base de datos sin tener que escribir arrays gigantescos en cada prueba.
2. **Database Seeders (`App/Seeders/`)**: Poblar tablas masivamente con datos consistentes.

---

## 2. Generar una Factory con la Consola CLI

Usa el generador nativo de la consola de Cronos:

```bash
# Crea App/Factories/UserFactory.php
php cronos make:factory UserFactory

# Sufijo automático si se omite:
php cronos make:factory User

# Con subcarpeta (App/Factories/Auth/UserFactory.php):
php cronos make:factory User Auth
```

---

## 3. Estructura de una Factory

Cada Factory extiende de [`Cronos\Model\Factory`](../../System/Model/Factory.php) e implementa el método `definition()`:

```php
<?php

declare(strict_types=1);

namespace App\Factories;

use App\Models\User;
use Cronos\Model\Factory;

class UserFactory extends Factory
{
    /**
     * El modelo de Cronos asociado a esta Factory.
     */
    protected string $model = User::class;

    /**
     * Define los atributos predeterminados del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Usuario de Prueba',
            'correo' => 'test_' . uniqid() . '@ejemplo.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'rol' => 'cliente',
            'activo' => true,
        ];
    }

    /**
     * Estado para usuarios administradores.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'administrador',
        ]);
    }

    /**
     * Estado para usuarios inactivos o bloqueados.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
```

---

## 4. Habilitar Factories en el Modelo (`HasFactory`)

Para poder invocar la factory directamente desde tu modelo con la sintaxis fluida `Modelo::factory()`, simplemente añade el trait [`Cronos\Model\HasFactory`](../../System/Model/HasFactory.php):

```php
<?php

namespace App\Models;

use Cronos\Model\Model;
use Cronos\Model\HasFactory;

class User extends Model
{
    use HasFactory;

    protected string $table = 'usuarios';
    protected string $primaryKey = 'id';

    // Recuerda: en Cronos, los atributos fillable son requeridos en create()
    protected array $fillable = [
        'nombre',
        'correo',
        'password',
        'rol',
        'activo',
    ];
}
```

> **Resolución por Convención:**
> Por defecto, el trait `HasFactory` busca la clase de la factory en:
> 1. `App\Factories\{NombreModelo}Factory`
> 2. `Database\Factories\{NombreModelo}Factory`

---

## 5. Métodos de Generación: `make()` vs `create()` vs `raw()`

### A. `make()` — Generación en Memoria (Sin Base de Datos)
Crea una o más instancias del modelo **sin guardarlas en la base de datos**. Es extremadamente rápido y se recomienda para probar validaciones, recursos API o vistas.

```php
// Una sola instancia (App\Models\User)
$usuario = User::factory()->make();
echo $usuario->nombre; // 'Usuario de Prueba'

// Sobrescribiendo atributos específicos:
$usuario = User::factory()->make([
    'nombre' => 'Carlos Tucno',
    'rol' => 'moderador',
]);
```

### B. `create()` — Persistencia en Base de Datos
Crea una o más instancias y las **inserta directamente en la base de datos** usando `Model::create()`.

```php
// Inserta una fila en MySQL y retorna la instancia con su ID generado:
$usuario = User::factory()->create([
    'correo' => 'carlos@dominio.com',
]);

echo $usuario->id; // ID asignado por MySQL
```

### C. `raw()` — Retorno de Array Asociativo
Retorna los datos generados directamente como un `array` asociativo, sin instanciar la clase del modelo. Es ideal para simular peticiones HTTP en pruebas (`$this->post('/api/usuarios', $data)`).

```php
$datosPeticion = User::factory()->raw([
    'password' => 'secret123',
]);

// $datosPeticion es un array PHP nativo
```

---

## 6. Generación Múltiple con `count(N)`

Cuando usas `count(N)` con un número mayor a 1, tanto `make()` como `create()` retornan una instancia de [`Cronos\Model\ModelCollection`](../../System/Model/ModelCollection.php):

```php
// Crea 10 usuarios en memoria:
$usuarios = User::factory()->count(10)->make();

// Crea 5 administradores en base de datos:
$admins = User::factory()->count(5)->admin()->create();

// ModelCollection implementa Countable e IteratorAggregate:
echo count($admins); // 5

foreach ($admins as $admin) {
    echo $admin->correo . PHP_EOL;
}

// Convertir toda la colección a array asociativo:
$array = $admins->toArray();
```

---

## 7. Modificadores de Estado (`state()`)

Los estados te permiten definir variantes frecuentes de un modelo de forma reutilizable y limpia:

```php
// Aplicar estado con un método definido en la Factory:
$admin = User::factory()->admin()->make();

// Aplicar un estado dinámico en línea pasando un array o closure:
$premium = User::factory()
    ->state(['plan' => 'enterprise', 'creditos' => 500])
    ->create();
```

---

## 8. Uso en Pruebas Automatizadas y Seeders

### En Pruebas Funcionales (`tests/Feature/` o `tests/Unit/`):
```php
namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase\OrmTestCase;

class UserApiTest extends OrmTestCase
{
    public function test_usuario_puede_consultar_su_perfil(): void
    {
        // 1. Crear usuario con Factory
        $user = User::factory()->create([
            'rol' => 'usuario',
        ]);

        // 2. Probar endpoint
        $response = $this->withSession(['user_id' => $user->id])
                         ->get('/api/perfil');

        $response->assertOk()
                 ->assertJson(['correo' => $user->correo]);
    }
}
```

### En Seeders (`App/Seeders/DatabaseSeeder.php`):
```php
namespace App\Seeders;

use App\Models\User;
use Cronos\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Administrador principal garantizado:
        User::factory()->admin()->create([
            'correo' => 'admin@empresa.com',
            'nombre' => 'Administrador General',
        ]);

        // 20 usuarios falsos para pruebas de carga y paginación:
        User::factory()->count(20)->create();
    }
}
```
