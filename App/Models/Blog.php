<?php

namespace App\Models;

use Cronos\Model\Model;

class Blog extends Model
{
    //el nombre de la tabla
    protected string $table = 'blogs';

    protected string $primaryKey = 'id';

    //las columnas de su tabla
    protected array $fillable = ['title', 'slug', 'content', 'user_id'];

    protected array $hidden = [];

    //si la tabla tiene timestamps, si es false el framework no intentara
    //insertar o actualizar las columnas de timestamps  
    protected bool $timestamps = true;

    //nombre por defecto de las columnas de timestamps
    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
