<?php $pageTitle = 'Fests'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-6">Fests</h1>
    <div class="grid sm:grid-cols-2 gap-5">
        <?php foreach ($fests as $f): ?>
            <a href="/index.php?route=participant/fests/show&id=<?= (int)$f['id'] ?>" class="event-card block">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="font-display text-lg font-bold"><?= e($f['name']) ?></h2>
                    <?php if ($f['status'] === 'completed'): ?><span class="badge badge-slate">completed</span><?php endif; ?>
                </div>
                <p class="text-xs text-slateink mb-2">"<?= e($f['theme']) ?>" &middot; <?= format_date($f['start_date']) ?> &ndash; <?= format_date($f['end_date']) ?> &middot; <?= e($f['venue']) ?></p>
                <p class="text-sm text-slateink mb-3"><?= e($f['description']) ?></p>
                <span class="badge badge-teal"><?= (int)$f['event_count'] ?> event(s) open</span>
            </a>
        <?php endforeach; ?>
        <?php if (empty($fests)): ?><p class="empty-state">No published fests yet.</p><?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
