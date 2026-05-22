<?php

namespace App\Models;

use Cronos\Model\Model;

class PersonalAccessToken extends Model
{
    protected string $table = 'personal_access_tokens';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected array $hidden = ['token'];

    protected bool $timestamps = false;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
