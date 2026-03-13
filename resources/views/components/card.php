<?php
// Props:
$class   = $class   ?? '';
$shadow  = $shadow  ?? true;
$padding = $padding ?? 'md';

// Slots:
$headerSlot  = $__slots['header']  ?? null;
$footerSlot  = $__slots['footer']  ?? null;
$defaultSlot = $__slots['default'] ?? $slot ?? '';

$shadowClass  = $shadow ? 'shadow-md' : '';
$paddingClass = match ($padding) {
    'sm' => 'p-3',
    'lg' => 'p-8',
    default => 'p-6'
};
?>
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden <?= $shadowClass ?> <?= $class ?>">
    <?php if ($headerSlot !== null): ?>
        <div class="px-6 py-4 border-b border-gray-100">
            <?= $headerSlot ?>
        </div>
    <?php endif; ?>

    <div class="<?= $paddingClass ?>">
        <?= $defaultSlot ?>
    </div>

    <?php if ($footerSlot !== null): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            <?= $footerSlot ?>
        </div>
    <?php endif; ?>
</div>