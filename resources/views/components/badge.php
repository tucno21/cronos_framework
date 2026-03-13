<?php
$color = $color ?? 'blue';
$label = $__slots['default'] ?? $slot ?? '';

$colors = [
    'blue'   => 'bg-blue-100 text-blue-700',
    'green'  => 'bg-green-100 text-green-700',
    'red'    => 'bg-red-100 text-red-700',
    'yellow' => 'bg-yellow-100 text-yellow-700',
    'gray'   => 'bg-gray-100 text-gray-600',
];
$colorClass = $colors[$color] ?? $colors['blue'];
?>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $colorClass ?>">
    <?= $label ?>
</span>