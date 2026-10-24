<?php $pageTitle = 'Event Roster'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-5xl mx-auto px-4 py-8 flex-1 w-full">
    <a href="/index.php?route=organizer/events" class="text-cyan text-sm font-semibold hover:underline">&larr; Back to My Events</a>
    <h1 class="font-display text-2xl font-bold mt-2 mb-1"><?= e($event['title']) ?> &mdash; Roster</h1>
    <p class="text-slateink text-sm mb-6"><?= (int)$event['seats_taken'] ?>/<?= (int)$event['capacity'] ?> seats filled &middot; <?= count($registrations) ?> registration(s)</p>

    <div class="card mb-6">
        <div class="card-head"><h2>Verify E-Pass</h2></div>
        <p class="text-sm text-slateink mb-3">Enter the code shown on the participant's e-pass to confirm this event registration.</p>
        <form method="post" action="/index.php?route=organizer/registrations/verify" class="flex flex-col sm:flex-row gap-2">
            <?= Csrf::field() ?>
            <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
            <input type="text" name="epass_code" maxlength="20" required autocomplete="off" placeholder="E-pass code" class="form-input font-mono uppercase flex-1">
            <button type="submit" class="btn-primary">Verify Code</button>
        </form>
    </div>

    <?php if (empty($registrations)): ?>
        <div class="card"><p class="empty-state">No registrations yet.</p></div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($registrations as $r): ?>
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2><?= e($r['team_name'] ?: 'Solo Entry') ?></h2>
                            <p class="text-xs text-slateink mt-1">Leader: <?= e($r['leader_name']) ?> (<?= e($r['leader_email']) ?>) &middot; <?= e($r['leader_college']) ?></p>
                        </div>
                        <span class="badge badge-teal"><?= (int)$r['team_size'] ?> member(s)</span>
                    </div>
                    <p class="text-xs text-slateink mb-2">E-Pass: <span class="font-mono font-semibold text-ink"><?= e($r['epass_code']) ?></span> &middot; Registered <?= format_datetime($r['registered_at']) ?></p>
                    <?php if (!empty($r['members'])): ?>
                        <div class="table-scroll mt-3">
                        <table class="data-table">
                            <thead><tr><th>Teammate</th><th>Email</th></tr></thead>
                            <tbody>
                            <?php foreach ($r['members'] as $m): ?>
                                <tr><td><?= e($m['member_name']) ?></td><td><?= e($m['member_email'] ?: '-') ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
