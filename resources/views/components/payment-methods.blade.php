@props(['methods' => []])

<div class="flex flex-wrap gap-2">
    @foreach($methods as $method)
        <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-faint border border-border rounded bg-surface">
            {{ str_replace('_', ' ', $method) }}
        </span>
    @endforeach
</div>
