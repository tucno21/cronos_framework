# CRONOS FRAMEWORK PHP 8.2

## Requisitos

- PHP >= 8.2
- Composer

## Instalacion

1. Clonar el repositorio
2. Ejecutar `composer install`
3. Crear un archivo `.env` en la raiz del proyecto
4. Configurar el `.env` con los datos de la base de datos

## Estructura de Carpetas

```
📁 cronos_framework/
├── 📁 App/
│   ├── 📁 Controllers/
│   ├── 📁 Middlewares/
│   ├── 📁 Models/
│   ├── 📁 Migrations/
│   ├── 📁 Providers/
│   ├── 📁 Help/
│   └── 📁 Library/
├── 📁 config/
├── 📁 public/
├── 📁 resources/
├── 📁 routes/
├── 📁 System/
├── 📁 storage/
└── 📄 cronos (CLI)
```

## Documentacion

La documentación completa y detallada se encuentra organizada temáticamente en la carpeta [`Documentation/`](Documentation/README.md):

| Módulo | Documento | Descripción |
|---|---|---|
| **01 - Getting Started** | [Instalación](Documentation/01-getting-started/01-instalacion.md) | Requisitos, instalación, estructura de capas y protocolo de limpieza |
| **01 - Getting Started** | [Configuración](Documentation/01-getting-started/02-configuracion.md) | Variables `.env`, archivos `config/*` y service providers |
| **01 - Getting Started** | [Consola CLI](Documentation/01-getting-started/03-consola-cli.md) | Comandos `php cronos`: `make:*`, `migrate`, `db:seed` |
| **02 - HTTP & Routing** | [Rutas](Documentation/02-http-and-routing/01-rutas.md) | Rutas web, API, prefijos, grupos y middlewares |
| **02 - HTTP & Routing** | [Controladores](Documentation/02-http-and-routing/02-controladores.md) | Inyección de dependencias, ciclo de vida, Request/Response |
| **02 - HTTP & Routing** | [Middleware](Documentation/02-http-and-routing/03-middleware.md) | Pipeline HTTP, Throttle, CORS y middlewares globales |
| **02 - HTTP & Routing** | [Form Requests](Documentation/02-http-and-routing/04-form-requests.md) | Validación tipada y autorización previa automática |
| **02 - HTTP & Routing** | [API Resources](Documentation/02-http-and-routing/05-api-resources.md) | Transformadores JSON estructurados (`JsonResource`) |
| **03 - Database & ORM** | [Modelos y ORM](Documentation/03-database-and-orm/01-modelos-orm.md) | ActiveRecord, relaciones (1:1, 1:N, N:M), Scopes, Casts y CRUD |
| **03 - Database & ORM** | [Migraciones y Seeders](Documentation/03-database-and-orm/02-migraciones-y-seeders.md) | Schema Builder DDL, migraciones versionadas y seeders |
| **04 - Frontend & Views** | [Vistas Blade](Documentation/04-frontend-and-views/01-vistas-blade.md) | Motor Blade, layouts `@extends`, componentes `<x-* />`, slots |
| **04 - Frontend & Views** | [Sesiones](Documentation/04-frontend-and-views/02-sesiones.md) | Sesiones nativas, flash data, `old()`, CSRF y autenticación |
| **04 - Frontend & Views** | [Tailwind CSS](Documentation/04-frontend-and-views/03-tailwindcss.md) | Configuración con Tailwind CSS v4 y directivas `@theme` |
| **04 - Frontend & Views** | [React SPA](Documentation/04-frontend-and-views/04-reactapp-spa.md) | SPA con Vite + React + TypeScript en carpeta `reactapp/` |
| **05 - Testing** | [Testing HTTP Funcional](Documentation/05-testing/01-testing-http.md) | Tests en memoria con `MakesHttpRequests` y `TestResponse` |
| **06 - Guías y Utilidades** | [Validaciones](Documentation/06-advanced-and-guides/01-validaciones.md) | Catálogo completo de 31 reglas nativas y reglas de BD |
| **06 - Guías y Utilidades** | [Helpers](Documentation/06-advanced-and-guides/02-helpers.md) | Funciones globales (`view()`, `route()`, `asset()`, etc.) |
| **06 - Guías y Utilidades** | [Guía Nuevo Módulo](Documentation/06-advanced-and-guides/03-guia-nuevo-modulo.md) | Receta paso a paso para construir un módulo de punta a punta |

## Inicio Rapido

```bash
# Crear un controlador
php cronos make:controller UserController

# Crear un modelo
php cronos make:model User

# Crear un middleware
php cronos make:middleware AuthMiddleware

# Crear y ejecutar migracion
php cronos make:migration database
php cronos migrate
```

## Ejecucion en Desarrollo

Para desarrollar se ejecutan 3 procesos en paralelo (cada uno en su terminal):

### 1. Servidor web (PHP)

El proyecto corre bajo **Laragon** (Apache + MySQL) apuntando a la carpeta `public/`. Alternativa con el servidor integrado de PHP:

```bash
php -S localhost:8080 -t public
```

### 2. TailwindCSS (Documento 12)

Compila los CSS del SSR con recarga automatica al guardar:

```bash
# Windows
.\tailwind-dev.bat

# Linux/Mac
./tailwind-dev.sh
```

### 3. SPA React (Documento 13)

**Desarrollo** (hot reload en `http://localhost:5173`):

```bash
cd reactapp
npm run dev
```

**Build** (compila a `public/assets/spa/` para servir la SPA via el framework):

```bash
cd reactapp
npm run build
```

> En desarrollo se recomienda hacer `npm run build` cada vez que se compilan los cambios de la SPA para verlos en `http://localhost/prueba/page1` y `/prueba/page2`.

## Creditos

_Modelo de framework php_

- [Juan de la Torre](https://www.udemy.com/course/desarrollo-web-completo-con-html5-css3-js-php-y-mysql/) - Curso PHP y base framework.
- [The Codeholic](https://www.youtube.com/playlist?list=PLLQuc_7jk__Uk_QnJMPndbdKECcTEwTA1) - Framework php.
- [Antonio Sarosi](https://antoniosarosi.com/) - Framework php.

_Modificacion para la validacion del formulario de_

- [mkakpabla](https://github.com/mkakpabla/form-validation-php#readme) - Validacion Adaptado.
- [booomerang](https://github.com/booomerang/Validatr/tree/master/src) - Validacion php.

_inspirado en:_

- [codeigniter](https://codeigniter.com/user_guide/libraries/validation.html) - formato y uso de validaciones.
- [laravel](https://laravel.com/docs/8.x/validation) - estilo de las validaciones y funciones.
