<?php

namespace App\Models;

use Cronos\Model\Model;

class Publicacion extends Model
{
    protected string $table = 'publicaciones';

    protected string $primaryKey = 'id';

    //NOTA: en el ORM de Cronos $fillable son los campos OBLIGATORIOS en create();
    //los demas campos (categoria_id, resumen, estado, vistas...) toman defaults de la BD
    protected array $fillable = [
        'usuario_id',
        'titulo',
        'slug',
        'contenido',
    ];

    protected array $hidden = ['eliminado_en'];

    protected bool $timestamps = true;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    //n:m con etiquetas (pivote publicacion_etiqueta)
    public function etiquetas()
    {
        return $this->belongsToMany(Etiqueta::class, 'publicacion_etiqueta', 'publicacion_id', 'etiqueta_id');
    }

    //1:n con comentarios
    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'publicacion_id');
    }
}
