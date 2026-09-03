<?php

namespace App\Models;

use Cronos\Model\Model;

class Rol extends Model
{
    protected string $table = 'roles';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'nombre',
        'slug',
        'descripcion',
    ];

    protected array $hidden = [];

    protected bool $timestamps = false;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    //n:m con usuarios (pivote rol_usuario)
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'rol_usuario', 'rol_id', 'usuario_id');
    }
}
