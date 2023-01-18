<?php

namespace App\Models;

use Cronos\Model\Model;

class ModelName extends Model
{
    //el nombre de la tabla
    protected string $table = 'table';

    protected string $primaryKey = 'id';

    //las columnas de su tabla
    protected array $fillable = ['name', 'email',];

    protected array $hidden = [];

    //si la tabla tiene timestamps, si es false el framework no intentara
    //insertar o actualizar las columnas de timestamps  
    protected bool $timestamps = true;

    //nombre por defecto de las columnas de timestamps
    protected string $created = 'created_at';

    protected string $updated = 'updated_at';
}
