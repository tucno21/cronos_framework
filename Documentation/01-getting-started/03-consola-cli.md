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

### Generar Form Request

Los form requests se crean en `App/Requests/`.

```bash
php cronos make:request NameRequest
php cronos make:request Name          # genera NameRequest.php (agrega el sufijo automaticamente)
php cronos make:request Name FolderName # crea en App/Requests/FolderName/NameRequest.php
```

### Generar API Resource

Los recursos JSON se crean en `App/Resources/`.

```bash
php cronos make:resource NameResource
php cronos make:resource Name          # genera NameResource.php (agrega el sufijo automaticamente)
php cronos make:resource Name FolderName # crea en App/Resources/FolderName/NameResource.php
```

### Generar Comando de Consola Personalizado

Los comandos de consola personalizados se crean en `App/Commands/`. Heredan de `Cronos\ConsoleCLI\Command` y se ejecutan directamente con `php cronos <firma>`.

```bash
php cronos make:command SendEmailsCommand
php cronos make:command SendEmails          # genera SendEmailsCommand.php automáticamente
php cronos make:command SendEmails Reports  # crea en App/Commands/Reports/SendEmailsCommand.php
```

**Ejemplo de Comando generado:**
```php
namespace App\Commands;

use Cronos\ConsoleCLI\Command;

class SendEmailsCommand extends Command
{
    protected string $signature = 'app:send-emails';
    protected string $description = 'Envía correos electrónicos pendientes.';

    public function handle(): int
    {
        $this->info("Enviando correos...");
        // Tu lógica de negocio aquí...
        return 0; // Código de salida exitoso
    }
}
```

**Ejecución:**
```bash
php cronos app:send-emails
```

### Generar Model Factory

Las factories de modelos se crean en `App/Factories/` y heredan de `Cronos\Model\Factory`. Permiten generar datos falsos para pruebas o sembrado de bases de datos.

```bash
php cronos make:factory UserFactory
php cronos make:factory User           # genera UserFactory.php automáticamente
php cronos make:factory User Auth      # crea en App/Factories/Auth/UserFactory.php
```

**Uso con el trait `HasFactory` en tu Modelo:**
```php
namespace App\Models;

use Cronos\Model\Model;
use Cronos\Model\HasFactory;

class User extends Model
{
    use HasFactory;
}

// En tus tests o seeders:
$user = User::factory()->make();               // Instancia en memoria
$admin = User::factory()->state(['role' => 'admin'])->make();
$users = User::factory()->count(10)->create(); // 10 registros persistidos en BD
```



### Generar Migracion

Crea un archivo de migracion timestamped en `App/Migrations/`. Si el nombre sigue el patron `create_NOMBRE_table`, el stub rellena el nombre de la tabla automaticamente.

```bash
php cronos make:migration create_users_table
# genera: App/Migrations/2026_09_03_HHMMSS_create_users_table.php

php cronos make:migration add_phone_to_users_table
# genera el stub con el placeholder {{table}} para editar a mano
```

| Situacion | Resultado |
|---|---|
| Nombre en snake_case valido | Crea el archivo con plantilla `up()/down()` |
| Nombre invalido (espacios, mayusculas, vacio) | Error |
| Ya existe otra migracion con ese nombre | Error: no sobreescribe |

**Primero configure los datos de MySQL en el archivo `.env`**.

### Generar Seeder

```bash
php cronos make:seeder UserSeeder
php cronos make:seeder User   # genera UserSeeder.php (agrega el sufijo automaticamente)
```

Crea el archivo en `App/Seeders/`. El seeder `DatabaseSeeder` es el punto de entrada de `db:seed`.

### Ejecutar Migraciones y Seeds

```bash
php cronos migrate            # ejecuta solo las migraciones pendientes
php cronos migrate:rollback   # revierte el ultimo lote (acepta steps: rollback 3)
php cronos migrate:status     # tabla de estado por migracion
php cronos migrate:fresh      # ELIMINA todas las tablas y vuelve a migrar (destructivo)
php cronos migrate:refresh    # rollback total + migrate
php cronos db:seed            # ejecuta App/Seeders/DatabaseSeeder.php
```

> Ver la documentacion completa del Schema Builder, estructura de migraciones y seeders en: **[14 - Migraciones y Seeders](14-migraciones-y-seeders.md)**.

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

**Migracion generada (stub con clase anonima):**
```php
<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

**Seeder generado:**
```php
<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
```

---

> **Anterior**: [01 - Rutas](01-rutas.md)
> **Siguiente**: [03 - Controladores](03-controladores.md)
