<?php $pageTitle = $fest['name']; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <a href="/index.php?route=organizer/fests" class="text-cyan text-sm font-semibold hover:underline">&larr; Back to Fests</a>
    <h1 class="font-display text-2xl font-bold mt-2 mb-1"><?= e($fest['name']) ?></h1>
    <p class="text-slateink text-sm mb-6"><?= e($fest['description']) ?></p>

    <?php if (empty($events)): ?>
        <div class="card"><p class="empty-state">No approved events yet.</p></div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 gap-5">
            <?php foreach ($events as $ev): ?>
                <div class="event-card">
                    <div class="flex items-center justify-between mb-1">
                        <h2 class="font-display text-lg font-bold"><?= e($ev['title']) ?></h2>
                        <span class="badge badge-cyan"><?= e(ucfirst($ev['category'])) ?></span>
                    </div>
                    <p class="text-xs text-slateink mb-2"><?= e($ev['club_name']) ?> &middot; <?= e($ev['venue']) ?> &middot; <?= format_datetime($ev['start_time']) ?></p>
                    <p class="text-xs text-slateink">Seats: <?= (int)$ev['seats_taken'] ?>/<?= (int)$ev['capacity'] ?> &middot; Team <?= (int)$ev['team_size_min'] ?>&ndash;<?= (int)$ev['team_size_max'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
