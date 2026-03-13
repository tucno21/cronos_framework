# CONTEXTO DEL PROYECTO

Estás trabajando en **Cronos Framework**, un mini-framework PHP con estructura MVC inspirado en Laravel. El proyecto ya existe y tiene la siguiente estructura de carpetas relevante para el frontend:

```
cronos_framework/
├── public/
│   └── assets/               ← Aquí van los CSS compilados (OUTPUT de Tailwind)
│       ├── index.css
│       ├── cronos.dashboard.css
│       └── ...
├── resources/
│   └── views/                ← Aquí están las vistas PHP (donde Tailwind escanea clases)
│       ├── home/
│       │   ├── layouts/
│       │   │   ├── head.php
│       │   │   └── footer.php
│       │   ├── index.php
│       │   ├── login.php
│       │   └── register.php
│       ├── dashboard/
│       │   ├── layouts/
│       │   │   ├── head.php
│       │   │   └── footer.php
│       │   ├── index.php
│       │   ├── show.php
│       │   └── create.php
│       └── error/
│           └── 404.php
```

---

## TAREA PRINCIPAL

Implementa **TailwindCSS v4** usando **Tailwind CLI standalone** (sin Node.js, sin npm). La implementación debe:

1. Usar **múltiples archivos CSS** separados por grupo de vistas (NO un solo archivo global)
2. Tener configuración para **desarrollo** (con watcher) y **producción** (minificado y optimizado)
3. Actualizar el archivo `PROYECTO_CRONOS.md` con la documentación completa de la implementación

---

## ⚠️ ADVERTENCIA CRÍTICA — DIFERENCIAS TAILWIND v3 vs v4

> **Eres una IA que puede tener conocimiento de Tailwind v3. DEBES usar exclusivamente la sintaxis de Tailwind v4.** Las siguientes diferencias son obligatorias:

### ❌ NO USES (Tailwind v3 — OBSOLETO):
```js
// tailwind.config.js  ← NO EXISTE en v4
module.exports = {
  content: ['./resources/**/*.php'],
  theme: { extend: {} },
  plugins: [],
}
```
```css
/* ❌ Directivas v3 obsoletas */
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### ✅ USA ESTO (Tailwind v4 — CORRECTO):
```css
/* Archivo CSS fuente — directiva única de v4 */
@import "tailwindcss";

/* Personalización se hace aquí con @theme */
@theme {
  --color-primary: #3b82f6;
  --font-sans: "Inter", sans-serif;
}
```

### Cambios clave en v4:
- **No hay `tailwind.config.js`** — la configuración va en el CSS con `@theme {}`
- **No hay `@tailwind base/components/utilities`** — se reemplaza por `@import "tailwindcss"`
- **El CLI v4 detecta automáticamente** las clases usadas en los archivos que escanea
- **Los archivos a escanear** se especifican con `--input` y el CLI los detecta desde las importaciones
- **Para escanear archivos extra** (como vistas PHP) se usa `@source` en el CSS fuente:
  ```css
  @import "tailwindcss";
  @source "../../resources/views/home/**/*.php";
  ```
- **El binario CLI se descarga directamente** desde: `https://github.com/tailwindlabs/tailwindcss/releases/latest`

---

## PASO 1 — Descargar Tailwind CLI v4

Crea el script `tailwind-setup.sh` en la raíz del proyecto:

```bash
#!/bin/bash
# Descargar Tailwind CLI v4 standalone (sin Node.js)
# Detectar OS y arquitectura automáticamente

OS=$(uname -s | tr '[:upper:]' '[:lower:]')
ARCH=$(uname -m)

if [ "$ARCH" = "x86_64" ]; then ARCH="x64"; fi
if [ "$ARCH" = "aarch64" ]; then ARCH="arm64"; fi

echo "Descargando Tailwind CLI v4 para $OS-$ARCH..."

curl -sLO "https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-$OS-$ARCH"
mv "tailwindcss-$OS-$ARCH" tailwindcss
chmod +x tailwindcss

echo "✓ Tailwind CLI v4 instalado: $(./tailwindcss --version)"
```

> **Nota para Windows:** Descargar `tailwindcss-windows-x64.exe` y renombrar a `tailwindcss.exe`

---

## PASO 2 — Estructura de Archivos CSS a Crear

Implementa la siguiente estructura (múltiples archivos por grupo de vistas):

