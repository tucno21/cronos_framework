# Migraciones, Schema Builder y Seeders

> **AVISO IMPORTANTE PARA DESARROLLADORES E IAs**
>
> Cronos NO es Laravel. Aunque la sintaxis de migraciones se parece a Eloquent/Artisan, **no debe suponerse que Laravel funciona igual**. Los nombres de metodos son similares a proposito, pero el comportamiento, el alcance y las limitaciones son propios de Cronos.
>
> **Si eres una IA (LLM/agente): NO importes conocimiento de Laravel ni de otro framework. Basate UNICAMENTE en esta documentacion y en el codigo fuente de `System/Database/`. Ante la duda, verifica en el codigo, no en tu memoria de Laravel.**

---

## 1. Arquitectura del sistema

Todo vive en `System/Database/`:

| Archivo | Responsabilidad |
|---|---|
| `Schema.php` | Facade estatico: `Schema::create()`, `Schema::table()`, `Schema::drop()`, `Schema::dropIfExists()`, `Schema::hasTable()`, `Schema::hasColumn()` |
| `Blueprint.php` | Define columnas/indices/llaves de una tabla y genera el SQL (dialecto **MySQL/MariaDB**) |
| `ColumnDefinition.php` | Definicion fluida de una columna (`->nullable()`, `->default()`, etc.) |
| `ForeignKeyDefinition.php` | Definicion de llave foranea (`->references()`, `->on()`, `->onDelete()`) |
| `Migrator.php` | Ejecuta/revierte migraciones y mantiene el historial en la tabla `migrations` |
| `Migration.php` | Clase base abstracta (`up()` / `down()`) |
| `Seeder.php` | Clase base para seeders (provee `$this->pdo()` y `$this->call()`) |
| `DatabaseMigrate.php` | Conexion PDO por consola: lee el `.env`, conecta al servidor y **crea la base de datos si no existe** |

Los archivos del proyecto viven en:

```
App/
├── Migrations/    # migraciones timestamped, una por archivo
│   ├── 2026_09_03_000001_create_users_table.php
│   └── ...
└── Seeders/       # seeders del proyecto
    ├── DatabaseSeeder.php
    └── UserSeeder.php
```

---

## 2. Comandos de consola

Todos se ejecutan desde la raiz del proyecto con `php cronos <comando>`:

| Comando | Que hace |
|---|---|
| `php cronos make:migration create_users_table` | Genera `App/Migrations/YYYY_MM_DD_HHMMSS_create_users_table.php` con stub `up()/down()` |
| `php cronos make:seeder UserSeeder` | Genera `App/Seeders/UserSeeder.php` (agrega el sufijo `Seeder` si falta) |
| `php cronos migrate` | Ejecuta **solo las migraciones pendientes**, en orden de nombre de archivo, agrupadas en un lote (`batch`) |
| `php cronos migrate:rollback` | Revierte el ultimo lote ejecutando `down()` de cada migracion en orden inverso |
| `php cronos migrate:rollback 3` | Revierte los ultimos 3 lotes |
| `php cronos migrate:status` | Tabla con cada migracion y su lote, o `Pendiente` |
| `php cronos migrate:fresh` | **ELIMINA TODAS las tablas** de la base de datos y vuelve a ejecutar todas las migraciones (destructivo) |
| `php cronos migrate:refresh` | Rollback de todos los lotes + migrate (destructivo para datos) |
| `php cronos db:seed` | Ejecuta `App/Seeders/DatabaseSeeder.php` |

Detalles importantes del generador `make:migration`:

- El nombre debe ir en `snake_case`: letras minusculas, numeros y `_`.
- Si el nombre sigue el patron `create_NOMBRE_table`, el stub rellena automaticamente el nombre de tabla. Ejemplo: `create_products_table` genera `Schema::create('products', ...)` y `Schema::dropIfExists('products')`.
- Si el nombre no sigue el patron, el stub queda con el placeholder `{{table}}` y debes editarlo a mano.
- Si ya existe otra migracion con el mismo nombre (cualquier timestamp), da error y no sobreescribe.

---

## 3. Estructura de una migracion

Cada archivo retorna una **clase anonima** que extiende `Cronos\Database\Migration`. NO usa clases con nombre ni namespaces.

