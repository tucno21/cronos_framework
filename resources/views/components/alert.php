<?php
// Props disponibles (con defaults seguros):
$type  = $type  ?? 'info';   // 'success' | 'error' | 'warning' | 'info'
$title = $title ?? null;
$body  = $__slots['default'] ?? $slot ?? '';

$styles = [
    'success' => ['box' => 'bg-green-50 border-green-400',  'text' => 'text-green-800', 'icon' => 'bi-check-circle-fill text-green-500'],
    'error'   => ['box' => 'bg-red-50 border-red-400',      'text' => 'text-red-800',   'icon' => 'bi-x-circle-fill text-red-500'],
    'warning' => ['box' => 'bg-yellow-50 border-yellow-400', 'text' => 'text-yellow-800', 'icon' => 'bi-exclamation-triangle-fill text-yellow-500'],
    'info'    => ['box' => 'bg-blue-50 border-blue-400',    'text' => 'text-blue-800',  'icon' => 'bi-info-circle-fill text-blue-500'],
];
$s = $styles[$type] ?? $styles['info'];
?>
<div class="rounded-md border-l-4 p-4 mb-4 <?= $s['box'] ?>">
    <div class="flex items-start gap-3">
        <i class="bi <?= $s['icon'] ?> flex-shrink-0 mt-0.5"></i>
        <div class="flex-1 <?= $s['text'] ?>">
            <?php if ($title): ?>
                <p class="font-semibold mb-1"><?= htmlspecialchars($title, ENT_QUOTES) ?></p>
            <?php endif; ?>
            <div class="text-sm"><?= $body ?></div>
        </div>
    </div>
</div>