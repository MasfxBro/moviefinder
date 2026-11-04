<div {{ $attributes->merge(['class' => 'flex flex-col gap-3']) }}>
    <x-skeleton class="aspect-[2/3] w-full rounded-xl" />
    <div class="flex flex-col gap-2">
        <x-skeleton class="h-4 w-3/4 rounded" />
        <div class="flex gap-2">
            <x-skeleton class="h-3 w-1/4 rounded" />
            <x-skeleton class="h-3 w-1/3 rounded" />
        </div>
    </div>
</div>
