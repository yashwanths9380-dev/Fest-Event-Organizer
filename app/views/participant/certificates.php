<?php $pageTitle = 'My Certificates'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-5xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-6">My Certificates</h1>

    <?php if (empty($certificates)): ?>
        <div class="card"><p class="empty-state">No results have been recorded for your registrations yet.</p></div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 gap-5">
            <?php foreach ($certificates as $c): ?>
                <a href="/index.php?route=participant/certificates/show&id=<?= (int)$c['id'] ?>" class="event-card block">
                    <div class="flex items-center justify-between mb-1">
                        <h2 class="font-display text-lg font-bold"><?= e($c['event_title']) ?></h2>
                        <?php $posBadge = ['winner' => 'badge-gold', 'runner_up' => 'badge-teal', 'second_runner_up' => 'badge-cyan', 'participant' => 'badge-slate'][$c['position']]; ?>
                        <span class="badge <?= $posBadge ?>"><?= e(str_replace('_', ' ', $c['position'])) ?></span>
                    </div>
                    <p class="text-xs text-slateink"><?= e($c['fest_name']) ?> &middot; <?= e($c['team_name'] ?: 'Solo Entry') ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
