# 13 - SPA con React + Tailwind (carpeta `reactapp`)

Guia completa para crear la SPA (Single Page Application) que maneja las paginas protegidas del framework usando **Vite + React + TypeScript + TailwindCSS v4 + React Router**.

> **Contexto:** el framework tiene dos mundos que conviven sin tocarse:
> - **SSR (PHP):** paginas publicas `/`, `/login`, `/register` → vistas en `resources/views/`, buen SEO.
> - **SPA (React):** paginas de prueba `/prueba/page1`, `/prueba/page2` → solo carga en `public/assets/spa/` tras compilar.
>
> La SPA se crea dentro de la carpeta **`reactapp/`** en la raiz del proyecto. Su build NO genera HTML propio: el framework sirve `resources/views/spa/index.php` que referencia los archivos compilados. Las paginas de prueba se eliminaran despues para crear las reales.

---

## 1. Requisitos

- Node.js >= 20
- npm

---

## 2. Crear el proyecto Vite

Ejecutar desde la raiz del proyecto:

```bash
npm create vite@latest
```

Respuestas:
- Nombre del proyecto: `reactapp`
- Framework: `React`
- Variante: `TypeScript`

Entrar e instalar dependencias base:

```bash
cd reactapp
npm install
```

---

## 3. Instalar y configurar TailwindCSS v4

```bash
npm install tailwindcss @tailwindcss/vite
```

### 3.1 Configurar el plugin en `reactapp/vite.config.ts`

Reemplazar el contenido por:

```ts
import { resolve } from 'node:path'
import { rmSync } from 'node:fs'
import { defineConfig, type Plugin } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

function removeIndexHtml(): Plugin {
  let outDir = ''

  return {
    name: 'remove-index-html',
    configResolved(config) {
      outDir = config.build.outDir
    },
    closeBundle() {
      rmSync(resolve(outDir, 'index.html'), { force: true })
    },
  }
}

export default defineConfig({
  plugins: [react(), tailwindcss(), removeIndexHtml()],
  build: {
    outDir: '../public/assets/spa',
    assetsDir: '',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name].[ext]',
      },
    },
  },
})
```

**Puntos clave de esta config:**

| Opcion | Por que |
|---|---|
| `outDir: '../public/assets/spa'` | El build se compila DENTRO de `public/assets/spa/`, junto a los assets del SSR (`public/assets/css`, `public/assets/js`) sin mezclarse. |
| `assetsDir: ''` | Evita crear una subcarpeta `assets/` anidada dentro de `spa/`. |
| `emptyOutDir: true` | Limpia la carpeta `spa/` en cada build (no quedan archivos viejos). |
| `entryFileNames/chunkFileNames/assetFileNames` | Usa nombres FIJOS `index.js` y `index.css` (SIN hash). Asi la vista PHP no se rompe al recompilar. |
| plugin `removeIndexHtml` | Elimina el `index.html` que Vite genera; el HTML lo sirve el framework via `resources/views/spa/index.php`. |

### 3.2 Limpiar los archivos de ejemplo

```bash
# Eliminar el CSS de ejemplo
del src\App.css

# Reemplazar TODO el contenido de src/index.css con:
@import "tailwindcss";
```

### 3.3 Reemplazar `src/App.tsx`

```tsx
const App = () => {
  return (
    <div className="flex justify-center items-center h-screen">
      <h1 className="text-3xl font-bold underline">React + TypeScript + Vite</h1>
    </div>
  )
}

export default App
```

---

## 4. Instalar dependencias de la SPA

```bash
npm i react-router
npm i axios
```

- **`react-router`** (v7): maneja las rutas internas de la SPA sin recargar la pagina.
- **`axios`**: cliente HTTP para consumir la API existente en `/api/*` (JWT con header `X-Token`).

---

## 5. Crear las paginas, layout y router

### 5.1 Regla IMPORTANTE de las rutas

> Las rutas internas de la SPA **NO deben chocar con las rutas existentes** de `routes/web.php` (`/`, `/login`, `/register`, `/dashboard`, etc.). Por eso se usa el prefijo `/prueba`. Cada vista/route creada en `reactapp` **tambien debe registrarse** en `routes/web.php` apuntando al `SpaController` (ver paso 7).

### 5.2 Crear dos paginas de prueba (genericas)

> Las paginas de esta SPA son SOLO para probar la integracion. Seran eliminadas posteriormente para crear las paginas reales. Por eso son genericas (`Page1`, `Page2`) y no de dashboard.

`reactapp/src/pages/Page1.tsx`:

```tsx
const Page1 = () => {
  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-800">Página 1</h1>
      <p className="mt-2 text-gray-600">Esta es una página de prueba genérica.</p>
    </div>
  )
}

export default Page1
```

`reactapp/src/pages/Page2.tsx`:

```tsx
const Page2 = () => {
  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-800">Página 2</h1>
      <p className="mt-2 text-gray-600">Esta es una página de prueba genérica.</p>
    </div>
  )
}

export default Page2
```

### 5.3 Crear el layout (simple, sin sidebar)

`reactapp/src/layouts/Layout.tsx`:

