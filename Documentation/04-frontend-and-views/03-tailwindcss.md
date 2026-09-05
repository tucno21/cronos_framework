# TailwindCSS v4 — Guia de Uso y Actualizacion

Cronos Framework usa **TailwindCSS v4 CLI standalone** (sin Node.js ni npm). El binario `tailwindcss` se descarga directamente desde GitHub Releases y se ejecuta como comando local.

## Diferencia Critica: v3 vs v4

| Caracteristica | Tailwind v3 (Obsoleto) | Tailwind v4 (Actual) |
|---|---|---|
| Config | `tailwind.config.js` | No existe — va en CSS con `@theme {}` |
| Directivas CSS | `@tailwind base/components/utilities` | `@import "tailwindcss"` |
| Escaneo de archivos | `content: [...]` en config.js | `@source "ruta/**/*.php"` en CSS |
| Personalizacion | `theme.extend` en config.js | `@theme { --variable: valor; }` en CSS |

## Estructura de Archivos CSS

Los CSS estan organizados por grupo de vistas para optimizar el tamano de cada archivo:

| Archivo fuente | Vistas que cubre | CSS compilado |
|---|---|---|
| `resources/css/app.css` | Variables y componentes compartidos | (importado por los demas) |
| `resources/css/home.css` | home/, login.php, register.php | `public/assets/css/home.css` |
| `resources/css/dashboard.css` | dashboard/ (todas sus vistas) | `public/assets/css/dashboard.css` |
| `resources/css/error.css` | error/404.php | `public/assets/css/error.css` |

> **Los archivos en `public/assets/css/*.css` son generados** — no editarlos manualmente.
> **Los archivos en `resources/css/*.css` son los fuentes** — editar estos.

## Estructura de un Archivo CSS Fuente

```css
@import "tailwindcss";

/* Escanear SOLO las vistas correspondientes a este grupo */
@source "../../resources/views/home/**/*.php";

/* Incluir componentes x- para que sus clases se compilen */
@source "../../resources/views/components/**/*.php";

/* Importar variables y componentes base */
@import "./app.css";

/* Estilos personalizados del grupo */
@layer components {
  .mi-clase {
    @apply bg-blue-500 text-white px-4 py-2 rounded;
  }
}
```

### Configuracion de @source para Componentes x-

Los componentes `<x-nombre>` usan clases de Tailwind. Para que Tailwind las incluya en el CSS compilado, cada archivo CSS fuente debe tener una linea `@source` apuntando a `resources/views/components/`:

```css
@source "../../resources/views/components/**/*.php";
```

Sin esta linea, los componentes `<x-card>`, `<x-button>`, etc. apareceran en el HTML pero **sin estilos** porque el compilador no escaneo sus clases.

### Variables Globales con @theme

El archivo `resources/css/app.css` contiene las variables compartidas:

```css
@import "tailwindcss";

@theme {
  --color-primary: #1e40af;
  --color-secondary: #64748b;
  /* Mas variables globales... */
}

@layer components {
  /* Componentes compartidos entre todas las vistas */
}
```

## Comandos de Uso

### Desarrollo (con recarga automatica)

**Windows:**
```bash
.\tailwind-dev.bat
```

**Linux/Mac:**
```bash
./tailwind-dev.sh
```

Individualmente:
```bash
# Windows
.\tailwindcss -i resources/css/home.css -o public/assets/css/home.css --watch

# Linux/Mac
./tailwindcss -i resources/css/home.css -o public/assets/css/home.css --watch
```

### Produccion (minificado)

**Windows:**
```bash
.\tailwind-build.bat
```

**Linux/Mac:**
```bash
./tailwind-build.sh
```

Individualmente:
```bash
# Windows
.\tailwindcss -i resources/css/home.css -o public/assets/css/home.css --minify

# Linux/Mac
./tailwindcss -i resources/css/home.css -o public/assets/css/home.css --minify
```

### Verificar Version

```bash
# Windows
.\tailwindcss --version

# Linux/Mac
./tailwindcss --version
```

## Incluir CSS en las Vistas

Usar la directiva `@asset` para cache-busting automatico:

```php
<head>
    <link href="@asset('assets/css/home.css')" rel="stylesheet">
    @stack('styles')
</head>
<body>
    @yield('content')
    <script src="@asset('assets/js/home.js')"></script>
    @stack('scripts')
</body>
```

`@asset()` genera URLs del tipo: `http://proyecto.test/assets/css/home.css?v=1748291234`. El numero es el `filemtime()` del archivo, asi que si el archivo cambia, el navegador descarga la nueva version.

## Agregar un Nuevo Grupo de Vistas con CSS

