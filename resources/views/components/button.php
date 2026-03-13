<?php
// Props:
$type    = $type    ?? 'button';
$variant = $variant ?? 'primary';
$size    = $size    ?? 'md';
$href    = $href    ?? null;
$disabled = $disabled ?? false;
$class   = $class   ?? '';
$label   = $__slots['default'] ?? $slot ?? '';

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
$classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . $class;
?>
<?php if ($href): ?>
    <a href="<?= htmlspecialchars($href, ENT_QUOTES) ?>" class="<?= $classes ?>"><?= $label ?></a>
<?php else: ?>
    <button type="<?= htmlspecialchars($type, ENT_QUOTES) ?>" class="<?= $classes ?>" <?= $disabled ? 'disabled' : '' ?>><?= $label ?></button>
<?php endif; ?>