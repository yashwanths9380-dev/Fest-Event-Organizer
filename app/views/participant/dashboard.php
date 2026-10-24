<?php $pageTitle = 'My Dashboard'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-1">Welcome, <?= e(Auth::name()) ?></h1>
    <p class="text-slateink text-sm mb-6">Your upcoming schedule and passes at a glance.</p>

    <div class="grid grid-cols-3 gap-3 mb-8">
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= count($registrations) ?></span><span class="stat-label">Total Registrations</span></div>
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= count($upcoming) ?></span><span class="stat-label">Upcoming Events</span></div>
        <div class="stat-tile stat-tile-gold"><span class="stat-value"><?= count($certificates) ?></span><span class="stat-label">Certificates</span></div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-head">
                <h2>My Schedule</h2>
                <a href="/index.php?route=participant/fests" class="btn-primary btn-sm">Browse Fests</a>
            </div>
            <?php if (empty($upcoming)): ?>
                <p class="empty-state">No upcoming events yet — go register for one!</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($upcoming as $r): ?>
                        <li class="flex items-center justify-between gap-3 border-b border-line pb-2 last:border-0 last:pb-0">
                            <div>
                                <p class="font-semibold text-sm"><?= e($r['event_title']) ?></p>
                                <p class="text-xs text-slateink"><?= e($r['fest_name']) ?> &middot; <?= format_datetime($r['start_time']) ?></p>
                            </div>
                            <a href="/index.php?route=participant/registrations/epass&id=<?= (int)$r['id'] ?>" class="badge badge-teal">E-Pass</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-head">
                <h2>Recent Certificates</h2>
                <a href="/index.php?route=participant/certificates" class="btn-secondary btn-sm">View All</a>
            </div>
            <?php if (empty($certificates)): ?>
                <p class="empty-state">No results recorded for you yet.</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach (array_slice($certificates, 0, 5) as $c): ?>
                        <li class="flex items-center justify-between gap-3 border-b border-line pb-2 last:border-0 last:pb-0">
                            <div>
                                <p class="font-semibold text-sm"><?= e($c['event_title']) ?></p>
                                <p class="text-xs text-slateink"><?= e($c['fest_name']) ?></p>
                            </div>
                            <a href="/index.php?route=participant/certificates/show&id=<?= (int)$c['id'] ?>" class="badge badge-gold"><?= e(str_replace('_', ' ', $c['position'])) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
