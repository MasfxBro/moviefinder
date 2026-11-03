@props([
    'code' => '404',
    'title' => '',
    'message' => '',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ config('app.name', 'MovieFinder') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-bg text-text antialiased min-h-screen flex flex-col selection:bg-accent selection:text-text">
    <header class="w-full border-b border-border bg-surface px-4 py-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="/" class="text-xl font-bold tracking-tight text-text hover:opacity-90">
                {{ config('app.name', 'MovieFinder') }}
            </a>
        </div>
    </header>

    <main class="flex-1 flex flex-col items-center justify-center p-6 text-center">
        <div class="max-w-md w-full p-8 bg-card border border-border rounded-2xl shadow-xl space-y-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-accent-soft text-accent font-mono text-2xl font-bold">
                {{ $code }}
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight text-text">
                    {{ $title }}
                </h1>
                <p class="text-muted text-sm leading-relaxed">
                    {{ $message }}
                </p>
            </div>

            @if ($slot->isNotEmpty())
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </main>

    <footer class="w-full border-t border-border bg-surface px-4 py-4 text-center text-xs text-faint">
        &copy; {{ date('Y') }} {{ config('app.name', 'MovieFinder') }}. All rights reserved.
    </footer>
</body>
</html>
