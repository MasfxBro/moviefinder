<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitlePerson extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'title_id',
        'name',
        'role',
        'position',
        'tmdb_person_id',
    ];

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