Paso a paso para agregar un nuevo grupo (ejemplo: "admin"):

**1. Crear el archivo CSS fuente:**

```css
/* resources/css/admin.css */
@import "tailwindcss";

@source "../../resources/views/admin/**/*.php";
@source "../../resources/views/components/**/*.php";

@import "./app.css";

@layer components {
  .admin-layout {
    @apply min-h-screen bg-gray-100;
  }
  .admin-sidebar {
    @apply w-64 bg-gray-900 text-white fixed h-full;
  }
}
```

**2. Actualizar los scripts de compilacion:**

Agregar en `tailwind-dev.bat` (Windows):
```batch
start "Tailwind - Admin" cmd /k "tailwindcss -i resources/css/admin.css -o public/assets/css/admin.css --watch"
```

Agregar en `tailwind-dev.sh` (Linux/Mac):
```bash
./tailwindcss -i resources/css/admin.css -o public/assets/css/admin.css --watch &
```

Agregar en `tailwind-build.bat` (Windows):
```batch
echo Compilando admin.css...
tailwindcss -i resources/css/admin.css -o public/assets/css/admin.css --minify
if %errorlevel% neq 0 (
    echo [ERROR] Error compilando admin.css
    exit /b %errorlevel%
)
echo [OK] admin.css compilado
```

Agregar en `tailwind-build.sh` (Linux/Mac):
```bash
echo "Compilando admin.css..."
./tailwindcss -i resources/css/admin.css -o public/assets/css/admin.css --minify
echo "admin.css compilado"
```

**3. Agregar a `.gitignore`:**
```
/public/assets/css/admin.css
```

**4. Incluir en el layout de las vistas:**
```php
<!-- resources/views/admin/layouts/head.php -->
<link href="@asset('assets/css/admin.css')" rel="stylesheet">
```

**5. Compilar y probar:**
```bash
# Compilar
.\tailwind-build.bat    # Windows
./tailwind-build.sh     # Linux/Mac

# Probar en desarrollo
.\tailwind-dev.bat      # Windows
./tailwind-dev.sh       # Linux/Mac
```

## Actualizar TailwindCSS a una Nueva Version

**1. Verificar version actual:**
```bash
.\tailwindcss --version    # Windows
./tailwindcss --version    # Linux/Mac
```

**2. Descargar el nuevo binario:**

Windows (PowerShell):
```powershell
Invoke-WebRequest -Uri "https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-windows-x64.exe" -OutFile "tailwindcss.exe"
```

Linux/Mac:
```bash
OS=$(uname -s | tr '[:upper:]' '[:lower:]')
ARCH=$(uname -m)

if [ "$ARCH" = "x86_64" ]; then ARCH="x64"; fi
if [ "$ARCH" = "aarch64" ]; then ARCH="arm64"; fi

curl -sLO "https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-$OS-$ARCH"
mv "tailwindcss-$OS-$ARCH" tailwindcss
chmod +x tailwindcss
```

O descargar manualmente desde: https://github.com/tailwindlabs/tailwindcss/releases/latest

**3. Verificar la actualizacion:**
```bash
.\tailwindcss --version    # Windows
./tailwindcss --version    # Linux/Mac
```

**4. Recompilar todos los CSS:**
```bash
.\tailwind-build.bat    # Windows
./tailwind-build.sh     # Linux/Mac
```

**5. Probar que todo funciona:**
- Abrir el sitio web
- Verificar que los estilos cargan correctamente
- Abrir la consola del navegador (F12) para verificar que no hay errores

## Notas Importantes

- El binario `tailwindcss` esta en `.gitignore` — cada desarrollador debe ejecutar `tailwind-setup.bat` o `tailwind-setup.sh`
- TailwindCSS v4 es compatible hacia atras con v4.x, pero **no** con v3.x
- Si hay breaking changes en una nueva version, revisar: https://github.com/tailwindlabs/tailwindcss/blob/master/CHANGELOG.md
- No es necesario actualizar `resources/css/*.css` a menos que la nueva version tenga breaking changes
- Cada grupo de vistas tiene su propio CSS para que Tailwind solo incluya las clases usadas en ese grupo, resultando en archivos mas pequenos

## Scripts de Setup Inicial

Si un nuevo desarrollador se une al proyecto:

```bash
# Windows
.\tailwind-setup.bat

# Linux/Mac
chmod +x tailwind-setup.sh
./tailwind-setup.sh
```

Esto descarga el binario CLI de Tailwind v4. Despues compilar con `tailwind-build.bat` o `tailwind-build.sh`.

---

> **Anterior**: [11 - Guia Nuevo Modulo](11-guia-nuevo-modulo.md)
