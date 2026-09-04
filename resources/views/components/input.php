{{-- Props:
     name: nombre del campo
     label: etiqueta (default: derivada del name)
     type: tipo del input (default 'text')
     placeholder, value
     required: bool (atributo required + asterisco en el label)
     variant: standard | floating (default 'standard')
     old() tiene prioridad sobre value; muestra el error de sesion del campo.
     Atributos extra (id, data-*, class...) se combinan con $attributes sobre el input. --}}
@props(['name' => '', 'label' => null, 'type' => 'text', 'placeholder' => '', 'value' => null, 'required' => false, 'variant' => 'standard'])

<?php
$label = $label ?? ucfirst(str_replace(['_', '-'], ' ', $name));

if (function_exists('old') && old($name) !== null) {
    $value = old($name);
}

$errorMsg = null;
if (function_exists('session') && method_exists(session(), 'error')) {
    $errorMsg = session()->error($name) ?: null;
}

$errorClass = $errorMsg ? 'border-red-400 bg-red-50' : 'border-gray-300';
?>
@if ($variant === 'floating')
    <div class="mb-6 relative block">
        <label for="{{ $name }}" class="input-placeholder">{{ $label }}</label>
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ $value }}"
            placeholder=" "
            @required($required)
            {!! $attributes->merge(['class' => 'input-especial ' . ($errorMsg ? 'error' : '')]) !!}>

        @if ($errorMsg)
            <p class="mt-2 text-red-500 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
                </svg>
                {{ $errorMsg }}
            </p>
        @endif
    </div>
@else
    <div class="mb-4">
        @if ($label)
            <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
                {{ $label }}
                @if ($required)<span class="text-red-500 ml-0.5">*</span>@endif
            </label>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            @required($required)
            {!! $attributes->merge(['class' => 'w-full px-3 py-2 border rounded-md shadow-sm text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ' . $errorClass]) !!}>

        @if ($errorMsg)
            <p class="mt-1 text-xs text-red-600">{{ $errorMsg }}</p>
        @endif
    </div>
@endif
