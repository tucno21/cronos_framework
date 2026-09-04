{{-- Props:
     name: nombre del campo
     label: etiqueta (default: derivada del name)
     placeholder
     rows (default 4)
     required: bool (atributo required + asterisco en el label)
     El contenido del slot se usa como valor inicial; old() tiene prioridad.
     Muestra el error de sesion del campo.
     Atributos extra (id, data-*, class...) se combinan con $attributes sobre el textarea. --}}
@props(['name' => '', 'label' => null, 'placeholder' => '', 'rows' => 4, 'required' => false])

<?php
$label = $label ?? ucfirst(str_replace(['_', '-'], ' ', $name));

// el slot llega como HTML renderizado del padre (si el padre lo escapo, ya viene escapado);
// old() directo es input crudo del usuario: se escapa aqui. se imprime crudo abajo.
$value = $slot;
if (function_exists('old') && old($name) !== null) {
    $value = function_exists('e') ? e(old($name)) : htmlspecialchars((string) old($name), ENT_QUOTES);
}

$errorMsg = null;
if (function_exists('session') && method_exists(session(), 'error')) {
    $errorMsg = session()->error($name) ?: null;
}

$inputClass = 'w-full px-3 py-2 border rounded-md shadow-sm resize-y transition-colors ' .
    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ' .
    ($errorMsg ? 'border-red-400 bg-red-50 ' : 'border-gray-300 ');
?>
<div class="mb-4">
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
            @if ($required)<span class="text-red-500 ml-0.5">*</span>@endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @required($required)
        {!! $attributes->merge(['class' => $inputClass]) !!}>{!! $value !!}</textarea>

    @if ($errorMsg)
        <p class="mt-1 text-xs text-red-600">{{ $errorMsg }}</p>
    @endif
</div>
