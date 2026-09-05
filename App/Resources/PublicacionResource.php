<?php

namespace App\Resources;

use App\Models\Usuario;
use Cronos\Http\JsonResource;

class PublicacionResource extends JsonResource
{
    public static ?string $wrap = null;

    /**
     * Transforma el recurso a un array estructurado para respuestas JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $categoria = is_object($this->resource) && method_exists($this->resource, 'categoria')
            ? $this->resource->categoria()->get()
            : ($this->categoria ?? null);

        $etiquetas = is_object($this->resource) && method_exists($this->resource, 'etiquetas')
            ? $this->resource->etiquetas()->get()
            : ($this->etiquetas ?? []);

        $nombreAutor = $this->usuario->nombre ?? null;
        if (!$nombreAutor && !empty($this->usuario_id)) {
            $autor = Usuario::find((int) $this->usuario_id);
            $nombreAutor = $autor->nombre ?? null;
        }

        return [
            'id' => (int) $this->id,
            'usuario_id' => (int) $this->usuario_id,
            'categoria_id' => $this->categoria_id ? (int) $this->categoria_id : null,
            'titulo' => $this->titulo,
            'slug' => $this->slug,
            'resumen' => $this->resumen,
            'contenido' => $this->contenido,
            'estado' => $this->estado ?? 'publicado',
            'vistas' => (int) ($this->vistas ?? 0),
            'publicado_en' => $this->publicado_en,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'nombre_autor' => $nombreAutor,
            'usuario' => $this->usuario ? (is_object($this->usuario) ? $this->usuario->toArray() : $this->usuario) : null,
            'categoria' => $categoria ? (is_object($categoria) ? $categoria->toArray() : $categoria) : null,
            'etiquetas' => $etiquetas ? (is_object($etiquetas) && method_exists($etiquetas, 'toArray') ? $etiquetas->toArray() : (array) $etiquetas) : [],
            'comentarios_count' => $this->comentarios_count ?? null,
        ];
    }
}
