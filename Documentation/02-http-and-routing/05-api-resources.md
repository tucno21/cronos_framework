# API Resources (Transformadores JSON)

> **AVISO CRÍTICO PARA DESARROLLADORES E INTELIGENCIAS ARTIFICIALES (IAs)**
>
> Cronos **NO es Laravel**. Aunque `JsonResource` está diseñado con una API inspirada en Laravel para formatear salidas JSON de manera uniforme y elegante, **Cronos utiliza su propio motor de respuestas HTTP (`Cronos\Http\Response`), colecciones (`ResourceCollection`) y enrutador (`Router`)**.
>
> **Regla para IAs y desarrolladores:**
> - **NO uses clases ni namespaces de Illuminate** (como `Illuminate\Http\Resources\Json\JsonResource`).
> - Usa exclusivamente `Cronos\Http\JsonResource` y `Cronos\Http\ResourceCollection`.
> - Los recursos en Cronos se integran directamente con el router o mediante `$resource->toResponse()`.

---

## 1. ¿Qué es un API Resource en Cronos?

Al crear APIs RESTful, devolver directamente modelos de Eloquent/Cronos con `$modelo->toArray()` tiene riesgos e inconvenientes:
- **Acoplamiento:** El cliente queda acoplado a la estructura de las columnas en la base de datos.
- **Fuga de datos sensibles:** Atributos como contraseñas, tokens internos o columnas de auditoría pueden ser expuestos accidentalmente.
- **Formateo manual repetitivo:** Enriquecer respuestas con relaciones, cálculos o fechas requiere código duplicado en múltiples controladores.

Un **API Resource** actúa como una **capa de transformación intermedia** entre tus modelos de base de datos y la respuesta JSON final entregada al cliente.

---

## 2. Cómo Crear un API Resource

### Vía CLI (Recomendado)
Puedes generar un recurso rápidamente usando la consola de Cronos:

```bash
# Crear en App/Resources/PublicacionResource.php
php cronos make:resource PublicacionResource

# Si omites el sufijo "Resource", Cronos lo agrega automáticamente:
php cronos make:resource Publicacion
# Genera: App/Resources/PublicacionResource.php

# Crear dentro de una subcarpeta (ej: App/Resources/Admin/UserResource.php):
php cronos make:resource UserResource Admin
```

---

## 3. Estructura de la Clase

Una clase que extiende de `JsonResource` solo necesita definir el método `toArray()`:

```php
<?php

namespace App\Resources;

use Cronos\Http\JsonResource;

class PublicacionResource extends JsonResource
{
    /**
     * Transforma el recurso a un array estructurado.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'titulo'         => $this->titulo,
            'slug'           => $this->slug,
            'extracto'       => substr((string)$this->contenido, 0, 100) . '...',
            'vistas'         => (int) $this->vistas,
            'autor'          => $this->usuario->nombre ?? 'Anónimo',
            // Inclusión condicional de relaciones o campos:
            'categoria'      => $this->whenLoaded('categoria', fn() => [
                'id'     => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
            ]),
            'fecha_creacion' => date('d/m/Y H:i', strtotime((string)$this->created_at)),
        ];
    }
}
```

> **Acceso a Propiedades:** Puedes acceder a las propiedades del modelo subyacente usando directamente `$this->nombre_propiedad`.

---

## 4. Uso en Controladores

### A. Retornar un Recurso Individual
Puedes retornar directamente el recurso desde la acción de tu controlador (el Router de Cronos lo convierte automáticamente en una respuesta HTTP JSON) o llamar a `toResponse()`:

```php
use App\Models\Publicacion;
use App\Resources\PublicacionResource;

class PublicacionController
{
    public function show(Publicacion $publicacion)
    {
        // El router de Cronos detecta el JsonResource y genera Response::json() automaticamente:
        return new PublicacionResource($publicacion);
    }
}
```

### B. Retornar una Colección de Recursos (`::collection()`)
Para transformar listas o colecciones de modelos:

```php
public function index()
{
    $publicaciones = Publicacion::with('usuario', 'categoria')->get();

    // Transforma cada publicación con PublicacionResource
    return PublicacionResource::collection($publicaciones);
}
```

### C. Agregar Metadatos Adicionales (`additional()`)
Puedes adjuntar metadatos de nivel superior a la respuesta JSON (códigos de estado, mensajes de éxito, enlaces de paginación):

```php
public function show(Publicacion $publicacion)
{
    return (new PublicacionResource($publicacion))->additional([
        'status'  => 'success',
        'message' => 'Publicacion obtenida correctamente',
    ]);
}
```

Salida JSON producida:
```json
{
    "status": "success",
    "message": "Publicacion obtenida correctamente",
    "data": {
        "id": 1,
        "titulo": "Primer Articulo",
        "slug": "primer-articulo"
    }
}
```

---

## 5. Wrapping de Datos (`$wrap`)

Por defecto, tanto `JsonResource` como `ResourceCollection` envuelven la respuesta en una propiedad clave `"data"`, cumpliendo las especificaciones de la especificación JSON:API.

### Personalizar o Desactivar el Wrapping:
Si deseas que los campos se entreguen en la raíz sin la clave `"data"`:

```php
// En el recurso:
class UserSinWrapResource extends JsonResource
{
    public static ?string $wrap = null; // Desactiva {"data": ...}
}

// O en tiempo de ejecución:
PublicacionResource::$wrap = 'publicacion'; // Usa {"publicacion": ...}
```

---

## 6. Métodos Utilitarios Disponibles

| Método | Descripción |
|---|---|
| `$this->when(bool $condicion, mixed $valor, mixed $defecto = null)` | Incluye el campo únicamente si la condición se cumple. Soporta closures ejecutados de forma perezosa (*lazy*). |
| `$this->whenLoaded(string $relacion, mixed $valor = null)` | Incluye la relación solo si fue cargada previamente (vía `with()` o propiedad existente), evitando consultas N+1 accidentales. |
| `Resource::make($modelo)` | Constructor fluido estático alternativo a `new Resource($modelo)`. |
| `Resource::collection($lista)` | Crea una instancia de `ResourceCollection` que transforma cada elemento del array o `ModelCollection`. |
| `$resource->additional(array $meta)` | Añade datos de nivel superior al objeto JSON. |
| `$resource->toResponse(int $codigo = 200)` | Convierte explícitamente el recurso en una instancia `Cronos\Http\Response` con encabezados `Content-Type: application/json`. |

---

## 7. Qué SÍ Puede y Qué NO Puede Hacer

### ✅ Qué SÍ Puede Hacer:
- **Serialización limpia y segura:** Aislar completamente los nombres de columnas de base de datos de la API pública.
- **Resolución automática en el Router:** Retornar `new MiResource($item)` o `MiResource::collection($items)` directamente en closures de rutas o controladores sin requerir envolverlo manualmente en `json()`.
- **Carga condicional perezosa:** Usar closures en `$this->when(...)` para evitar cálculos pesados si la condición es falsa.
- **Generación rápida vía consola:** `php cronos make:resource NombreResource`.

### ❌ Qué NO Puede Hacer (Diferencias con Laravel):
- ❌ **NO intentes usar métodos de paginación de Laravel:** En Cronos, para paginar usa el paginador de Cronos (`Paginator`) y pasa los ítems a `collection()`.
- ❌ **NO uses `AnonymousResourceCollection`:** En Cronos la colección de recursos es manejada explícitamente por [`Cronos\Http\ResourceCollection`](file:///D:/laragon/www/cronos_framework/System/Http/ResourceCollection.php).
