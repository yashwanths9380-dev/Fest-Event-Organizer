<?php $pageTitle = 'My Events'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h1 class="font-display text-2xl font-bold">My Events &mdash; <?= e($club['name']) ?></h1>
        <?php if ($club['status'] === 'active'): ?>
            <a href="/index.php?route=organizer/events/new" class="btn-primary btn-sm">Propose New Event</a>
        <?php endif; ?>
    </div>

    <?php if ($club['status'] !== 'active'): ?>
        <div class="alert alert-error">
            <?= e($club['name']) ?> has been deactivated by the Fest Committee. Its events are hidden from participants and
            it can no longer propose or change events, verify e-passes, or record results. Existing rosters stay visible below.
        </div>
    <?php endif; ?>

    <?php if (empty($events)): ?>
        <div class="card"><p class="empty-state">No events proposed yet.</p></div>
    <?php endif; ?>

    <div class="space-y-4">
        <?php foreach ($events as $ev): ?>
            <div class="event-card">
                <div class="card-head">
                    <div>
                        <h2 class="font-display text-lg font-bold"><?= e($ev['title']) ?></h2>
                        <p class="text-xs text-slateink mt-1"><?= e($ev['fest_name']) ?> &middot; <?= e(ucfirst($ev['category'])) ?> &middot; <?= e($ev['venue']) ?></p>
                    </div>
                    <?php $badgeClass = ['pending' => 'badge-gold', 'approved' => 'badge-mint', 'rejected' => 'badge-rose'][$ev['status']]; ?>
                    <span class="badge <?= $badgeClass ?>"><?= e($ev['status']) ?></span>
                </div>
                <div class="grid sm:grid-cols-4 gap-3 text-xs text-slateink mb-4">
                    <div><span class="font-semibold text-ink">When:</span> <?= format_datetime($ev['start_time']) ?></div>
                    <div><span class="font-semibold text-ink">Capacity:</span> <?= (int)$ev['seats_taken'] ?>/<?= (int)$ev['capacity'] ?></div>
                    <div><span class="font-semibold text-ink">Team size:</span> <?= (int)$ev['team_size_min'] ?>&ndash;<?= (int)$ev['team_size_max'] ?></div>
                    <div><span class="font-semibold text-ink">Fee:</span> &#8377;<?= number_format((float)$ev['entry_fee'], 2) ?></div>
                </div>
                <?php if ($ev['status'] === 'rejected' && $ev['rejection_reason']): ?>
                    <p class="text-xs text-rose mb-3"><span class="font-semibold">Committee note:</span> <?= e($ev['rejection_reason']) ?></p>
                <?php endif; ?>
                <div class="flex gap-2 flex-wrap items-center">
                    <?php if ((int)$ev['seats_taken'] > 0): ?>
                        <span class="badge badge-slate" title="Editing would send the event back for re-approval and pull it off the published schedule.">Locked &mdash; <?= (int)$ev['seats_taken'] ?> confirmed</span>
                    <?php elseif ($club['status'] === 'active'): ?>
                        <a href="/index.php?route=organizer/events/edit&id=<?= (int)$ev['id'] ?>" class="btn-secondary btn-sm">Edit</a>
                    <?php endif; ?>
                    <a href="/index.php?route=organizer/events/roster&id=<?= (int)$ev['id'] ?>" class="btn-secondary btn-sm">Roster (<?= (int)$ev['seats_taken'] ?>)</a>
                    <?php if ($ev['status'] === 'approved'): ?>
                        <a href="/index.php?route=organizer/events/results&id=<?= (int)$ev['id'] ?>" class="btn-secondary btn-sm">Results</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
