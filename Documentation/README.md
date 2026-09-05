# Documentación Oficial de Cronos Framework

> **AVISO CRÍTICO PARA DESARROLLADORES E INTELIGENCIAS ARTIFICIALES (IAs)**
>
> Cronos **NO es Laravel**. Aunque toma inspiración en su elegancia y ergonomía (ActiveRecord, Blade, FormRequests, ApiResources, MakesHttpRequests), **Cronos es un framework PHP independiente y ligero**.
> - **NUNCA uses namespaces de Illuminate** (`Illuminate\*`).
> - Usa exclusivamente los componentes del namespace `Cronos\*`.
> - Todo el framework se ejecuta en memoria y bajo un contenedor de dependencias nativo (`Cronos\Container\Container`).

---

## 🗺️ Mapa de Documentación y Organización Modular

La documentación está organizada temáticamente en módulos para facilitar la navegación a equipos de desarrollo y modelos de IA.

```text
Documentation/
├── README.md                           # Índice maestro y orden de lectura
├── PROYECTO_CRONOS.md                  # Visión general, arquitectura y roadmap
│
├── 📁 01-getting-started/              # Primeros pasos, configuración e infraestructura
│   ├── 01-instalacion.md               # Instalación, capas framework vs demo y limpieza
│   ├── 02-configuracion.md             # Variables .env, archivos en config/* y proveedores
│   └── 03-consola-cli.md               # CLI "php cronos": comandos make:*, migrate, db:seed
│
├── 📁 02-http-and-routing/             # Capa Web, APIs y Ciclo de Vida HTTP
│   ├── 01-rutas.md                     # Definición de rutas web y API, prefijos y grupos
│   ├── 02-controladores.md             # Controladores, inyección de dependencias, Request/Response
│   ├── 03-middleware.md                # Pipeline HTTP, middlewares globales y de ruta
│   ├── 04-form-requests.md             # Validación tipada y desacoplada en clases Request
│   └── 05-api-resources.md             # Transformadores JSON para respuestas de API
│
├── 📁 03-database-and-orm/             # Persistencia, Modelado y Base de Datos
│   ├── 01-modelos-orm.md               # ORM Cronos: relaciones 1:1, 1:N, N:M, Scopes, Casts y CRUD
│   └── 02-migraciones-y-seeders.md     # Schema Builder, migraciones y seeders de datos
│
├── 📁 04-frontend-and-views/           # Capa de Presentación y Frontend
│   ├── 01-vistas-blade.md              # Motor Blade: layouts, secciones, componentes x-, slots
│   ├── 02-sesiones.md                  # Manejo de sesiones, flash data, old() y CSRF
│   ├── 03-tailwindcss.md               # Compilación de estilos con Tailwind CSS v4
│   └── 04-reactapp-spa.md              # Single Page Application (SPA) en React + TypeScript
│
├── 📁 05-testing/                      # Aseguramiento de Calidad y Pruebas
│   └── 01-testing-http.md              # Testing funcional HTTP en memoria y aserciones fluidas
│
46: └── 📁 06-advanced-and-guides/          # Utilidades, Validaciones y Recetas
47:     ├── 01-validaciones.md              # Catálogo exhaustivo de reglas de validación
48:     ├── 02-helpers.md                   # Funciones helpers globales del framework
49:     ├── 03-guia-nuevo-modulo.md         # Receta completa paso a paso para un nuevo módulo
50:     └── 04-depuracion-y-errores.md      # Depuración (dump, d, dd) y pantalla visual de errores
51: ```
52: 
53: ---
54: 
55: ## 📑 Tabla de Contenidos Detallada
56: 
57: ### 1. Primeros Pasos (`01-getting-started/`)
58: - [**01 - Instalación y Estructura**](01-getting-started/01-instalacion.md): Requisitos del sistema, estructura de carpetas, distinción entre núcleo `System/` y código de aplicación demo `App/`, y protocolo de limpieza para proyectos nuevos.
59: - [**02 - Configuración**](01-getting-started/02-configuracion.md): Gestión del entorno (`.env`), archivos de configuración en `config/`, configuración de base de datos, sesiones y CORS.
60: - [**03 - Consola CLI**](01-getting-started/03-consola-cli.md): Uso del ejecutable `php cronos`, generación de modelos, controladores, migraciones, seeders, requests y resources.
61: 
62: ### 2. Capa HTTP y Enrutamiento (`02-http-and-routing/`)
63: - [**01 - Rutas**](02-http-and-routing/01-rutas.md): Registro de rutas en `routes/web.php` y `routes/api.php`, parámetros dinámicos, rutas con nombre y grupos.
64: - [**02 - Controladores**](02-http-and-routing/02-controladores.md): Manejo del ciclo de vida del controlador, inyección de dependencias en métodos, respuestas HTML/JSON y redirecciones.
65: - [**03 - Middleware**](02-http-and-routing/03-middleware.md): Middlewares de autenticación, CORS, protección de rutas y Pipeline de ejecución HTTP.
66: - [**04 - Form Requests**](02-http-and-routing/04-form-requests.md): Clases de petición dedicadas con reglas de validación y autorización previa automáticas.
67: - [**05 - API Resources**](02-http-and-routing/05-api-resources.md): Capa de transformación de modelos a respuestas JSON estructuradas con `JsonResource` y `ResourceCollection`.
68: 
69: ### 3. Base de Datos y ORM (`03-database-and-orm/`)
70: - [**01 - Modelos y ORM**](03-database-and-orm/01-modelos-orm.md): ActiveRecord en Cronos, Query Builder, relaciones (`hasOne`, `belongsTo`, `hasMany`, `belongsToMany`), eager loading, local scopes, casts y transacciones.
71: - [**02 - Migraciones y Seeders**](03-database-and-orm/02-migraciones-y-seeders.md): Schema Builder fluído (`create`, `table`), tipos de columnas, índices, llaves foráneas y sembrado de datos.
72: 
73: ### 4. Frontend y Vistas (`04-frontend-and-views/`)
74: - [**01 - Vistas Blade**](04-frontend-and-views/01-vistas-blade.md): Sintaxis Blade nativa, directivas de control, layouts `@extends`, componentes anónimos `<x-* />` con atributos y slots.
75: - [**02 - Sesiones**](04-frontend-and-views/02-sesiones.md): Almacenamiento nativo de sesión, mensajes flash, persistencia de inputs con `old()`, protección CSRF y autenticación web.
76: - [**03 - Tailwind CSS**](04-frontend-and-views/03-tailwindcss.md): Configuración de compilación con Tailwind CSS v4, directivas de tema `@theme` y optimización.
77: - [**04 - React SPA**](04-frontend-and-views/04-reactapp-spa.md): Configuración de la Single Page Application alojada en `reactapp/` servida mediante Vite + TypeScript.
78: 
79: ### 5. Pruebas Automatizadas (`05-testing/`)
80: - [**01 - Testing HTTP Funcional**](05-testing/01-testing-http.md): Suite de pruebas funcionales en memoria con `MakesHttpRequests` y `TestResponse`. Pruebas completas de rutas, middlewares, validaciones y controladores sin necesidad de servidores web externos.
81: 
82: ### 6. Utilidades y Guías Prácticas (`06-advanced-and-guides/`)
83: - [**01 - Validaciones**](06-advanced-and-guides/01-validaciones.md): Catálogo exhaustivo de todas las reglas de validación nativas y reglas de base de datos (`unique`, `password_verify`).
84: - [**02 - Helpers**](06-advanced-and-guides/02-helpers.md): Catálogo de funciones globales (`view()`, `route()`, `asset()`, `session()`, `csrf_token()`, etc.).
85: - [**03 - Guía de Nuevo Módulo**](06-advanced-and-guides/03-guia-nuevo-modulo.md): Guía práctica paso a paso para implementar una funcionalidad completa desde la base de datos hasta la interfaz.
86: - [**04 - Depuración y Manejo de Errores**](06-advanced-and-guides/04-depuracion-y-errores.md): Guía completa de `dump()`, `d()`, `dd()`, formateo de consola CLI ANSI, panel oscuro interactivo en navegador y pantalla de excepciones con snippet de código en vivo.

---

## 🏛️ Arquitectura Global

- [**Visión General del Proyecto (PROYECTO_CRONOS.md)**](PROYECTO_CRONOS.md): Documento maestro de arquitectura, decisiones técnicas, roadmap y principios de diseño del framework. 
