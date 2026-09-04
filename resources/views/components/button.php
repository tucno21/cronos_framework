{{-- Props:
     type: tipo del boton HTML (default 'button')
     variant: primary | secondary | danger | ghost
     size: sm | md | lg
     href: si viene, renderiza <a> en vez de <button>
     disabled: deshabilita el boton
     Atributos extra (id, target, rel, class...) se combinan con $attributes. --}}
@props(['type' => 'button', 'variant' => 'primary', 'size' => 'md', 'href' => null, 'disabled' => false])

<?php
$variants = [
    'primary'   => 'bg-blue-600 hover:bg-blue-700 text-white border-transparent',
    'secondary' => 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300',
    'danger'    => 'bg-red-600 hover:bg-red-700 text-white border-transparent',
    'ghost'     => 'bg-transparent hover:bg-gray-100 text-gray-600 border-transparent',
];
$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$base = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed';
$classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
?>
@if ($href)
    <a href="{{ $href }}" {!! $attributes->merge(['class' => $classes]) !!}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {!! $attributes->merge(['class' => $classes]) !!} @disabled($disabled)>{{ $slot }}</button>
@endif
