<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'media_scope',
    ];

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'title_genre');
    }
}
