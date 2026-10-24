<?php $pageTitle = $fest['name']; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <a href="/index.php?route=participant/fests" class="text-cyan text-sm font-semibold hover:underline">&larr; Back to Fests</a>
    <h1 class="font-display text-2xl font-bold mt-2 mb-1"><?= e($fest['name']) ?></h1>
    <p class="text-slateink text-sm mb-4"><?= e($fest['description']) ?></p>

    <div class="flex gap-2 flex-wrap mb-6 text-sm">
        <a href="/index.php?route=participant/fests/show&id=<?= (int)$fest['id'] ?>" class="px-3 py-1.5 rounded-lg font-medium <?= $category === '' ? 'bg-teal-dark text-white' : 'bg-white border border-line text-slateink' ?>">All</a>
        <?php foreach (['competition' => 'Competitions', 'workshop' => 'Workshops', 'show' => 'Shows', 'seminar' => 'Seminars'] as $val => $label): ?>
            <a href="/index.php?route=participant/fests/show&id=<?= (int)$fest['id'] ?>&category=<?= $val ?>" class="px-3 py-1.5 rounded-lg font-medium <?= $category === $val ? 'bg-teal-dark text-white' : 'bg-white border border-line text-slateink' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($events)): ?>
        <div class="card"><p class="empty-state">No events in this filter.</p></div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 gap-5">
            <?php foreach ($events as $ev):
                $full = (int)$ev['seats_taken'] >= (int)$ev['capacity'];
                $pct = (int)$ev['capacity'] > 0 ? min(100, round(100 * (int)$ev['seats_taken'] / (int)$ev['capacity'])) : 0;
                $registered = in_array((int)$ev['id'], $myRegisteredEventIds, true);
                // Category is carried by colour on both the card's top edge and its
                // badge, so the grid is scannable by event type at a glance.
                $catBadge = ['competition' => 'badge-cyan', 'workshop' => 'badge-teal',
                             'show' => 'badge-gold', 'seminar' => 'badge-slate'][$ev['category']] ?? 'badge-slate';
            ?>
                <a href="/index.php?route=participant/events/show&id=<?= (int)$ev['id'] ?>" class="event-card event-card--<?= e($ev['category']) ?> block">
                    <div class="flex items-center justify-between mb-1">
                        <h2 class="font-display text-lg font-bold"><?= e($ev['title']) ?></h2>
                        <span class="badge <?= $catBadge ?>"><?= e(ucfirst($ev['category'])) ?></span>
                    </div>
                    <p class="text-xs text-slateink mb-2"><?= e($ev['club_name']) ?> &middot; <?= e($ev['venue']) ?> &middot; <?= format_datetime($ev['start_time']) ?></p>
                    <p class="text-sm text-slateink mb-3 line-clamp-2"><?= e(mb_strimwidth($ev['description'], 0, 120, '...')) ?></p>
                    <div class="capacity-bar mb-1"><div class="capacity-bar-fill <?= $full ? 'full' : '' ?>" style="width: <?= $pct ?>%"></div></div>
                    <div class="flex items-center justify-between text-xs text-slateink">
                        <span><?= (int)$ev['seats_taken'] ?>/<?= (int)$ev['capacity'] ?> seats &middot; team <?= (int)$ev['team_size_min'] ?>&ndash;<?= (int)$ev['team_size_max'] ?></span>
                        <?php if ($registered): ?><span class="badge badge-mint">Registered</span>
                        <?php elseif ($full): ?><span class="badge badge-rose">Full</span>
                        <?php else: ?><span class="badge badge-gold">&#8377;<?= number_format((float)$ev['entry_fee'], 0) ?></span><?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
