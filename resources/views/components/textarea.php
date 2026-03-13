<?php
$name        = $name        ?? '';
$label       = $label       ?? ucfirst(str_replace(['_', '-'], ' ', $name));
$placeholder = $placeholder ?? '';
$rows        = $rows        ?? 4;
$required    = $required    ?? false;
$class       = $class       ?? '';
$value       = $__slots['default'] ?? $slot ?? '';

if (function_exists('old') && old($name) !== null) {
    $value = old($name);
}

$errorMsg = null;
if (function_exists('session') && method_exists(session(), 'error')) {
    $errorMsg = session()->error($name) ?: null;
}

$inputClass = 'w-full px-3 py-2 border rounded-md shadow-sm resize-y transition-colors ' .
    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent ' .
    ($errorMsg ? 'border-red-400 bg-red-50 ' : 'border-gray-300 ') . $class;
?>
<div class="mb-4">
    <?php if ($label): ?>
        <label for="<?= htmlspecialchars($name, ENT_QUOTES) ?>" class="block text-sm font-medium text-gray-700 mb-1">
            <?= htmlspecialchars($label, ENT_QUOTES) ?>
            <?php if ($required): ?><span class="text-red-500 ml-0.5">*</span><?php endif; ?>
        </label>
    <?php endif; ?>
    <textarea
        name="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
        id="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
        rows="<?= (int)$rows ?>"
        placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES) ?>"
        class="<?= $inputClass ?>"
        <?= $required ? 'required' : '' ?>><?= htmlspecialchars($value, ENT_QUOTES) ?></textarea>
    <?php if ($errorMsg): ?>
        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($errorMsg, ENT_QUOTES) ?></p>
    <?php endif; ?>
</div>