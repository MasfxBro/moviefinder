<?php

declare(strict_types=1);

use App\Models\Genre;
use App\Models\Title;
use App\Models\TitlePerson;
use Database\Seeders\GenreSeeder;
use Illuminate\Support\Facades\DB;

test('factories create valid titles', function () {
    $title = Title::factory()->create();

    expect($title)->toBeInstanceOf(Title::class)
        ->and($title->adult)->toBeFalse()
        ->and($title->slug)->not->toBeEmpty();
});

test('slug generation is unique and stable', function () {
    $title1 = Title::factory()->create(['title' => 'Inception', 'release_year' => 2010]);
    $title2 = Title::factory()->create(['title' => 'Inception', 'release_year' => 2010]);

    expect($title1->slug)->toBe('inception-2010')
        ->and($title2->slug)->toBe('inception-2010-1')
        ->and($title1->slug)->not->toBe($title2->slug);
});

test('adult titles cannot be created', function () {
    Title::factory()->create(['adult' => true]);
})->throws(RuntimeException::class, 'Adult titles cannot be created');

test('adult flag cannot be set to true', function () {
    $title = Title::factory()->create(['adult' => false]);

    $title->adult = true;
    $title->save();
})->throws(RuntimeException::class, 'Adult flag cannot be set to true');

test('fulltext index exists on title columns', function () {
    $indexes = DB::select("SHOW INDEX FROM titles WHERE Index_type = 'FULLTEXT'");

    $columns = collect($indexes)->pluck('Column_name')->unique()->sort()->values();

    expect($columns->toArray())->toBe(['original_title', 'title']);
});

test('title has genres relation', function () {
    $this->seed(GenreSeeder::class);

    $title = Title::factory()->create();
    $genre = Genre::find(28); // Action

    $title->genres()->attach($genre->id);

    expect($title->genres)->toHaveCount(1)
        ->and($title->genres->first()->name)->toBe('Action');
});

test('title has people relation', function () {
    $title = Title::factory()->create();
    $person = TitlePerson::factory()->for($title)->director()->create();

    expect($title->people)->toHaveCount(1)
        ->and($title->people->first()->role)->toBe('director');
});
