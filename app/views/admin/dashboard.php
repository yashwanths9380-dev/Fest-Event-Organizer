<?php $pageTitle = 'Committee Dashboard'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-1">Fest Committee Dashboard</h1>
    <p class="text-slateink text-sm mb-6">Suite-wide view across every fest, club and event.</p>

    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-8">
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= $stats['fests'] ?></span><span class="stat-label">Fests</span></div>
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= $stats['clubs'] ?></span><span class="stat-label">Clubs</span></div>
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= $stats['events'] ?></span><span class="stat-label">Events</span></div>
        <div class="stat-tile stat-tile-alert"><span class="stat-value"><?= $stats['events_pending'] ?></span><span class="stat-label">Pending Approval</span></div>
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= $stats['registrations'] ?></span><span class="stat-label">Registrations</span></div>
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= $stats['participants'] ?></span><span class="stat-label">Participants</span></div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-head">
                <h2>Fests</h2>
                <a href="/index.php?route=admin/fests" class="btn-secondary btn-sm">Manage Fests</a>
            </div>
            <?php if (empty($fests)): ?>
                <p class="empty-state">No fests yet.</p>
            <?php else: ?>
                <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Dates</th><th>Status</th><th>Events</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($fests, 0, 6) as $f): ?>
                        <tr>
                            <td class="font-semibold"><?= e($f['name']) ?></td>
                            <td><?= format_date($f['start_date']) ?> &ndash; <?= format_date($f['end_date']) ?></td>
                            <td>
                                <?php $badgeClass = $f['status'] === 'published' ? 'badge-mint' : ($f['status'] === 'completed' ? 'badge-teal' : 'badge-slate'); ?>
                                <span class="badge <?= $badgeClass ?>"><?= e($f['status']) ?></span>
                            </td>
                            <td><?= (int)$f['event_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-head">
                <h2>Pending Event Approvals</h2>
                <a href="/index.php?route=admin/events" class="btn-secondary btn-sm">Review All</a>
            </div>
            <?php if (empty($pendingEvents)): ?>
                <p class="empty-state">Nothing waiting on you right now.</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach (array_slice($pendingEvents, 0, 5) as $ev): ?>
                        <li class="flex items-center justify-between gap-3 border-b border-line pb-2 last:border-0 last:pb-0">
                            <div>
                                <p class="font-semibold text-sm"><?= e($ev['title']) ?></p>
                                <p class="text-xs text-slateink"><?= e($ev['club_name']) ?> &middot; <?= e($ev['fest_name']) ?></p>
                            </div>
                            <a href="/index.php?route=admin/events" class="badge badge-gold">Review</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($clashes)): ?>
    <div class="card mt-6">
        <div class="card-head">
            <h2 class="text-rose">Venue/Time Clashes Detected</h2>
            <a href="/index.php?route=admin/clashes" class="btn-secondary btn-sm">Full Report</a>
        </div>
        <p class="text-sm text-slateink"><?= count($clashes) ?> approved event pair(s) share a venue with overlapping timing. Review the clash report before publishing schedules.</p>
    </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
