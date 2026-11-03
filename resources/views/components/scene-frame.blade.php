@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'scene-frame ' . $class]) }}>
    <div class="scene-frame-bracket scene-frame-tl"></div>
    <div class="scene-frame-bracket scene-frame-tr"></div>
    <div class="scene-frame-bracket scene-frame-bl"></div>
    <div class="scene-frame-bracket scene-frame-br"></div>
    {{ $slot }}
</div>
