<?php

namespace App\Models;

use Cronos\Model\Model;

class Tag extends Model
{
    protected string $table = 'tags';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name',
        'slug',
    ];

    protected array $hidden = [];

    protected bool $timestamps = false;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag', 'tag_id', 'post_id');
    }
}
