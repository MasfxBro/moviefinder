@props(['step', 'title', 'description'])

<div class="flex flex-col gap-6 p-8 bg-surface rounded-2xl border border-border group hover:border-accent/50 transition-colors">
    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-accent-soft text-accent font-bold text-lg">
        {{ $step }}
    </div>
    <div class="space-y-2">
        <h3 class="text-xl font-bold text-text">{{ $title }}</h3>
        <p class="text-muted leading-relaxed">{{ $description }}</p>
    </div>
</div>
