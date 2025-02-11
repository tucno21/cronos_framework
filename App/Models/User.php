<?php

namespace App\Models;

use Cronos\Model\Model;

class User extends Model
{
    protected string $table = 'users';

    protected string $primaryKey = 'id';

    protected array $fillable = ['name', 'email', 'password'];

    protected array $hidden = ['password'];

    protected bool $timestamps = true;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'user_id');
    }
}