```php
<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function ($table) {
            $table->id();
            $table->string('name', 150);
            $table->decimal('price', 8, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

Reglas:

1. `up()` crea o modifica; `down()` debe deshacer EXACTAMENTE lo que `up()` hizo (necesario para `migrate:rollback`).
2. El orden de ejecucion es el **orden alfabetico del nombre de archivo** (por eso el prefijo timestamp). Las migraciones de tablas padre deben ir antes que las de tablas hijas.
3. Cada migracion se ejecuta de forma independiente: si una falla, las anteriores del lote quedan aplicadas y registradas.
4. Al ejecutarse, la clase debe retornar una instancia de `Migration`; si el archivo retorna otra cosa, `Migrator` lanza `RuntimeException`.

### Historial: la tabla `migrations`

`Migrator` crea y mantiene automaticamente una tabla `migrations`:

| Columna | Tipo | Descripcion |
|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | Orden de insercion |
| `migration` | VARCHAR(255) UNIQUE | Nombre del archivo sin `.php` |
| `batch` | INT | Numero de lote: `migrate` toma el ultimo lote y suma 1 |

- `migrate` ejecuta los archivos que **no** estan en la tabla y los registra en un nuevo lote.
- `migrate:rollback` revierte **solo el ultimo lote** (o los N ultimos con el argumento `steps`).
- `migrate:fresh` elimina todas las tablas (incluida `migrations`) y empieza de cero en el lote 1.

---

## 4. Schema Builder

`Schema` es un facade estatico. La conexion PDO la inyecta `Migrator` al ejecutar por consola. Si usas `Schema` fuera de la consola (por ejemplo en tests), primero llama `Schema::setPDO($pdo)` o recibiras un `RuntimeException`.

| Metodo | Accion |
|---|---|
| `Schema::create('tabla', fn($t) => ...)` | `CREATE TABLE` |
| `Schema::table('tabla', fn($t) => ...)` | `ALTER TABLE` (agregar/eliminar columnas o indices) |
| `Schema::drop('tabla')` | `DROP TABLE` (falla si no existe) |
| `Schema::dropIfExists('tabla')` | `DROP TABLE IF EXISTS` |
| `Schema::hasTable('tabla')` | `bool` (consulta `INFORMATION_SCHEMA`) |
| `Schema::hasColumn('tabla', 'col')` | `bool` (consulta `SHOW COLUMNS`) |

### 4.1 Tipos de columna disponibles

| Metodo Blueprint | SQL MySQL generado |
|---|---|
| `$table->id('id')` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY` |
| `$table->increments('id')` | `INT UNSIGNED ... AUTO_INCREMENT PRIMARY KEY` |
| `$table->bigIncrements('id')` | Igual que `id()` |
| `$table->string('name', 100)` | `VARCHAR(100)` (default 255) |
| `$table->char('x', 36)` | `CHAR(36)` |
| `$table->text('x')` | `TEXT` |
| `$table->mediumText('x')` | `MEDIUMTEXT` |
| `$table->longText('x')` | `LONGTEXT` |
| `$table->integer('x')` | `INT` |
| `$table->tinyInteger('x')` | `TINYINT` |
| `$table->smallInteger('x')` | `SMALLINT` |
| `$table->bigInteger('x')` | `BIGINT` |
| `$table->unsignedInteger('x')` | `INT UNSIGNED` |
| `$table->unsignedBigInteger('x')` | `BIGINT UNSIGNED` |
| `$table->boolean('x')` | `TINYINT(1) NOT NULL DEFAULT 0` |
| `$table->decimal('x', 8, 2)` | `DECIMAL(8, 2)` |
| `$table->float('x')` / `double('x')` | `DOUBLE` |
| `$table->date('x')` | `DATE` |
| `$table->dateTime('x')` | `DATETIME` |
| `$table->time('x')` | `TIME` |
| `$table->timestamp('x')` | `TIMESTAMP` |
| `$table->json('x')` | `JSON` |
| `$table->uuid('x')` | `CHAR(36)` |
| `$table->enum('x', ['a', 'b'])` | `ENUM('a', 'b')` |
| `$table->foreignId('user_id')` | `BIGINT UNSIGNED` (para FK) |
| `$table->rememberToken()` | `VARCHAR(100) NULL` con nombre `remember_token` |
| `$table->timestamps()` | `created_at TIMESTAMP NULL` + `updated_at TIMESTAMP NULL` |
| `$table->softDeletes('deleted_at')` | `deleted_at TIMESTAMP NULL` |

### 4.2 Modificadores de columna

Encadenables sobre cualquier columna (retornan la misma columna):

```php
$table->string('email')->unique();              // UNIQUE KEY `tabla_email_unique`
$table->string('phone')->nullable();            // permite NULL
$table->integer('views')->default(0);           // DEFAULT 0
$table->string('bio')->nullable()->default(null);
$table->foreignId('user_id')->constrained();    // FK hacia tabla derivada
$table->integer('x')->unsigned();               // UNSIGNED
$table->string('code')->index();                // KEY `tabla_code_index`
```

Modificador `constrained()`: deriva la tabla referenciada quitando el sufijo `_id` y pluralizando (`user_id` -> `users`, `category_id` -> `categories`). La columna referenciada es siempre `id`. Luego puedes encadenar `->cascadeOnDelete()` o `->nullOnDelete()`.

Para control total usa FK manual:

```php
$table->foreignId('post_id');
$table->foreign('post_id')
    ->references('uuid')
    ->on('posts')
    ->onDelete('cascade')
    ->onUpdate('cascade');
```

### 4.3 Indices y llaves a nivel de tabla

```php
$table->primary(['post_id', 'tag_id']);        // PRIMARY KEY compuesta
$table->unique(['country', 'phone']);          // UNIQUE KEY compuesta
$table->index('status');                       // KEY normal

$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
```