```
cronos_framework/
├── resources/
│   └── css/                         ← NUEVA carpeta (archivos CSS FUENTE)
│       ├── app.css                  ← CSS base/compartido (variables, reset, componentes globales)
│       ├── home.css                 ← CSS para vistas: home/, login, register
│       ├── dashboard.css            ← CSS para vistas: dashboard/
│       └── error.css                ← CSS para vistas: error/
├── public/
│   └── assets/                      ← CSS COMPILADOS (output de Tailwind)
│       ├── app.css                  ← Compilado del base (si se usa standalone)
│       ├── home.css                 ← Compilado para home
│       ├── dashboard.css            ← Compilado para dashboard
│       └── error.css                ← Compilado para error
```

---

## PASO 3 — Contenido de los Archivos CSS Fuente

### `resources/css/app.css` (base compartido — NO se compila solo, se importa):
```css
/* Cronos Framework — Estilos base compartidos */
/* Este archivo define variables globales y utilidades comunes */

@theme {
  /* Colores personalizados del proyecto */
  --color-primary: #3b82f6;
  --color-primary-dark: #1d4ed8;
  --color-secondary: #64748b;
  --color-danger: #ef4444;
  --color-success: #22c55e;
  --color-warning: #f59e0b;

  /* Tipografía */
  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
  --font-mono: "JetBrains Mono", ui-monospace, monospace;

  /* Breakpoints personalizados (opcionales) */
  --breakpoint-xs: 475px;
}

/* Componentes globales reutilizables */
@layer components {
  .btn {
    @apply inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-colors duration-200;
  }
  .btn-primary {
    @apply btn bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500;
  }
  .btn-danger {
    @apply btn bg-red-600 text-white hover:bg-red-700;
  }
  .input-field {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500;
  }
  .card {
    @apply bg-white rounded-xl shadow-sm border border-gray-200 p-6;
  }
  .alert-error {
    @apply bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg;
  }
  .alert-success {
    @apply bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg;
  }
}
```

### `resources/css/home.css` (vistas de home, login, register):
```css
/* Cronos Framework — Estilos para Home, Login y Register */
@import "tailwindcss";

/* Escanear SOLO las vistas correspondientes a este grupo */
@source "../../resources/views/home/**/*.php";

/* Importar variables y componentes base */
@import "./app.css";

/* Estilos específicos del grupo home */
@layer components {
  .hero-section {
    @apply min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100;
  }
  .auth-card {
    @apply card max-w-md w-full mx-auto;
  }
  .auth-title {
    @apply text-2xl font-bold text-gray-800 mb-6 text-center;
  }
}
```

### `resources/css/dashboard.css` (vistas de dashboard):
```css
/* Cronos Framework — Estilos para Dashboard */
@import "tailwindcss";

/* Escanear SOLO las vistas del dashboard */
@source "../../resources/views/dashboard/**/*.php";

/* Importar variables y componentes base */
@import "./app.css";

/* Estilos específicos del dashboard */
@layer components {
  .sidebar {
    @apply fixed left-0 top-0 h-full w-64 bg-gray-900 text-white flex flex-col;
  }
  .sidebar-link {
    @apply flex items-center gap-3 px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors;
  }
  .sidebar-link.active {
    @apply bg-blue-600 text-white;
  }
  .main-content {
    @apply ml-64 min-h-screen bg-gray-50 p-8;
  }
  .page-title {
    @apply text-2xl font-bold text-gray-800 mb-6;
  }
  .data-table {
    @apply w-full bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden;
  }
  .data-table th {
    @apply bg-gray-50 px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider;
  }
  .data-table td {
    @apply px-6 py-4 text-sm text-gray-700 border-t border-gray-100;
  }
}
```

### `resources/css/error.css` (vistas de error):
```css
/* Cronos Framework — Estilos para páginas de error */
@import "tailwindcss";

/* Escanear vistas de error */
@source "../../resources/views/error/**/*.php";

/* Importar base */
@import "./app.css";

@layer components {
  .error-page {
    @apply min-h-screen flex flex-col items-center justify-center bg-gray-50 text-center;
  }
  .error-code {
    @apply text-8xl font-black text-blue-600 mb-4;
  }
  .error-message {
    @apply text-xl text-gray-600 mb-8;
  }
}
```

---

## PASO 4 — Scripts de Compilación

### Para DESARROLLO (con watcher — recarga automática):

```bash
# Compilar home.css en modo watch (desarrollo)
./tailwindcss -i resources/css/home.css -o public/assets/home.css --watch

# Compilar dashboard.css en modo watch (desarrollo)
./tailwindcss -i resources/css/dashboard.css -o public/assets/dashboard.css --watch

# Compilar error.css en modo watch (desarrollo)
./tailwindcss -i resources/css/error.css -o public/assets/error.css --watch
```

### Para PRODUCCIÓN (minificado y optimizado):

