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
        <x-navbar :guest="true" />

        <main class="flex-1 flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            <div class="w-full sm:max-w-md my-8 p-6 bg-card border border-border rounded-2xl shadow-xl">
                {{ $slot }}
            </div>
        </main>

        <x-footer />
    </body>
</html>
