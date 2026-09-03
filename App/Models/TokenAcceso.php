<?php

namespace App\Models;

use Cronos\Model\Model;

class TokenAcceso extends Model
{
    protected string $table = 'tokens_acceso';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'usuario_id',
        'nombre',
        'token',
        'habilidades',
        'ultimo_uso_en',
        'expira_en',
    ];

    protected array $hidden = ['token'];

    protected bool $timestamps = false;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