```bash
# Compilar home.css para producción
./tailwindcss -i resources/css/home.css -o public/assets/home.css --minify

# Compilar dashboard.css para producción
./tailwindcss -i resources/css/dashboard.css -o public/assets/dashboard.css --minify

# Compilar error.css para producción
./tailwindcss -i resources/css/error.css -o public/assets/error.css --minify
```

---

## PASO 5 — Crear Scripts de Automatización

Crea estos archivos en la raíz del proyecto:

### `tailwind-dev.sh` (desarrollo — compila todos en watch):
```bash
#!/bin/bash
# Cronos Framework — Compilar Tailwind en modo DESARROLLO
# Ejecuta todos los watchers en paralelo

echo "🔧 Iniciando Tailwind CSS v4 en modo DESARROLLO..."
echo "   Presiona Ctrl+C para detener todos los procesos"

./tailwindcss -i resources/css/home.css      -o public/assets/home.css      --watch &
./tailwindcss -i resources/css/dashboard.css -o public/assets/dashboard.css --watch &
./tailwindcss -i resources/css/error.css     -o public/assets/error.css     --watch &

echo "✓ Watchers activos para: home.css, dashboard.css, error.css"
wait
```

### `tailwind-build.sh` (producción — compila y minifica todo):
```bash
#!/bin/bash
# Cronos Framework — Compilar Tailwind en modo PRODUCCIÓN
# Genera archivos minificados y optimizados

echo "🚀 Compilando Tailwind CSS v4 para PRODUCCIÓN..."

./tailwindcss -i resources/css/home.css      -o public/assets/home.css      --minify
echo "✓ home.css compilado"

./tailwindcss -i resources/css/dashboard.css -o public/assets/dashboard.css --minify
echo "✓ dashboard.css compilado"

./tailwindcss -i resources/css/error.css     -o public/assets/error.css     --minify
echo "✓ error.css compilado"

echo ""
echo "✅ Build de producción completado. Archivos en public/assets/"
```

Dar permisos de ejecución:
```bash
chmod +x tailwind-dev.sh tailwind-build.sh tailwind-setup.sh
```

---

## PASO 6 — Actualizar las Vistas para Usar los CSS Correctos

### `resources/views/home/layouts/head.php`:
```php
<!-- Usar el CSS compilado correspondiente a este grupo de vistas -->
<link rel="stylesheet" href="/assets/home.css">
```

### `resources/views/dashboard/layouts/head.php`:
```php
<!-- CSS específico del dashboard -->
<link rel="stylesheet" href="/assets/dashboard.css">
```

### `resources/views/error/404.php` (en el head):
```php
<link rel="stylesheet" href="/assets/error.css">
```

---

## PASO 7 — Agregar al .gitignore

Agregar al archivo `.gitignore` existente:
```gitignore
# Tailwind CLI binary
/tailwindcss
/tailwindcss.exe

# CSS compilados (se generan con los scripts)
/public/assets/home.css
/public/assets/dashboard.css
/public/assets/error.css
# NO ignorar los CSS fuente en resources/css/
```

---

## PASO 8 — Actualizar PROYECTO_CRONOS.md

Después de implementar todo lo anterior, actualiza el archivo `PROYECTO_CRONOS.md` agregando:

### En la sección `## 2. Árbol de Archivos y Carpetas`, agrega dentro del árbol:

```
├── resources/
│   ├── css/                         # Archivos CSS fuente de TailwindCSS v4
│   │   ├── app.css                  # Variables @theme y componentes globales compartidos
│   │   ├── home.css                 # Estilos para vistas: home, login, register
│   │   ├── dashboard.css            # Estilos para vistas: dashboard
│   │   └── error.css                # Estilos para vistas: error
│   └── views/                       # (existente)
│       └── ...
├── public/
│   └── assets/                      # CSS compilados (OUTPUT — generados por Tailwind CLI)
│       ├── home.css                 # Compilado de home.css
│       ├── dashboard.css            # Compilado de dashboard.css
│       └── error.css               # Compilado de error.css
├── tailwindcss                      # Binario CLI de Tailwind v4 (en .gitignore)
├── tailwind-dev.sh                  # Script: compilar en modo desarrollo (watch)
└── tailwind-build.sh                # Script: compilar en modo producción (minify)
```

### Agrega una nueva sección al final del documento `## 15. TailwindCSS v4`:

```markdown
## 15. TailwindCSS v4 — Frontend

### Instalación y herramienta

Cronos Framework usa **TailwindCSS v4 CLI standalone** (sin Node.js ni npm). El binario `tailwindcss` 
se descarga directamente desde GitHub Releases y se ejecuta como comando local.

- **CLI descargado desde:** `https://github.com/tailwindlabs/tailwindcss/releases/latest`
- **Binario:** `./tailwindcss` en la raíz del proyecto (en .gitignore)
- **Versión:** v4.x (NO v3)

