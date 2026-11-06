<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'MovieFinder') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans bg-bg text-text antialiased min-h-screen flex flex-col selection:bg-accent selection:text-text">
        <x-offline-banner />
        @if (config('ai.driver') === 'fake')
            <x-ai-demo-banner class="mx-4 mt-3 sm:mx-6 lg:mx-8" />
        @endif
        <x-navbar />

        @isset($header)
            <header class="bg-surface border-b border-border">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
            {{ $slot }}
        </main>

        <x-footer />
    </body>
</html>
