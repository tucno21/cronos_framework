<?php

namespace App\Models;

use Cronos\Model\Model;

class Etiqueta extends Model
{
    protected string $table = 'etiquetas';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'nombre',
        'slug',
    ];

    protected array $hidden = [];

    protected bool $timestamps = false;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    //n:m con publicaciones (pivote publicacion_etiqueta)
    public function publicaciones()
    {
        return $this->belongsToMany(Publicacion::class, 'publicacion_etiqueta', 'etiqueta_id', 'publicacion_id');
    }
}
