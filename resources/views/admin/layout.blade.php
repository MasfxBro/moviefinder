<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Panel' }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg text-text min-h-screen font-sans antialiased flex flex-col">
    <header class="bg-surface border-b border-border sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <span class="admin-badge inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-danger text-white tracking-wide">ADMIN</span>
                    <a href="{{ route('admin.dashboard') }}" class="font-bold text-lg text-text hover:text-accent transition-colors">MovieFinder Ops</a>
                </div>
                <nav class="hidden sm:flex items-center gap-2 text-sm font-medium">
                    <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-card text-accent' : 'text-muted hover:text-text hover:bg-card/50' }}">Dashboard</a>
                    <a href="{{ route('admin.ai-monitoring.index') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.ai-monitoring.*') ? 'bg-card text-accent' : 'text-muted hover:text-text hover:bg-card/50' }}">AI Monitoring</a>
                    <a href="{{ route('admin.billing.index') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.billing.*') ? 'bg-card text-accent' : 'text-muted hover:text-text hover:bg-card/50' }}">Billing</a>
                    <a href="{{ route('admin.titles.index') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.titles.*') ? 'bg-card text-accent' : 'text-muted hover:text-text hover:bg-card/50' }}">Titles</a>
                    <a href="{{ route('admin.requests.index') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.requests.*') ? 'bg-card text-accent' : 'text-muted hover:text-text hover:bg-card/50' }}">Requests</a>
                    <a href="{{ route('admin.clip_links.index') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.clip_links.*') ? 'bg-card text-accent' : 'text-muted hover:text-text hover:bg-card/50' }}">Clip Links</a>
                    <a href="{{ route('admin.reports.index') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.reports.*') ? 'bg-card text-accent' : 'text-muted hover:text-text hover:bg-card/50' }}">Reports</a>
                    <a href="{{ route('admin.channels.index') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('admin.channels.*') ? 'bg-card text-accent' : 'text-muted hover:text-text hover:bg-card/50' }}">Channels</a>
                </nav>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-muted text-xs hidden md:inline">Logged in as admin</span>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-success/10 border border-success/30 text-success text-sm flex items-center justify-between" role="status">
                <span>{{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-success hover:opacity-75">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm flex items-center justify-between" role="alert">
                <span>{{ session('error') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-danger hover:opacity-75">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-danger/10 border border-danger/30 text-danger text-sm" role="alert">
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
