<?php

namespace App\Models;

use Cronos\Model\Model;

class Comentario extends Model
{
    protected string $table = 'comentarios';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'publicacion_id',
        'usuario_id',
        'contenido',
    ];

    protected array $hidden = [];

    protected bool $timestamps = true;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'publicacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
