<?php

namespace App\Models;

use Cronos\Model\Model;

class Category extends Model
{
    protected string $table = 'categories';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected array $hidden = [];

    protected bool $timestamps = true;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function posts()
    {
        return $this->hasMany(Post::class, 'category_id');
    }
}