```tsx
import { Link, Outlet } from 'react-router'

const navItems = [
  { to: '/prueba/page1', label: 'Página 1' },
  { to: '/prueba/page2', label: 'Página 2' },
]

const Layout = () => {
  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-gray-900 text-white px-5 py-4">
        <div className="flex items-center gap-6">
          <h1 className="font-bold text-lg">Cronos SPA</h1>
          {navItems.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              className="text-gray-300 hover:text-white transition-colors"
            >
              {item.label}
            </Link>
          ))}
        </div>
      </nav>

      <main className="p-8">
        <Outlet />
      </main>
    </div>
  )
}

export default Layout
```

### 5.4 Configurar el router en `reactapp/src/App.tsx`

```tsx
import { createBrowserRouter } from 'react-router'
import Layout from './layouts/Layout'
import Page1 from './pages/Page1'
import Page2 from './pages/Page2'

const router = createBrowserRouter([
  {
    path: '/prueba',
    Component: Layout,
    children: [
      { path: 'page1', Component: Page1 },
      { path: 'page2', Component: Page2 },
    ],
  },
])

export default router
```

### 5.5 Actualizar `reactapp/src/main.tsx`

```tsx
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { RouterProvider } from 'react-router/dom'
import './index.css'
import router from './App.tsx'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <RouterProvider router={router} />
  </StrictMode>,
)
```

> **Nota:** `react-router` v7 importa `RouterProvider` desde `react-router/dom` (no `react-router-dom`).

---

## 6. Compilar el build

```bash
cd reactapp
npm run build
```

El resultado queda en `public/assets/spa/` con nombres fijos:

```
public/assets/spa/
├── favicon.svg        (copiado desde reactapp/public/)
├── icons.svg          (copiado desde reactapp/public/)
├── index.css
└── index.js
```

> `npm run dev` ejecuta el servidor de desarrollo de Vite (con hot reload) en `http://localhost:5173`.

---

## 7. Integrar con el framework PHP

### 7.1 Crear el controlador `App/Controllers/SpaController.php`

```php
<?php

namespace App\Controllers;

use Cronos\Http\Controller;


class SpaController extends Controller
{
    public function index()
    {
        return view('spa/index');
    }
}
```

### 7.2 Crear la vista `resources/views/spa/index.php`

Este es el `index.html` del SPA, servido por el framework. **Debe referenciar los archivos compilados con rutas absolutas**:

```html
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/assets/spa/favicon.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>reactapp</title>
</head>

<body>
    <div id="root"></div>
    <script type="module" crossorigin src="/assets/spa/index.js"></script>
    <link rel="stylesheet" crossorigin href="/assets/spa/index.css">
</body>

</html>
```

### 7.3 Registrar CADA ruta del SPA en `routes/web.php`

> **REGLA CLAVE:** por cada vista/ruta que se cree en `reactapp` (dentro de React Router), **tambien se debe agregar la ruta en `routes/web.php`** apuntando al `SpaController`, para que al recargar/abrir esa URL el framework devuelva `resources/views/spa/index.php` y la SPA funcione (deep-links).

```php
<?php

use Cronos\Routing\Route;
use App\Controllers\SpaController;

//Rutas SPA
Route::get('/prueba/page1', [SpaController::class, 'index']);
Route::get('/prueba/page2', [SpaController::class, 'index']);
```

---

## 8. Ignorar el build en git

Agregar al `.gitignore` de la raiz:

```
# Build SPA (React/Vite) - se genera con el build de reactapp/
/public/assets/spa/
```

El `reactapp/node_modules` ya esta ignorado por el propio `.gitignore` de `reactapp/`.

---

## 9. Como crear una pagina real (reemplazando las de prueba)

Las paginas `Page1` y `Page2` son de prueba. Para crear las paginas reales del proyecto:

1. Eliminar las paginas de prueba: `reactapp/src/pages/Page1.tsx`, `reactapp/src/pages/Page2.tsx` y el layout `reactapp/src/layouts/Layout.tsx` (o adaptarlo).
2. Crear los componentes reales en `reactapp/src/pages/` (ej. `Dashboard.tsx`, `Blogs.tsx`).
3. Crear el layout real (ej. con sidebar) en `reactapp/src/layouts/`.
4. Actualizar el router en `reactapp/src/App.tsx` con las rutas reales.
5. Compilar: `cd reactapp && npm run build`.
6. Actualizar `routes/web.php`: eliminar las rutas de prueba y registrar cada ruta real con `Route::get('/ruta', [SpaController::class, 'index'])`.
7. Probar en el navegador.

---

## 10. Notas y advertencias

- **No editar `public/assets/spa/`**: es el build generado; se borra y recrea en cada `npm run build`.
- **No editar `resources/views/spa/index.php` para rutas de archivos**: solo se toca si cambian los nombres de los assets (favicon, etc.). Los nombres `index.js`/`index.css` son fijos por config.
- **Cache del navegador**: como los nombres son fijos (sin hash), el navegador puede mostrar una version vieja. Si al compilar cambios no se ven, limpiar cache (Ctrl+F5) o subir `?v=` en la vista manualmente.
- **Tailwind CSS del SPA es independiente** del `tailwindcss.exe` de la raiz (que compila el CSS del SSR). No se cruzan: salidas distintas.
- **API**: la SPA debe consumir `routes/api.php` (JWT, header `X-Token`). El token se guarda en `localStorage` y se envia con axios via interceptor.
- **SEO**: las paginas protegidas (`/prueba/*`) no deben indexarse; solo las publicas SSR tienen SEO.

---

> **Anterior**: [12 - TailwindCSS](12-tailwindcss.md)