### Diferencia crítica v3 → v4

| Característica       | Tailwind v3 (OBSOLETO)            | Tailwind v4 (ACTUAL)              |
|----------------------|-----------------------------------|-----------------------------------|
| Config               | `tailwind.config.js`              | ❌ No existe — va en CSS con `@theme {}` |
| Directivas CSS       | `@tailwind base/components/utilities` | `@import "tailwindcss"`       |
| Escaneo de archivos  | `content: [...]` en config.js     | `@source "ruta/**/*.php"` en CSS  |
| Personalización      | `theme.extend` en config.js       | `@theme { --variable: valor; }` en CSS |

### Estructura de archivos CSS

Los CSS están organizados por grupo de vistas para optimizar el tamaño de cada archivo:

| Archivo fuente                  | Vistas que cubre                     | CSS compilado              |
|---------------------------------|--------------------------------------|----------------------------|
| `resources/css/app.css`         | Variables y componentes compartidos  | (importado por los demás)  |
| `resources/css/home.css`        | home/, login.php, register.php       | `public/assets/home.css`   |
| `resources/css/dashboard.css`   | dashboard/ (todas sus vistas)        | `public/assets/dashboard.css` |
| `resources/css/error.css`       | error/404.php (y otros errores)      | `public/assets/error.css`  |

### Comandos de uso

#### Desarrollo (con recarga automática):
```bash
./tailwind-dev.sh        # Inicia watchers para todos los grupos en paralelo
```

O individualmente:
```bash
./tailwindcss -i resources/css/home.css -o public/assets/home.css --watch
```

#### Producción (minificado):
```bash
./tailwind-build.sh      # Compila y minifica todos los grupos
```

O individualmente:
```bash
./tailwindcss -i resources/css/home.css -o public/assets/home.css --minify
```

### Por qué múltiples archivos CSS

Cada grupo de vistas tiene su propio CSS compilado para que Tailwind **solo incluya las clases 
usadas en ese grupo específico**. Esto significa:

- `home.css` solo contiene utilidades usadas en home/login/register
- `dashboard.css` solo contiene utilidades usadas en el dashboard
- El resultado es archivos CSS más pequeños y carga más rápida por página

### Cómo agregar un nuevo grupo de vistas

1. Crear `resources/css/nuevo-grupo.css` con `@import "tailwindcss"` y `@source` apuntando a las vistas
2. Agregar a `tailwind-dev.sh` la línea de watch correspondiente
3. Agregar a `tailwind-build.sh` la línea de minify correspondiente
4. Incluir el CSS compilado en el layout del nuevo grupo

### Notas importantes

- Los archivos en `public/assets/*.css` son **generados** — no editarlos manualmente
- Los archivos en `resources/css/*.css` son los **fuentes** — editarlos con las clases personalizadas
- El binario `tailwindcss` está en `.gitignore` — cada desarrollador debe ejecutar `tailwind-setup.sh`
- En producción, ejecutar `./tailwind-build.sh` antes del deploy
```

---

## PASO 9 — Migrar las Vistas Existentes a TailwindCSS v4

Las vistas ya usan clases de Tailwind en el HTML. Debes migrarlas para que:
1. **Eliminen las fuentes de Google Fonts** cargadas externamente (se define la fuente en `@theme`)
2. **Apunten al CSS compilado correcto** según el grupo al que pertenecen
3. **Conserven todas las clases Tailwind existentes** — solo se cambia el `<link>` del CSS y la fuente

---

### Vista: `resources/views/home/layouts/head.php`

**ANTES (código actual):**
```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?? 'Crosos Framework' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= base_url . '/assets/index.css' ?>" rel="stylesheet">
</head>
<body class="font-[Poppins]">
```

**DESPUÉS (actualizar a):**
```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?? 'Cronos Framework' ?></title>
    <!-- TailwindCSS v4 compilado para el grupo home/login/register -->
    <link href="<?= base_url . '/assets/home.css' ?>" rel="stylesheet">
</head>
<body class="font-sans">
```

> **Nota:** La fuente Poppins ya no se carga desde Google Fonts. Debe declararse en `resources/css/app.css` dentro de `@theme`:
> ```css
> @theme {
>   --font-sans: "Poppins", ui-sans-serif, system-ui, sans-serif;
> }
> ```
> Y agregar el `@import` de Google Fonts dentro del CSS fuente `resources/css/home.css`:
> ```css
> @import "tailwindcss";
> @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap");
> @source "../../resources/views/home/**/*.php";
> @import "./app.css";
> ```
> Así la fuente queda gestionada desde el CSS y no desde el HTML.

---

### Vista: `resources/views/dashboard/layouts/head.php`

**ANTES (código actual):**
```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?? 'Crosos Framework' ?></title>
    <link href="<?= base_url . '/assets/croonos.dashboard.css' ?>" rel="stylesheet">
