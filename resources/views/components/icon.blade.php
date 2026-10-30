@props(['name' => 'droplet'])
@php
    $iconName = basename((string) $name);
    $iconPath = public_path('icons/pixel/'.$iconName.'.svg');
@endphp
@if (file_exists($iconPath))
    <span {{ $attributes->merge(['class' => 'px-icon']) }} aria-hidden="true">{!! file_get_contents($iconPath) !!}</span>
@endif
