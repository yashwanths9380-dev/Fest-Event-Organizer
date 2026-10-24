<?php $pageTitle = 'Organizer Dashboard'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-1"><?= e($club['name']) ?></h1>
    <p class="text-slateink text-sm mb-6"><?= e($club['description']) ?></p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= $stats['events'] ?></span><span class="stat-label">Total Events</span></div>
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= $stats['approved'] ?></span><span class="stat-label">Approved</span></div>
        <div class="stat-tile stat-tile-alert"><span class="stat-value"><?= $stats['pending'] ?></span><span class="stat-label">Pending Review</span></div>
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= $stats['registrations'] ?></span><span class="stat-label">Seats Registered</span></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Recent Events</h2>
            <a href="/index.php?route=organizer/events/new" class="btn-primary btn-sm">Propose New Event</a>
        </div>
        <?php if (empty($events)): ?>
            <p class="empty-state">You haven't proposed any events yet.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Title</th><th>Fest</th><th>When</th><th>Status</th><th>Seats</th><th></th></tr></thead>
                <tbody>
                <?php foreach (array_slice($events, 0, 8) as $ev): ?>
                    <tr>
                        <td class="font-semibold"><?= e($ev['title']) ?></td>
                        <td><?= e($ev['fest_name']) ?></td>
                        <td><?= format_datetime($ev['start_time']) ?></td>
                        <td>
                            <?php $badgeClass = ['pending' => 'badge-gold', 'approved' => 'badge-mint', 'rejected' => 'badge-rose'][$ev['status']]; ?>
                            <span class="badge <?= $badgeClass ?>"><?= e($ev['status']) ?></span>
                        </td>
                        <td><?= (int)$ev['seats_taken'] ?>/<?= (int)$ev['capacity'] ?></td>
                        <td><a href="/index.php?route=organizer/events/roster&id=<?= (int)$ev['id'] ?>" class="text-cyan text-xs font-semibold hover:underline">Roster</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
