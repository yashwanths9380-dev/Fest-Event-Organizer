<?php $pageTitle = 'Event Approvals'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h1 class="font-display text-2xl font-bold">Event Approvals</h1>
        <div class="flex gap-2 text-sm">
            <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?>
                <a href="/index.php?route=admin/events&status=<?= $key ?>"
                   class="px-3 py-1.5 rounded-lg font-medium <?= $filter === $key ? 'bg-teal-dark text-white' : 'bg-white border border-line text-slateink' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($events)): ?>
        <p class="empty-state">No events in this filter.</p>
    <?php endif; ?>

    <div class="space-y-4">
        <?php foreach ($events as $ev): ?>
            <div class="card">
                <div class="card-head">
                    <div>
                        <h2><?= e($ev['title']) ?></h2>
                        <p class="text-xs text-slateink mt-1"><?= e($ev['club_name']) ?> &middot; <?= e($ev['fest_name']) ?> &middot; <?= e(ucfirst($ev['category'])) ?></p>
                    </div>
                    <?php $badgeClass = ['pending' => 'badge-gold', 'approved' => 'badge-mint', 'rejected' => 'badge-rose'][$ev['status']]; ?>
                    <span class="badge <?= $badgeClass ?>"><?= e($ev['status']) ?></span>
                </div>
                <p class="text-sm text-slateink mb-3"><?= e($ev['description']) ?></p>
                <div class="grid sm:grid-cols-4 gap-3 text-xs text-slateink mb-4">
                    <div><span class="font-semibold text-ink">Venue:</span> <?= e($ev['venue']) ?></div>
                    <div><span class="font-semibold text-ink">When:</span> <?= format_datetime($ev['start_time']) ?></div>
                    <div><span class="font-semibold text-ink">Capacity:</span> <?= (int)$ev['seats_taken'] ?>/<?= (int)$ev['capacity'] ?></div>
                    <div><span class="font-semibold text-ink">Team size:</span> <?= (int)$ev['team_size_min'] ?>&ndash;<?= (int)$ev['team_size_max'] ?></div>
                </div>
                <?php if ($ev['status'] === 'rejected' && $ev['rejection_reason']): ?>
                    <p class="text-xs text-rose mb-3"><span class="font-semibold">Rejection reason:</span> <?= e($ev['rejection_reason']) ?></p>
                <?php endif; ?>
                <?php if ($ev['status'] === 'pending'): ?>
                    <div class="flex gap-2 flex-wrap items-start">
                        <form method="POST" action="/index.php?route=admin/events/approve">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                            <button type="submit" class="btn-primary btn-sm">Approve</button>
                        </form>
                        <form method="POST" action="/index.php?route=admin/events/reject" class="flex gap-2 flex-1 min-w-[260px]">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                            <input type="text" name="reason" placeholder="Rejection reason&hellip;" class="field-input flex-1" required>
                            <button type="submit" class="btn-danger btn-sm">Reject</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
