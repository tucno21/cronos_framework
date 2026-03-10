# ⚠️ INSTRUCCIONES PREVIAS (leer antes de ejecutar)

1. Abre el archivo `PROYECTO_CRONOS.md` en VS Code antes de ejecutar cada fase
2. Usa `@workspace` para que la IA tenga contexto completo del proyecto
3. Ejecuta una fase, revisa el código generado, y luego pasa a la siguiente
4. Al finalizar TODAS las fases, ejecuta el **Prompt Final** para actualizar `PROYECTO_CRONOS.md`

---

---

# FASE 1 — Middleware Pipeline

```
@@/PROYECTO_CRONOS.md 

Lee el archivo PROYECTO_CRONOS.md completo y luego analiza estos archivos del proyecto:
- System/Http/Middleware.php (interfaz actual)
- System/Routing/Router.php (cómo resuelve rutas actualmente)
- System/App.php (bootstrap de la app)
- App/Middlewares/AuthMiddleware.php (ejemplo de middleware existente)
- public/index.php (punto de entrada)

TAREA: Implementar un Middleware Pipeline robusto para Cronos Framework.

REQUISITOS OBLIGATORIOS:
1. Crear `System/Http/Pipeline.php` — clase Pipeline que ejecute middlewares en cadena
   - Debe implementar el patrón "onion" (cada middleware envuelve al siguiente)
   - Método principal: `send(Request $request)->through(array $middlewares)->then(callable $destination)`
   - Los middlewares deben poder detener la cadena retornando una Response

2. Crear `System/Http/MiddlewareGroup.php` — registro de grupos de middlewares
   - Grupos predefinidos: 'web' (sesión, CSRF) y 'api' (throttle, JSON)
   - Método: `group(string $name, array $middlewares)`
   - Método: `resolve(string $nameOrClass): array` — resuelve alias a clases reales

3. Modificar `System/Routing/Router.php` para:
   - Usar el Pipeline al despachar rutas
   - Soportar middlewares globales que se ejecuten en TODAS las rutas
   - Respetar el orden actual: primero middlewares de ruta, luego de controlador

4. Crear estos middlewares nuevos en `App/Middlewares/`:
   - `CorsMiddleware.php` — mueve la lógica CORS que actualmente está en Request::__construct()
   - `ThrottleMiddleware.php` — límite de peticiones por IP (configurable: max requests/minuto)
   - `LogRequestMiddleware.php` — registra cada request en el sistema de Log

5. Actualizar `config/app.php` para agregar:
   ```php
   'middleware_groups' => [
       'web' => [
           \App\Middlewares\CorsMiddleware::class,
       ],
       'api' => [
           \App\Middlewares\ThrottleMiddleware::class,
           \App\Middlewares\CorsMiddleware::class,
       ],
   ],
   'global_middlewares' => [
       \App\Middlewares\LogRequestMiddleware::class,
   ],
   ```

RESTRICCIONES:
- NO rompas la sintaxis actual de definición de rutas en routes/web.php y routes/api.php
- Los middlewares existentes (AuthMiddleware, AuthApiMiddleware, etc.) deben seguir funcionando SIN modificación
- Mantén la interfaz `System/Http/Middleware.php` compatible hacia atrás
- El orden de ejecución debe ser: global → grupo → ruta → controlador

ENTREGA: Genera TODOS los archivos mencionados con código completo y funcional.
Incluye comentarios explicando cada método importante.
```


Al finalizar la FASE 1, actualizar `PROYECTO_CRONOS.md`
---

---

# FASE 2 — Manejo de Errores Centralizado

