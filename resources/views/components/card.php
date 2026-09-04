{{-- Props:
     shadow: bool (default true)
     padding: sm | md | lg (default 'md')
     Slots: default (cuerpo), header, footer (como variables $header / $footer).
     Atributos extra (id, class...) se combinan con $attributes. --}}
@props(['shadow' => true, 'padding' => 'md'])

<?php
$shadowClass  = $shadow ? 'shadow-md' : '';
$paddingClass = match ($padding) {
    'sm' => 'p-3',
    'lg' => 'p-8',
    default => 'p-6'
};
?>
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-100 overflow-hidden ' . $shadowClass]) }}>
    {{-- los slots llegan como HTML renderizado del padre: crudos; el padre escapo sus datos --}}
    @isset($header)
        <div class="px-6 py-4 border-b border-gray-100">{!! $header !!}</div>
    @endisset

    <div class="{{ $paddingClass }}">{!! $slot !!}</div>

    @isset($footer)
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">{!! $footer !!}</div>
    @endisset
</div>
