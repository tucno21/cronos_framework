<?php

namespace App\Models;

use Cronos\Model\Model;

class Blog extends Model
{
    protected string $table = 'posts';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
    ];

    protected array $hidden = [];

    protected bool $timestamps = true;

    protected string $created = 'created_at';

    protected string $updated = 'updated_at';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
