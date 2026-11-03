@props([
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'animate-pulse bg-elevated/60 rounded-xl ' . $class]) }}></div>
