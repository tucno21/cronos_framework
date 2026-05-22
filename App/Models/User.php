<?php

namespace App\Models;

use Cronos\Model\Model;

class User extends Model
{
    protected string $table = 'users';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'email_verified_at',
        'remember_token',
    ];

    protected array $hidden = ['password', 'remember_token'];

    protected bool $timestamps = true;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function tokens()
    {
        return $this->hasMany(PersonalAccessToken::class, 'user_id');
    }
}
