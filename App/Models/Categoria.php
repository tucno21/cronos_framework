<?php

namespace App\Models;

use Cronos\Model\Model;

class Categoria extends Model
{
    protected string $table = 'categorias';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'categoria_padre_id',
    ];

    protected array $hidden = [];

    protected bool $timestamps = true;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function publicaciones()
    {
        return $this->hasMany(Publicacion::class, 'categoria_id');
    }

    //auto-referencial: categoria padre
    public function categoriaPadre()
    {
        return $this->belongsTo(Categoria::class, 'categoria_padre_id', 'id');
    }

    //auto-referencial: subcategorias hijas
    public function subcategorias()
    {
        return $this->hasMany(Categoria::class, 'categoria_padre_id');
    }
}