Nombres de indices generados automaticamente: `{tabla}_{columna}_unique`, `{tabla}_{columna}_index`, `{tabla}_{columna}_foreign` (igual convencion visual que Laravel, pero generada por `Blueprint` de Cronos).

### 4.4 Modificar tablas (`Schema::table`)

```php
Schema::table('users', function ($table) {
    $table->string('phone', 50)->nullable();   // ADD COLUMN
    $table->index('phone');                    // ADD KEY
    $table->dropColumn('legacy', 'old_field'); // DROP COLUMN (varias a la vez)
});
```

Nota: `Schema::table` solo agrega columnas/indices o elimina columnas. **No renombra ni modifica tipos de columna existentes** (para eso crea una migracion con SQL especifico usando el PDO, ver limitaciones).

---

## 5. Ejemplo real del proyecto

Asi luce `App/Migrations/2026_09_03_000006_create_posts_table.php`:

```php
<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 255);
            $table->string('slug', 191)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('cover_image')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

---

## 6. Seeders

Los seeders insertan datos de prueba. La clase base `Cronos\Database\Seeder` provee:

- `$this->pdo()`: conexion PDO a la base de datos del `.env` (se conecta la primera vez y reutiliza la misma).
- `$this->call([Seeder::class, ...])`: ejecuta otros seeders en orden.

```php
<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->pdo()->exec("
            INSERT INTO `users` (`name`, `email`, `password`, `role`, `created_at`)
            VALUES ('Admin', 'admin@admin.com', '<hash-bcrypt>', 'admin', NOW());
        ");
    }
}
```

`DatabaseSeeder.php` es el punto de entrada de `db:seed` y llama a los demas en orden (respeta dependencias: primero users, luego posts):

```php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            PostSeeder::class,
        ]);
    }
}
```

---

## 7. Flujo de trabajo tipico

```bash
# 1. crear una migracion
php cronos make:migration create_products_table

# 2. editar el archivo generado (up/down con Schema)

# 3. ejecutar solo lo pendiente
php cronos migrate

# 4. verificar estado
php cronos migrate:status

# 5. si algo salio mal, revertir el ultimo lote
php cronos migrate:rollback

# 6. datos de prueba
php cronos make:seeder ProductSeeder
# ... editar el seeder y registrarlo en DatabaseSeeder ...
php cronos db:seed

# 7. reiniciar TODO en desarrollo (destructivo)
php cronos migrate:fresh
php cronos migrate:fresh --seed  # NO existe: fresh y seed son comandos separados
php cronos db:seed
```

---

## 8. Diferencias con Laravel (importante para IAs)

| Tema | Laravel | Cronos |
|---|---|---|
| Archivo de migracion | Clase con nombre o anonima, Schema identico en API | SIEMPRE clase anonima retornada con `return new class extends Migration` |
| Directorio | `database/migrations` | `App/Migrations` |
| Tabla de historial | `migrations` con lote | Igual concept, gestionada por `Migrator` de Cronos |
| `migrate:fresh --seed` | Existe como flag | NO existe: ejecutar `migrate:fresh` y luego `db:seed` |
| Motor de BD | Multi-dialecto (MySQL, Postgres, SQLite...) | Solo **MySQL/MariaDB** (el SQL lo genera `Blueprint`) |
| `Schema::table` | Renombra/modifica columnas (`change()`, `renameColumn()`) | Solo `ADD COLUMN`, indices y `DROP COLUMN` |
| Seeds | Faker + factories integrados | SQL/Modelo manual dentro de `run()`; no hay factories |
| Conexion de Schema | Servicio de contenedor | `Schema::setPDO()` inyectado por `Migrator` (o manual en tests) |
| `id()` | `BIGINT UNSIGNED` | Igual, `BIGINT UNSIGNED` |
| Transacciones por migracion | Envuelve en transaccion (donde el motor lo permite) | NO envuelve en transaccion (los DDL de MySQL hacen commit implicito) |
| Creacion de la BD | Requiere BD creada (o `db:create` de paquetes) | `DatabaseMigrate::connect()` la crea automaticamente si no existe |

---

## 9. Limitaciones conocidas

1. Dialecto unico MySQL/MariaDB: `Blueprint` genera sintaxis MySQL (`ENGINE=InnoDB`, backticks, `ENUM`).
2. No hay transacciones alrededor de cada migracion (los `CREATE/ALTER` de MySQL hacen commit implicito); una migracion fallida puede dejar cambios parciales: corrige y vuelve a `migrate`.
3. `Schema::table` no renombra ni cambia tipos de columnas existentes.
4. `constrained()` siempre referencia la columna `id` y deriva el nombre de tabla con un pluralizador simple (reglas `-s`, `-es`, `-ies`); para casos especiales usa `foreign()` manual.
5. `migrate:fresh` elimina TODAS las tablas de la base de datos indicada en el `.env`; no usar en produccion.
6. Los seeders conectan por su cuenta (via `DatabaseMigrate`); no dependen del contenedor ni del modo web.

---

> **Anterior**: [13 - React SPA](13-reactapp-spa.md)
