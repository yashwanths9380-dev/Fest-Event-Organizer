<?php
$success = flash_get('success');
$error = flash_get('error');
?>
<?php if ($success): ?>
    <div class="max-w-6xl mx-auto px-4 mt-4">
        <div class="rounded-xl bg-mint/10 border border-mint/30 text-mint px-4 py-3 text-sm font-medium">
            <?= e($success) ?>
        </div>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="max-w-6xl mx-auto px-4 mt-4">
        <div class="rounded-xl bg-rose/10 border border-rose/30 text-rose px-4 py-3 text-sm font-medium">
            <?= e($error) ?>
        </div>
    </div>
<?php endif; ?>
