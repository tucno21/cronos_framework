# Arquitectura Global y Manifiesto de Diseño — Cronos Framework

> **AVISO CRÍTICO PARA DESARROLLADORES E INTELIGENCIAS ARTIFICIALES (IAs)**
>
> Cronos **NO es Laravel**. Aunque implementa patrones ergonómicos familiares (ActiveRecord, BladeEngine, FormRequests, ApiResources, MakesHttpRequests), **Cronos es un framework PHP independiente y autocontenido**.
>
> **Reglas fundamentales para IAs y desarrolladores:**
> 1. **Cero dependencias de Illuminate:** No intentes importar ni sugerir paquetes de `Illuminate\*`.
> 2. **Ejecución en memoria:** Para pruebas HTTP funcionales, nunca levantes servidores web externos (`php -S` / Apache / Nginx) ni recurras a `curl`. Usa `$this->getJson()`, `$this->postJson()`, etc.
> 3. **Aislamiento de BD:** Usa siempre `$this->rollbackAfter(function () { ... });` en tests de integración y funcionales.
> 4. **Documentación modular:** Para guías detalladas de implementación, consulta los módulos temáticos en [`Documentation/README.md`](README.md).

---

## 1. Filosofía de Diseño y Propósito

Cronos Framework es un framework MVC en PHP >= 8.3 diseñado con una filosofía de **simplicidad, legibilidad y rendimiento sin sobrecarga**:

- **Arquitectura MVC transparente:** Núcleo desacoplado en `System/` y código de aplicación en `App/`.
- **Contenedor de Inyección de Dependencias nativo:** Resuelve automáticamente dependencias por reflexión en constructores y métodos de controladores (`System/Container/`).
- **Pipeline HTTP cebolla (Onion pattern):** Ejecuta middlewares globales, de ruta y de controlador de forma anidada y determinista (`System/Http/Pipeline.php`).
- **Motor Blade propio:** Compilador de plantillas en dos fases con soporte para layouts (`@extends`, `@section`), componentes anónimos (`<x-nombre />`), slots nombrados y `@push`/`@stack` sin dependencias externas.
- **ORM ActiveRecord expresivo:** Manejo de relaciones 1:1, 1:N y N:M con clave foránea, Eager Loading (`with`), Local Scopes (`scopeActivo`), Casts bidireccionales y transacciones atómicas.
- **Schema Builder y Migraciones:** DDL fluído con historial por lotes (`batch`) y rollback seguro.

---

## 2. Ciclo de Vida de una Petición (Request Lifecycle)

```
[ Petición HTTP ]
       │
       ▼
1. public/index.php ──────────► Requiere Composer autoload y arranca Cronos\App
       │
       ▼
2. App::bootstrap($root) ─────► Dotenv::load() + Config::loadConfig()
       │                         Providers boot (Database, Session, View, Hasher)
       │                         Instancia Request, Response, Router
       │                         Apertura de Sesión y conexión PDO
       │                         Providers runtime (RouteServiceProvider)
       │
       ▼
3. App::run() ────────────────► Router::resolve($request)
       │
       ▼
4. Router::resolve() ─────────► Matchea método HTTP y regex de URI
       │                         Construye el Pipeline:
       │                         Middlewares Globales ──► Ruta ──► Controlador
       │
       ▼
5. Pipeline::then() ──────────► Inyección de dependencias (Container + Reflection)
       │                         Instanciación y validación automática de FormRequests
       │                         Ejecución de la acción del Controlador
       │
       ▼
6. Generación de Response ────► Instancia de Cronos\Http\Response (HTML, JSON, Redirect)
       │
       ▼
7. Response::sendResponse() ──► Envío de cabeceras HTTP y cuerpo de respuesta
       │
       ▼
[ Cliente HTTP ]
```

---

## 3. Límites Intencionales del Framework (Qué NO incluye)

Para preservar su ligereza y facilidad de mantenimiento, Cronos deliberadamente **no** incluye:
- Sistemas pesados de colas/jobs o workers en segundo plano.
- Sistema complejo de eventos/listeners asíncronos.
- Soporte multibase de datos simultánea en runtime (enfocado en MySQL/PDO).
- Validaciones anidadas con asteriscos complejos (`items.*.id`).

---

## 4. Notas Clave para IAs y Desarrolladores

### Motor Blade (`System/View/`)
- Los atributos de componentes `<x-nombre>` son cadenas literales; para pasar variables se usa `:prop="$variable"`.
- Los archivos compilados se almacenan en `storage/cache/`. Si se altera la gramática del compilador, debe limpiarse ese directorio.

### Sub-sistema de Depuración y Errores (`System/Debug/` y `System/Errors/`)
- Utiliza `dump(...$vars)`, `d(...$vars)` y `dd(...$vars)` para inspección de memoria en cualquier punto del framework.
- El orquestador `Cronos\Debug\Dumper` detecta el entorno automáticamente: colores ANSI para terminal CLI, panel interactivo colapsable con tema oscuro para navegador web, y JSON estructurado (`__cronos_debug: true`) para llamadas API/AJAX.
- Si `CRONOS_APP_DEBUG=true`, las excepciones no capturadas se presentan con `Cronos\Debug\ErrorRenderer` en una pantalla interactiva con snippet de código fuente navegable en vivo, línea resaltada y datos de contexto de la petición.
- En producción (`CRONOS_APP_DEBUG=false`), los errores se capturan de forma silenciosa y segura mostrando `errors.500`.

### Testing Automatizado (`tests/`)
- La suite completa de PHPUnit se ejecuta con `vendor/bin/phpunit`.
- Toda prueba de ruta o middleware debe usar el trait `Cronos\Testing\MakesHttpRequests` heredando de `Tests\TestCase\OrmTestCase`.
- Para consultar la documentación por temas específicos, consulta siempre el índice maestro en [`Documentation/README.md`](README.md).

 
