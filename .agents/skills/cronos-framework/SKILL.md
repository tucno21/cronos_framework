---
name: cronos-framework
description: Use when working on the cronos_framework project — Cronos is NOT Laravel. Required before writing any PHP code (controllers, models, migrations, routes, middleware, FormRequests, ApiResources), Blade views or x-* components, Tailwind SSR styles, the reactapp/ SPA, sessions, validation rules, helpers, or tests with MakesHttpRequests. Triggers: Cronos, php cronos CLI, BladeEngine, ActiveRecord, Schema Builder, Model Factory, SPA, reactapp, Vite, TailwindCSS, X-Token API, routes/web.php, routes/api.php, storage/cache/views.
---

# Cronos Framework — Guía para IAs

Guía de uso del proyecto **cronos_framework** (PHP >= 8.3, MVC, sin dependencias externas de framework).

## ⚠️ Reglas Invariables (NUNCA violar)

1. **Cronos NO es Laravel.** Jamás importes ni sugieras `Illuminate\*`, Eloquent, Carbon, ni facades (`Auth::`, `Log::`, `DB::`). Todo vive en el namespace `Cronos\*` (núcleo en `System/`, app en `App/`).
2. **No existen facades ni Carbon.** Los timestamps de BD son strings. Usa helpers globales: `session()`, `route()`, `asset()`, `view()`, `csrf_token()`, `old()`.
3. **Testing en memoria:** nunca levantes servidores (`php -S`, curl). Usa el trait `Cronos\Testing\MakesHttpRequests` (`$this->getJson()`, `postJson()`, ...) heredando de `Tests\TestCase\OrmTestCase`, y envuelve escrituras con `$this->rollbackAfter(fn () => ...)`.
4. **Mundos separados SSR/SPA:**
   - SSR público (Blade + Tailwind CLI standalone, sin Node): vistas en `resources/views/`, CSS fuente en `resources/css/`.
   - SPA protegida (React + Vite en `reactapp/`): build a `public/assets/spa/`. Por CADA ruta de React Router se registra también `Route::get('/ruta', [SpaController::class, 'index'])` en `routes/web.php`.
5. **Nunca editar builds:** `public/assets/**` y `storage/cache/**` son generados. Los fuentes van en `resources/css/` y las vistas en `resources/views/`.
6. **Límites del framework:** no hay colas/jobs, eventos asíncronos, multi-DB en runtime, ni validaciones anidadas `items.*.id`. No los inventes.

## 📋 Mapa de Ruteo: tarea → documentación a leer

Antes de implementar, lee con la herramienta Read SOLO los archivos relevantes (rutas relativas a la raíz del proyecto):

| Tarea | Documentación |
|---|---|
| Setup, estructura, config `.env`, limpiar demo | `Documentation/01-getting-started/01-instalacion.md`, `02-configuracion.md` |
| Generar código con CLI (`make:*`, `migrate`, `db:seed`) | `Documentation/01-getting-started/03-consola-cli.md` |
| Rutas web/API, grupos, nombres | `Documentation/02-http-and-routing/01-rutas.md` |
| Controladores, DI, Request/Response | `Documentation/02-http-and-routing/02-controladores.md` |
| Middleware, pipeline, auth | `Documentation/02-http-and-routing/03-middleware.md` |
| Validación tipada en clases Request | `Documentation/02-http-and-routing/04-form-requests.md` |
| Transformar modelos a JSON (API) | `Documentation/02-http-and-routing/05-api-resources.md` |
| Modelos, relaciones, scopes, casts, CRUD | `Documentation/03-database-and-orm/01-modelos-orm.md` |
| Migraciones y seeders (Schema Builder) | `Documentation/03-database-and-orm/02-migraciones-y-seeders.md` |
| Factories de datos de prueba | `Documentation/03-database-and-orm/03-model-factories.md` |
| Vistas Blade (¡leer Reglas de Oro y secciones 13, 22, 26!) | `Documentation/04-frontend-and-views/01-vistas-blade.md` |
| Sesiones, flash, old(), CSRF | `Documentation/04-frontend-and-views/02-sesiones.md` |
| CSS del SSR (Tailwind CLI, `@source`, `@theme`) | `Documentation/04-frontend-and-views/03-tailwindcss.md` |
| SPA React (`reactapp/`, build, rutas, API X-Token) | `Documentation/04-frontend-and-views/04-reactapp-spa.md` |
| Tests funcionales HTTP | `Documentation/05-testing/01-testing-http.md` |
| Catálogo de reglas de validación | `Documentation/06-advanced-and-guides/01-validaciones.md` |
| Helpers globales | `Documentation/06-advanced-and-guides/02-helpers.md` |
| Módulo completo end-to-end (receta) | `Documentation/06-advanced-and-guides/03-guia-nuevo-modulo.md` |
| Depuración (`dump`/`d`/`dd`), errores | `Documentation/06-advanced-and-guides/04-depuracion-y-errores.md` |
| Arquitectura, ciclo de vida, roadmap | `Documentation/PROYECTO_CRONOS.md` |

Índice maestro: `Documentation/README.md`.

## 🚫 Errores Frecuentes que Debes Evitar

| MAL (instinto Laravel) | BIEN (Cronos) |
|---|---|
| `use Illuminate\Http\Request` | `Cronos\Http\Request` (inyección por constructor/método vía Container) |
| `Auth::user()`, `Log::info()` | `session()->user()`, sin logger: depurar con `dump()/d()/dd()` |
| `Carbon::now()` | strings de BD o `date()`/`DateTime` nativo |
| `@extends($layout)` dinámico | `@extends('layouts.app')` literal, UNA vez, primera línea |
| `$loop` en `@for`/`@while` | `$loop` SOLO en `@foreach`/`@forelse` |
| `<x-card>{{ $slot }}</x-card>` con markup dentro del componente usando `{{ $slot }}` | `{!! $slot !!}` (el slot llega como HTML del padre) |
| `@props` dentro de `<?php ?>` | `@props([...])` en la primera línea, modo HTML |
| `base_url . '/assets/x.css'` o rutas absolutas | `@asset('assets/css/x.css')` (con cache-busting `?v=filemtime`) y `{{ route('nombre') }}` |
| Formulario POST sin `@csrf` | siempre `@csrf`; repoblar con `{{ old('campo') }}` escapado |
| Buscar `tailwind.config.js` | v4: config en CSS con `@theme {}`, escaneo con `@source` |
| Compilar SPA para cambiar estilos del SSR | `.\tailwind-dev.bat` (SSR usa CLI standalone, independiente de Vite) |
| Test con `curl` o servidor web | `$this->get('/ruta')` con `MakesHttpRequests` + `rollbackAfter` |

## ✅ Flujo de Trabajo Recomendado

1. **Identifica el dominio** de la tarea y lee los docs del mapa de ruteo (solo los necesarios).
2. **Módulo nuevo:** sigue la receta de `06-advanced-and-guides/03-guia-nuevo-modulo.md` (migración → modelo → factory → rutas → FormRequest → controlador → vistas → test).
3. **Verifica siempre** contra los cheat sheets: Blade (sección 26 de vistas-blade.md), validaciones (01-validaciones.md), helpers (02-helpers.md).
4. **Tests:** ejecuta `vendor/bin/phpunit` tras cambios con lógica; toda prueba funcional usa `MakesHttpRequests`.
5. **Frontend:** SSR → editar `resources/css/*.css` y compilar con scripts tailwind-*; SPA → editar `reactapp/` y `npm run build` (nunca tocar `public/assets/spa/`).
