<?php $pageTitle = 'Browse Fests'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-6">Published Fests</h1>
    <div class="grid sm:grid-cols-2 gap-5">
        <?php foreach ($fests as $f): ?>
            <a href="/index.php?route=organizer/fests/show&id=<?= (int)$f['id'] ?>" class="event-card block">
                <h2 class="font-display text-lg font-bold"><?= e($f['name']) ?></h2>
                <p class="text-xs text-slateink mb-2">"<?= e($f['theme']) ?>" &middot; <?= format_date($f['start_date']) ?> &ndash; <?= format_date($f['end_date']) ?></p>
                <p class="text-sm text-slateink mb-3"><?= e($f['description']) ?></p>
                <span class="badge badge-teal"><?= (int)$f['event_count'] ?> approved event(s)</span>
            </a>
        <?php endforeach; ?>
        <?php if (empty($fests)): ?><p class="empty-state">No published fests yet.</p><?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