</head>
<body class="h-screen flex">
```

**DESPUÉS (actualizar a):**
```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?? 'Cronos Framework' ?></title>
    <!-- TailwindCSS v4 compilado para el grupo dashboard -->
    <link href="<?= base_url . '/assets/dashboard.css' ?>" rel="stylesheet">
</head>
<body class="h-screen flex">
```

> **Nota:** Se corrige el typo en el nombre del archivo (`croonos` → `cronos`) y apunta al nuevo archivo compilado `dashboard.css`.

---

### Clases de Dashboard que usan CSS personalizado (`.sidebar`, `.top-menu`, etc.)

El dashboard actual usa clases como `.sidebar`, `.active-link`, `.top-menu`, `.top-menu-dropdown`, `.singleMenu`, etc. que **NO son clases de Tailwind** — son clases personalizadas definidas en el CSS anterior (`cronos.dashboard.css`).

Estas clases deben migrarse a `resources/css/dashboard.css` usando `@layer components` con `@apply`:

```css
/* resources/css/dashboard.css */
@import "tailwindcss";
@source "../../resources/views/dashboard/**/*.php";
@import "./app.css";

/* Bootstrap Icons (si se usa en el dashboard) */
@import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css");

@layer components {

  /* === SIDEBAR === */
  .sidebar {
    @apply fixed top-0 h-screen w-56 flex flex-col transition-all duration-300 z-30;
  }
  .sidebar-logo {
    @apply flex items-center justify-between px-4 py-5 border-b border-slate-700;
  }

  /* === MENU ITEMS === */
  .singleMenu {
    @apply flex items-center gap-3 px-4 py-2.5 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors duration-200 text-sm font-medium;
  }
  .active-link {
    @apply bg-blue-600 text-white hover:bg-blue-700;
  }

  /* === TOP MENU === */
  .top-menu {
    @apply flex items-center justify-between h-14 border-b border-gray-700 sticky top-0 z-20;
  }
  .top-menu-movil {
    @apply text-white text-xl p-1;
  }
  .top-menu-laptop {
    @apply text-gray-300 text-xl cursor-pointer hover:text-white transition-colors;
  }

  /* === DROPDOWN USER === */
  .top-menu-dropdown {
    @apply absolute right-0 top-10 w-52 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50 overflow-hidden;
  }
  .top-menu-dropdown-link {
    @apply flex items-center px-4 py-3 text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition-colors;
  }
}
```

> **IMPORTANTE:** Revisa el archivo `public/assets/cronos.dashboard.css` original del proyecto para migrar **todas** las clases personalizadas que encuentres ahí. No inventes estilos — mígralos tal como están, solo tradúcelos a `@apply` con clases de Tailwind equivalentes.

---

## RESUMEN DE ARCHIVOS A CREAR/MODIFICAR

| Acción    | Archivo                                | Descripción                                  |
|-----------|----------------------------------------|----------------------------------------------|
| CREAR     | `resources/css/app.css`                | CSS base con @theme y componentes globales   |
| CREAR     | `resources/css/home.css`               | CSS para grupo home/login/register           |
| CREAR     | `resources/css/dashboard.css`          | CSS para grupo dashboard                     |
| CREAR     | `resources/css/error.css`              | CSS para páginas de error                    |
| CREAR     | `tailwind-setup.sh`                    | Script para descargar el CLI                 |
| CREAR     | `tailwind-dev.sh`                      | Script de desarrollo (watch)                 |
| CREAR     | `tailwind-build.sh`                    | Script de producción (minify)                |
| MODIFICAR | `resources/views/home/layouts/head.php`      | Quitar Google Fonts del HTML, cambiar link a `/assets/home.css`, cambiar `font-[Poppins]` por `font-sans` |
| MODIFICAR | `resources/views/dashboard/layouts/head.php` | Cambiar link a `/assets/dashboard.css`, corregir typo `croonos` → `cronos` |
| MODIFICAR | `resources/views/error/404.php`        | Agregar link a `/assets/error.css`           |
| MODIFICAR | `.gitignore`                           | Ignorar binario y CSS compilados             |
| MODIFICAR | `PROYECTO_CRONOS.md`                   | Agregar árbol CSS y sección 15               |

---