```
@workspace

Lee el archivo PROYECTO_CRONOS.md completo y luego analiza estos archivos:
- System/Errors/ExceptionHandler.php (manejador actual)
- System/Errors/HttpNotFoundException.php
- System/Errors/RouteException.php
- System/Exceptions/CronosException.php
- resources/views/error/404.php
- public/index.php
- config/app.php

TAREA: Implementar un sistema de manejo de errores centralizado y profesional.

REQUISITOS OBLIGATORIOS:

1. Mejorar `System/Errors/ExceptionHandler.php` para:
   - Capturar TODOS los tipos de error: Exception, Error, y errores fatales de PHP
   - Detectar si la request es API (Accept: application/json o ruta /api/*) y retornar JSON en ese caso
   - En modo debug (config app.debug = true): mostrar stack trace detallado
   - En modo producción (app.debug = false): mostrar página de error amigable sin exponer detalles
   - Registrar TODOS los errores en el sistema de Log con contexto completo (URL, método HTTP, IP, user-agent)

2. Crear estas excepciones en `System/Errors/`:
   - `HttpException.php` — excepción HTTP genérica con código de estado configurable
   - `ValidationException.php` — para cuando falla la validación (lanza automáticamente desde Validation)
   - `AuthorizationException.php` — acceso denegado (403)
   - `MethodNotAllowedException.php` — método HTTP no permitido (405)

3. Crear vistas de error en `resources/views/error/`:
   - `500.php` — error interno del servidor
   - `403.php` — acceso denegado
   - `405.php` — método no permitido
   - `error.php` — página genérica para cualquier error HTTP no específico
   - Todas deben tener el mismo diseño consistente que `404.php`

4. Crear `System/Log/Logger.php` — implementar el sistema de Log vacío que ya existe en `System/Log/`:
   - Método estático: `Logger::error(string $message, array $context = [])`
   - Método estático: `Logger::warning(string $message, array $context = [])`
   - Método estático: `Logger::info(string $message, array $context = [])`
   - Guarda en `storage/logs/cronos-YYYY-MM-DD.log` con formato: `[datetime] LEVEL: message | context_json`
   - Rota archivos por día automáticamente

5. Agregar helper global en `System/Helpers/app.php`:
   - `abort(int $code, string $message = '')` — lanza HttpException con el código dado
   - `abort_if(bool $condition, int $code, string $message = '')` — lanza si condición es verdadera
   - `logger(): Logger` — retorna instancia del Logger

6. Actualizar `public/index.php` para:
   - Registrar el ExceptionHandler ANTES de cualquier otra inicialización
   - Usar `set_exception_handler`, `set_error_handler` y `register_shutdown_function`

RESTRICCIONES:
- Los errores en producción NUNCA deben exponer rutas del servidor, versiones de PHP, ni stack traces
- La respuesta JSON de errores para API debe seguir este formato estándar:
  ```json
  {"error": true, "code": 500, "message": "Internal Server Error"}
  ```
- Mantén compatibilidad con las excepciones ya existentes (HttpNotFoundException, RouteException)

ENTREGA: Genera todos los archivos con código completo. Para las vistas de error,
usa el mismo estilo visual que la vista 404.php existente.
```

---

---

# FASE 3 — Validación de Datos Mejorada

