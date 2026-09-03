<?php

namespace App\Models;

use Cronos\Model\Model;

class RestablecimientoContrasena extends Model
{
    protected string $table = 'restablecimientos_contrasena';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'correo',
        'token',
    ];

    protected array $hidden = ['token'];

    protected bool $timestamps = false;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';
}
