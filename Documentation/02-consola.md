# Consola (CLI)

Cronos incluye un sistema de linea de comandos similar a Artisan de Laravel, ejecutado mediante el archivo `cronos`.

## Comandos Disponibles

### Generar Controlador

Los controladores se crean en `App/Controllers/`.

```bash
php cronos make:controller Name
php cronos make:controller Name FolderName
```

### Generar Modelo

Los modelos se crean en `App/Models/`.

```bash
php cronos make:model Name
php cronos make:model Name FolderName
```

### Generar Middleware

Los middlewares se crean en `App/Middlewares/`.

```bash
php cronos make:middleware Name
```

### Generar Migracion

Crea el archivo `App/Migrations/Database.php`. **Primero configure los datos en el archivo .env**.

```bash
php cronos make:migration database
```

| Situacion | Resultado |
|---|---|
| Archivo no existe | Crea `App/Migrations/Database.php` con plantilla |
| Archivo ya existe | Error: no sobreescribe |
| Argumento diferente a `database` | Error |

El archivo generado incluye tablas `users` y `blogs` como ejemplo. Debe modificar las tablas segun su proyecto. La plantilla usa `DROP TABLE IF EXISTS` (destruye datos, usar con cuidado).

### Ejecutar Migracion

```bash
php cronos migrate
```

## Ejemplos de Archivos Generados

**Controlador generado:**
```php
<?php

namespace App\Controllers;

use Cronos\Http\Controller;

class NameController extends Controller
{
    public function index()
    {
        return view('name.index');
    }
}
```

**Modelo generado:**
```php
<?php

namespace App\Models;

use Cronos\Model\Model;

class Name extends Model
{
    protected string $table = 'names';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
}
```

**Middleware generado:**
```php
<?php

namespace App\Middlewares;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Http\Middleware;

class NameMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Logica del middleware
        return $next($request);
    }
}
```

**Migracion generada (plantilla base):**
```php
<?php

namespace App\Migrations;

use Cronos\Database\DatabaseMigrate;

class Database extends DatabaseMigrate
{
    public function migrate()
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            echo "\nIniciando migracion...\n";

            $this->pdo->exec("DROP TABLE IF EXISTS `blogs`;");
            $this->pdo->exec("DROP TABLE IF EXISTS `users`;");

            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(60),
                    `email` VARCHAR(60),
                    `password` VARCHAR(60),
                    `created_at` TIMESTAMP NULL DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT NULL
                );
            ");

            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `blogs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(100),
                    `slug` VARCHAR(150),
                    `content` TEXT,
                    `user_id` INT,
                    `created_at` TIMESTAMP NULL DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );
            ");

            echo "\nMigracion completada exitosamente.\n";
            return true;
        } catch (\PDOException $e) {
            echo "\nError en la migracion: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
```

---

> **Anterior**: [01 - Rutas](01-rutas.md)
> **Siguiente**: [03 - Controladores](03-controladores.md)