```
@workspace

Lee el archivo PROYECTO_CRONOS.md completo y luego analiza estos archivos:
- System/Validation/Validation.php (sistema actual)
- System/Validation/MessageError.php (mensajes de error actuales)
- System/Http/Controller.php (método validate() actual)
- System/Helpers/variable.php (constante RESULT_TYPE)
- tests/Integration/ValidationTest.php (tests actuales para no romperlos)

TAREA: Extender el sistema de validación existente sin romper lo que ya funciona.

REGLAS DE ORO:
- NO reescribas Validation.php desde cero, EXTIÉNDELO
- Todos los tests en ValidationTest.php deben seguir pasando
- Mantén la sintaxis actual: 'campo' => 'required|min:3|unique:Model,columna'

REQUISITOS OBLIGATORIOS:

1. Agregar estas reglas nuevas a `System/Validation/Validation.php`:
   - `email` — valida formato de email (usa filter_var)
   - `url` — valida formato de URL
   - `numeric` — solo números (enteros o decimales)
   - `integer` — solo enteros
   - `boolean` — acepta: true, false, 1, 0, "1", "0"
   - `date` — fecha válida (formato Y-m-d)
   - `date_format:formato` — valida contra un formato específico (ej: d/m/Y)
   - `before:fecha` — fecha anterior a la dada
   - `after:fecha` — fecha posterior a la dada
   - `in:val1,val2,val3` — el valor debe estar en la lista
   - `not_in:val1,val2` — el valor NO debe estar en la lista
   - `confirmed` — verifica que exista campo `campo_confirmation` con el mismo valor
   - `same:otro_campo` — igual al valor de otro campo
   - `different:otro_campo` — diferente al valor de otro campo
   - `starts_with:prefijo` — el string debe empezar con el prefijo dado
   - `ends_with:sufijo` — el string debe terminar con el sufijo dado
   - `regex:patron` — valida contra expresión regular
   - `file` — verifica que sea un archivo subido válido
   - `image` — archivo que sea imagen (jpg, png, gif, webp, svg)
   - `mimes:ext1,ext2` — extensiones de archivo permitidas
   - `max_size:kb` — tamaño máximo de archivo en KB
   - `nullable` — permite que el campo sea null o vacío (detiene validación si está vacío)
   - `sometimes` — solo valida si el campo está presente en el request

2. Crear `System/Validation/Rule.php` — clase para reglas con fluent interface:
   ```php
   // Permitir esto en los controladores:
   'email' => Rule::required()->email()->unique('User', 'email')->max(100),
   'avatar' => Rule::nullable()->image()->max_size(2048),
   ```

3. Mejorar mensajes de error en `System/Validation/MessageError.php`:
   - Agregar mensajes para TODAS las reglas nuevas en español
   - Permitir mensajes personalizados por campo:
     ```php
     $this->validate($data, $rules, [
         'email.required' => 'El correo electrónico es obligatorio',
         'email.email' => 'Ingresa un correo válido',
     ]);
     ```

4. Crear `System/Errors/ValidationException.php` (coordinado con Fase 2):
   - Se lanza automáticamente cuando la validación falla en contexto API
   - Incluye todos los errores como JSON: `{"errors": {"campo": ["mensaje"]}}`

5. Agregar método en `System/Http/Controller.php`:
   - `validateOrFail(array $data, array $rules, array $messages = [])`:
     En web: redirige back() con errores en sesión (comportamiento actual)
     En API: lanza ValidationException con los errores en JSON

RESTRICCIONES:
- La validación `unique` existente debe seguir funcionando igual
- El helper `session()->errors()` debe seguir siendo compatible
- RESULT_TYPE debe seguir funcionando como antes
- No cambies la firma del método `validate()` actual en Controller.php

ENTREGA: Código completo de todos los archivos modificados/creados.
Agrega al menos 3 ejemplos de uso en comentarios al inicio de Validation.php.
```

---

---

# FASE 4 — Sistema de Migraciones

