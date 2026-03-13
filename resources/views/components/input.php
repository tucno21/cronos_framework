<?php
// Props:
$name        = $name        ?? '';
$label       = $label       ?? ucfirst(str_replace(['_', '-'], ' ', $name));
$type        = $type        ?? 'text';
$placeholder = $placeholder ?? '';
$value       = $value       ?? '';
$required    = $required    ?? false;
$class       = $class       ?? '';
$variant     = $variant     ?? 'standard'; // 'standard' | 'floating'

// Intentar recuperar valor previo si existe la función old()
if (function_exists('old') && old($name) !== null) {
    $value = old($name);
}

// Verificar error para este campo
$errorMsg = null;
if (function_exists('session') && method_exists(session(), 'error')) {
    $errorMsg = session()->error($name) ?: null;
}

// Variante floating label (estilo home.css)
if ($variant === 'floating') {
    $inputClass = 'input-especial ' . ($errorMsg ? 'error' : '');
?>
    <div class="mb-6 relative block">
        <label for="<?= htmlspecialchars($name, ENT_QUOTES) ?>" class="input-placeholder">
            <?= htmlspecialchars($label, ENT_QUOTES) ?>
        </label>
        <input
            type="<?= htmlspecialchars($type, ENT_QUOTES) ?>"
            name="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
            id="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
            value="<?= htmlspecialchars($value, ENT_QUOTES) ?>"
            placeholder=" "
            class="<?= $inputClass ?> <?= $class ?>"
            <?= $required ? 'required' : '' ?>>
        <?php if ($errorMsg): ?>
            <p class="mt-2 text-red-500 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
                </svg>
                <?= htmlspecialchars($errorMsg, ENT_QUOTES) ?>
            </p>
        <?php endif; ?>
    </div>
<?php
} else {
    // Variante estándar (estilo dashboard)
    $inputClass = 'w-full px-3 py-2 border rounded-md shadow-sm text-sm transition-colors ' .
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
        <input
            type="<?= htmlspecialchars($type, ENT_QUOTES) ?>"
            name="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
            id="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
            value="<?= htmlspecialchars($value, ENT_QUOTES) ?>"
            placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES) ?>"
            class="<?= $inputClass ?>"
            <?= $required ? 'required' : '' ?>>
        <?php if ($errorMsg): ?>
            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($errorMsg, ENT_QUOTES) ?></p>
        <?php endif; ?>
    </div>
<?php } ?>