<?php

namespace App\Models;

use Cronos\Model\Model;

class Contac extends Model
{
    protected string $table = 'contac';

    protected string $primaryKey = 'id';

    protected array $fillable = ['nombre', 'telefono'];

    protected array $hidden = [];

    protected bool $timestamps = false;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';
}
