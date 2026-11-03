<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('blade views and lang files contain no contractions', function () {
    $directories = [
        resource_path('views'),
        base_path('lang'),
    ];

    $contractionRegexes = [
        '/\b\w+n\'t\b/i',  // e.g., don't, can't, won't
        '/\b\w+\'ve\b/i',  // e.g., I've, you've
        '/\b\w+\'re\b/i',  // e.g., you're, we're
        '/\b\w+\'ll\b/i',  // e.g., I'll, call'll
        '/\b\w+\'m\b/i',   // e.g., I'm
    ];

    $violations = [];

    foreach ($directories as $directory) {
        if (! File::isDirectory($directory)) {
            continue;
        }

        $files = File::allFiles($directory);

        foreach ($files as $file) {
            $path = $file->getRealPath();
            $content = File::get($path);

            foreach ($contractionRegexes as $regex) {
                if (preg_match($regex, $content, $matches)) {
                    $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
                    $violations[] = "{$relative}: found contraction '{$matches[0]}'";
                }
            }
        }
    }

    expect($violations)->toBeEmpty(
        'Contractions found in UI text files:'.PHP_EOL.implode(PHP_EOL, $violations)
    );
});
