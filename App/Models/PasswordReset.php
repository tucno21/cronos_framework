<?php

namespace App\Models;

use Cronos\Model\Model;

class PasswordReset extends Model
{
    protected string $table = 'password_resets';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'email',
        'token',
    ];

    protected array $hidden = ['token'];

    protected bool $timestamps = false;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';
}
