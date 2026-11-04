<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            // Movie only
            ['id' => 28, 'name' => 'Action', 'media_scope' => 'movie'],
            ['id' => 12, 'name' => 'Adventure', 'media_scope' => 'movie'],
            ['id' => 14, 'name' => 'Fantasy', 'media_scope' => 'movie'],
            ['id' => 36, 'name' => 'History', 'media_scope' => 'movie'],
            ['id' => 27, 'name' => 'Horror', 'media_scope' => 'movie'],
            ['id' => 10402, 'name' => 'Music', 'media_scope' => 'movie'],
            ['id' => 10749, 'name' => 'Romance', 'media_scope' => 'movie'],
            ['id' => 878, 'name' => 'Science Fiction', 'media_scope' => 'movie'],
            ['id' => 10770, 'name' => 'TV Movie', 'media_scope' => 'movie'],
            ['id' => 53, 'name' => 'Thriller', 'media_scope' => 'movie'],
            ['id' => 10752, 'name' => 'War', 'media_scope' => 'movie'],

            // TV only
            ['id' => 10759, 'name' => 'Action & Adventure', 'media_scope' => 'tv'],
            ['id' => 10762, 'name' => 'Kids', 'media_scope' => 'tv'],
            ['id' => 10763, 'name' => 'News', 'media_scope' => 'tv'],
            ['id' => 10764, 'name' => 'Reality', 'media_scope' => 'tv'],
            ['id' => 10765, 'name' => 'Sci-Fi & Fantasy', 'media_scope' => 'tv'],
            ['id' => 10766, 'name' => 'Soap', 'media_scope' => 'tv'],
            ['id' => 10767, 'name' => 'Talk', 'media_scope' => 'tv'],
            ['id' => 10768, 'name' => 'War & Politics', 'media_scope' => 'tv'],

            // Both
            ['id' => 16, 'name' => 'Animation', 'media_scope' => 'both'],
            ['id' => 35, 'name' => 'Comedy', 'media_scope' => 'both'],
            ['id' => 80, 'name' => 'Crime', 'media_scope' => 'both'],
            ['id' => 99, 'name' => 'Documentary', 'media_scope' => 'both'],
            ['id' => 18, 'name' => 'Drama', 'media_scope' => 'both'],
            ['id' => 10751, 'name' => 'Family', 'media_scope' => 'both'],
            ['id' => 9648, 'name' => 'Mystery', 'media_scope' => 'both'],
            ['id' => 37, 'name' => 'Western', 'media_scope' => 'both'],
        ];

        DB::table('genres')->insert($genres);
    }
}
