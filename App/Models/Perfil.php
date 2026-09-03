<?php

namespace App\Models;

use Cronos\Model\Model;

class Perfil extends Model
{
    protected string $table = 'perfiles';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'usuario_id',
        'biografia',
        'telefono',
        'fecha_nacimiento',
        'sitio_web',
    ];

    protected array $hidden = [];

    protected bool $timestamps = true;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
