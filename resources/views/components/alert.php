{{-- Props:
     type: success | error | warning | info (default 'info')
     title: titulo opcional
     Atributos extra (id, class...) se combinan con $attributes. --}}
@props(['type' => 'info', 'title' => null])

<?php
$styles = [
    'success' => ['box' => 'bg-green-50 border-green-400',   'text' => 'text-green-800',  'icon' => 'bi-check-circle-fill text-green-500'],
    'error'   => ['box' => 'bg-red-50 border-red-400',       'text' => 'text-red-800',    'icon' => 'bi-x-circle-fill text-red-500'],
    'warning' => ['box' => 'bg-yellow-50 border-yellow-400', 'text' => 'text-yellow-800', 'icon' => 'bi-exclamation-triangle-fill text-yellow-500'],
    'info'    => ['box' => 'bg-blue-50 border-blue-400',     'text' => 'text-blue-800',   'icon' => 'bi-info-circle-fill text-blue-500'],
];
$s = $styles[$type] ?? $styles['info'];
?>
<div {{ $attributes->merge(['class' => 'rounded-md border-l-4 p-4 mb-4 ' . $s['box']]) }}>
    <div class="flex items-start gap-3">
        <i class="bi {{ $s['icon'] }} flex-shrink-0 mt-0.5"></i>
        <div class="flex-1 {{ $s['text'] }}">
            @if ($title)
                <p class="font-semibold mb-1">{{ $title }}</p>
            @endif
            {{-- el slot llega como HTML renderizado del padre: crudo; el padre escapo sus datos --}}
            <div class="text-sm">{!! $slot !!}</div>
        </div>
    </div>
</div>