```
@workspace

Lee el archivo PROYECTO_CRONOS.md completo y luego analiza estos archivos:
- System/Database/DatabaseMigrate.php (sistema actual)
- System/Database/PdoDriver.php (driver de BD)
- System/Database/DBexecute.php (ejecutor de queries)
- App/Migrations/Database.php (migraciones actuales de la app)
- System/ConsoleCLI/ConsoleCLI.php (CLI actual)
- System/ConsoleCLI/templates/migration.php (template actual)
- config/database.php (configuración de BD)

TAREA: Implementar un sistema de migraciones completo con control de versiones del esquema.

REQUISITOS OBLIGATORIOS:

1. Crear `System/Database/Schema/Blueprint.php` — constructor fluido de tablas:
   ```php
   // Debe permitir esta sintaxis:
   Schema::create('users', function(Blueprint $table) {
       $table->id();                          // INT AUTO_INCREMENT PRIMARY KEY
       $table->string('name', 100);           // VARCHAR(100) NOT NULL
       $table->string('email')->unique();     // VARCHAR(255) NOT NULL UNIQUE
       $table->string('password');
       $table->text('bio')->nullable();       // TEXT NULL
       $table->boolean('active')->default(1);
       $table->integer('age')->unsigned();
       $table->decimal('price', 8, 2);
       $table->enum('status', ['active','inactive','pending']);
       $table->timestamps();                  // created_at, updated_at TIMESTAMP
       $table->softDeletes();                 // deleted_at TIMESTAMP NULL
   });
   ```
   Métodos de columna: `id()`, `string()`, `text()`, `longText()`, `integer()`,
   `bigInteger()`, `boolean()`, `decimal()`, `float()`, `date()`, `datetime()`,
   `timestamp()`, `timestamps()`, `softDeletes()`, `enum()`, `json()`, `binary()`
   
   Modificadores encadenables: `->nullable()`, `->default($val)`, `->unique()`,
   `->unsigned()`, `->after('columna')`, `->comment('texto')`
   
   Índices: `$table->index(['col1', 'col2'])`, `$table->foreign('user_id')->references('id')->on('users')`

2. Crear `System/Database/Schema/Schema.php` — fachada del Schema Builder:
   - `Schema::create(string $table, callable $callback)` — crear tabla
   - `Schema::table(string $table, callable $callback)` — modificar tabla existente
   - `Schema::drop(string $table)` — eliminar tabla
   - `Schema::dropIfExists(string $table)` — eliminar si existe
   - `Schema::hasTable(string $table): bool` — verificar si tabla existe
   - `Schema::hasColumn(string $table, string $column): bool`
   - `Schema::rename(string $from, string $to)` — renombrar tabla

3. Mejorar `System/Database/DatabaseMigrate.php`:
   - Crear tabla `cronos_migrations` automáticamente si no existe:
     ```sql
     CREATE TABLE cronos_migrations (
         id INT AUTO_INCREMENT PRIMARY KEY,
         migration VARCHAR(255) NOT NULL,
         batch INT NOT NULL,
         executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     )
     ```
   - Método `run()`: ejecuta solo las migraciones pendientes (no ejecutadas aún)
   - Método `rollback(int $steps = 1)`: revierte el último batch (o N batches)
   - Método `reset()`: revierte TODAS las migraciones
   - Método `refresh()`: reset() + run() (equivalente a migrate:fresh)
   - Método `status()`: muestra tabla con estado de cada migración (ejecutada/pendiente)
   - Agrupa migraciones por "batch" para poder hacer rollback por grupos

4. Actualizar el template `System/ConsoleCLI/templates/migration.php`:
   ```php
   <?php
   use System\Database\Schema\Schema;
   use System\Database\Schema\Blueprint;
   
   return new class {
       public string $table = '{{TABLE_NAME}}';
   
       public function up(): void {
           Schema::create($this->table, function (Blueprint $table) {
               $table->id();
               $table->timestamps();
           });
       }
   
       public function down(): void {
           Schema::dropIfExists($this->table);
       }
   };
   ```

5. Actualizar `System/ConsoleCLI/ConsoleCLI.php` para agregar estos comandos:
   - `php cronos migrate` — ejecuta migraciones pendientes
   - `php cronos migrate:rollback` — revierte último batch
   - `php cronos migrate:reset` — revierte todo
   - `php cronos migrate:refresh` — reset + migrate
   - `php cronos migrate:status` — muestra estado de migraciones
   - `php cronos make:migration nombre_migracion [--table=nombre]` — crea archivo de migración
     con nombre de archivo: `YYYY_MM_DD_HHMMSS_nombre_migracion.php`
     guardado en: `App/Migrations/`

6. Crear migraciones de ejemplo en `App/Migrations/` para las tablas existentes del proyecto
   (User y Blog) como referencia de cómo documentar las tablas actuales.

RESTRICCIONES:
- Compatible con MySQL/MariaDB (el proyecto usa PDO + MySQL)
- NO uses librerías externas, solo PHP puro + PDO
- Las migraciones deben poder ejecutarse en ORDEN por nombre de archivo (timestamp prefix)
- El método `down()` es OBLIGATORIO en toda migración y debe revertir completamente el `up()`
- Si una migración falla, se hace rollback automático de esa migración y se muestra el error

ENTREGA: Todos los archivos completos. Incluye un ejemplo de salida esperada
en la consola para cada comando de migración (como comentario al inicio del ConsoleCLI.php).
```

---

---

# PROMPT FINAL — Actualizar PROYECTO_CRONOS.md

> Ejecuta este prompt DESPUÉS de haber implementado y verificado las 4 fases anteriores.

