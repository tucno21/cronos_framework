<?php

namespace App\Models;

use Cronos\Model\Model;

class Usuario extends Model
{
    protected string $table = 'usuarios';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'nombre',
        'correo',
        'contrasena',
        'rol',
        'avatar',
        'correo_verificado_en',
        'token_recordar',
    ];

    protected array $hidden = ['contrasena', 'token_recordar'];

    protected bool $timestamps = true;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    //1:1 con perfiles
    public function perfil()
    {
        return $this->hasOne(Perfil::class, 'usuario_id');
    }

    //1:n con publicaciones
    public function publicaciones()
    {
        return $this->hasMany(Publicacion::class, 'usuario_id');
    }

    //1:n con tokens de acceso
    public function tokens()
    {
        return $this->hasMany(TokenAcceso::class, 'usuario_id');
    }

    //n:m con roles (pivote rol_usuario)
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_usuario', 'usuario_id', 'rol_id');
    }

    //auto-referencial: quien invito a este usuario
    public function invitadoPor()
    {
        return $this->belongsTo(Usuario::class, 'invitado_por', 'id');
    }

    //auto-referencial: usuarios que este usuario invito
    public function invitados()
    {
        return $this->hasMany(Usuario::class, 'invitado_por');
    }

    //1:n con comentarios
    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'usuario_id');
    }
}
