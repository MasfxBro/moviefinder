<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaType;
use App\Enums\RequestStatus;
use App\Notifications\RequestResolved;
use Database\Factories\TitleRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property MediaType $media_type
 * @property RequestStatus $status
 */
class TitleRequest extends Model
{
    /** @use HasFactory<TitleRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'media_type',
        'title_text',
        'release_year',
        'note',
        'status',
        'admin_note',
        'resolved_title_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'media_type' => MediaType::class,
            'status' => RequestStatus::class,
            'release_year' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedTitle(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'resolved_title_id');
    }

    public function resolve(RequestStatus $status, ?string $adminNote = null, ?Title $resolvedTitle = null): void
    {
        $this->update([
            'status' => $status,
            'admin_note' => $adminNote,
            'resolved_title_id' => $resolvedTitle?->id,
        ]);

        $this->user->notify(new RequestResolved($this));
    }
}