```
@workspace

Las siguientes mejoras críticas acaban de ser implementadas en Cronos Framework:

1. MIDDLEWARE PIPELINE: Se crearon Pipeline.php, MiddlewareGroup.php, CorsMiddleware.php,
   ThrottleMiddleware.php y LogRequestMiddleware.php. Se actualizó Router.php y config/app.php.

2. MANEJO DE ERRORES: Se mejoró ExceptionHandler.php con soporte para errores fatales,
   modo debug/producción, respuestas JSON para API, y logging. Se crearon nuevas excepciones
   (HttpException, ValidationException, AuthorizationException, MethodNotAllowedException)
   y vistas de error (500, 403, 405). Se implementó Logger.php y helpers abort(), abort_if().

3. VALIDACIÓN MEJORADA: Se extendió Validation.php con 20+ reglas nuevas, se creó Rule.php
   para fluent interface, se mejoraron los mensajes de error con soporte para mensajes
   personalizados por campo, y se agregó validateOrFail() en Controller.php.

4. SISTEMA DE MIGRACIONES: Se crearon Blueprint.php y Schema.php para definir esquemas
   con sintaxis fluida. Se mejoró DatabaseMigrate.php con tabla cronos_migrations,
   batches, rollback y status. Se actualizó ConsoleCLI con comandos migrate:*.

TU TAREA: Actualiza el archivo PROYECTO_CRONOS.md para reflejar todos estos cambios.

Específicamente, actualiza o agrega:

1. **Sección 2 (Árbol de archivos):** Agrega TODOS los archivos nuevos creados con sus comentarios

2. **Sección 3 (Flujo de ejecución):** Actualiza el lifecycle para incluir el Middleware Pipeline
   y el ExceptionHandler registrado desde el inicio

3. **Sección 4 (Componentes del Core):** Agrega documentación de:
   - Pipeline.php, MiddlewareGroup.php
   - Logger.php
   - Blueprint.php, Schema.php
   - Rule.php
   - Los nuevos archivos de excepciones

4. **Agrega nueva Sección: "Sistema de Migraciones"** con:
   - Cómo crear una migración (comando CLI)
   - Estructura de un archivo de migración
   - Todos los tipos de columna disponibles en Blueprint
   - Todos los comandos migrate disponibles con ejemplos
   - Cómo hacer rollback

5. **Actualiza Sección 7 (Validación):** Lista completa de todas las reglas disponibles
   incluyendo las nuevas, con ejemplo de uso de Rule fluent interface

6. **Actualiza Sección 11 (Funcionalidades implementadas):** Marca como completadas:
   - ✅ Middleware Pipeline con grupos y middlewares globales
   - ✅ Manejo de errores centralizado con logging
   - ✅ Validación extendida con 20+ reglas
   - ✅ Sistema de migraciones con control de versiones

7. **Actualiza Sección 12 (Limitaciones vs Laravel):** Actualiza la tabla comparativa
   para reflejar las nuevas capacidades

8. **Actualiza Sección 14 (Notas para IAs):** Agrega advertencias sobre:
   - Cómo usar correctamente el Pipeline
   - Cómo usar abort() vs lanzar excepciones manualmente
   - Diferencia entre validate() y validateOrFail()
   - Convención de nombres de archivos de migración (timestamp prefix)
   - Cómo usar Schema vs SQL directo

Mantén TODO el contenido existente del documento. Solo agrega o actualiza lo necesario.
El resultado debe ser el PROYECTO_CRONOS.md completo y actualizado.
```

---

## 📋 Checklist de verificación post-implementación

Antes de ejecutar el Prompt Final, verifica que:

- [ ] `php cronos migrate` crea la tabla `cronos_migrations` y ejecuta las migraciones
- [ ] Un middleware inválido en una ruta lanza error descriptivo, no 500 genérico  
- [ ] En modo `app.debug = false`, los errores 500 muestran página amigable sin stack trace
- [ ] `$this->validate()` sigue funcionando igual en controladores existentes
- [ ] Los tests en `tests/Integration/ValidationTest.php` siguen pasando
- [ ] Las rutas existentes en `routes/web.php` y `routes/api.php` siguen funcionando sin cambios