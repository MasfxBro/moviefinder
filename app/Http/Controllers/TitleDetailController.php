<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\MetadataProvider;
use App\Enums\MediaType;
use App\Models\Title;
use App\Services\Catalog\TitleImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TitleDetailController extends Controller
{
    public function __invoke(
        Request $request,
        string $slug,
        TitleImporter $importer,
        MetadataProvider $metadataProvider
    ): View|RedirectResponse {
        if (preg_match('/^tmdb-(\d+)$/', $slug, $matches)) {
            $tmdbId = (int) $matches[1];

            $existing = Title::where('tmdb_id', $tmdbId)
                ->where('adult', false)
                ->where('is_hidden', false)
                ->first();

            if ($existing) {
                return redirect()->route('titles.show', ['slug' => $existing->slug]);
            }

            try {
                $imported = $importer->import($tmdbId, MediaType::Movie);
            } catch (\Throwable) {
                try {
                    $imported = $importer->import($tmdbId, MediaType::Tv);
                } catch (\Throwable) {
                    abort(404);
                }
            }

            if ($imported->adult || $imported->is_hidden) {
                abort(404);
            }

            return redirect()->route('titles.show', ['slug' => $imported->slug]);
        }

        $title = Title::where('slug', $slug)->first();

        if (! $title || $title->adult || $title->is_hidden) {
            abort(404);
        }

        $title->load([
            'genres',
            'people' => fn ($query) => $query->orderBy('position'),
        ]);

        $title->updateQuietly(['last_viewed_at' => now()]);

        $watchProviders = [];
        if ($title->tmdb_id) {
            try {
                $mediaType = MediaType::from($title->media_type);

                $watchProviders = $metadataProvider->watchProviders((int) $title->tmdb_id, $mediaType, 'ID');
            } catch (\Throwable) {
                $watchProviders = [];
            }
        }

        $saved = $request->user()->hasVerifiedEmail()
            && $request->user()->watchlist()->where('title_id', $title->id)->exists();

        return view('titles.show', [
            'title' => $title,
            'watchlistSaved' => $saved,
            'watchProviders' => $watchProviders,
        ]);
    }
}
