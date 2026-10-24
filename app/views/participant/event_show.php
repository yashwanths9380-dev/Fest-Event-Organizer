<?php $pageTitle = $event['title']; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php';
$seatsRemaining = max(0, (int)$event['capacity'] - (int)$event['seats_taken']);
$full = $seatsRemaining <= 0;
$soloOnly = (int)$event['team_size_max'] === 1;
$started = strtotime($event['start_time']) <= time();
?>
<main class="max-w-4xl mx-auto px-4 py-8 flex-1 w-full">
    <a href="/index.php?route=participant/fests/show&id=<?= (int)$event['fest_id'] ?>" class="text-cyan text-sm font-semibold hover:underline">&larr; Back to <?= e($event['fest_name']) ?></a>

    <div class="card mt-3">
        <div class="card-head">
            <div>
                <h1 class="font-display text-2xl font-bold"><?= e($event['title']) ?></h1>
                <p class="text-xs text-slateink mt-1"><?= e($event['club_name']) ?> &middot; <?= e(ucfirst($event['category'])) ?></p>
            </div>
            <span class="badge badge-gold">&#8377;<?= number_format((float)$event['entry_fee'], 2) ?></span>
        </div>
        <p class="text-sm text-slateink mb-4"><?= nl2br(e($event['description'])) ?></p>
        <div class="grid sm:grid-cols-4 gap-3 text-xs text-slateink">
            <div><span class="font-semibold text-ink">Venue:</span> <?= e($event['venue']) ?></div>
            <div><span class="font-semibold text-ink">Start:</span> <?= format_datetime($event['start_time']) ?></div>
            <div><span class="font-semibold text-ink">End:</span> <?= format_datetime($event['end_time']) ?></div>
            <div><span class="font-semibold text-ink">Team size:</span> <?= (int)$event['team_size_min'] ?>&ndash;<?= (int)$event['team_size_max'] ?></div>
        </div>
        <div class="capacity-bar mt-4 mb-1"><div class="capacity-bar-fill <?= $full ? 'full' : '' ?>" style="width: <?= (int)$event['capacity'] > 0 ? min(100, round(100 * (int)$event['seats_taken'] / (int)$event['capacity'])) : 0 ?>%"></div></div>
        <p class="text-xs text-slateink"><?= (int)$event['seats_taken'] ?>/<?= (int)$event['capacity'] ?> seats filled &middot; <?= $seatsRemaining ?> remaining</p>
    </div>

    <div class="card">
        <div class="card-head"><h2>Register</h2></div>
        <?php if ($alreadyRegistered): ?>
            <p class="text-sm text-mint font-semibold">You're already registered for this event &mdash; check "My Registrations" for your e-pass.</p>
        <?php elseif ($started): ?>
            <p class="text-sm text-rose font-semibold">Registration is closed &mdash; this event has already started.</p>
        <?php elseif ($full): ?>
            <p class="text-sm text-rose font-semibold">This event is full.</p>
        <?php else: ?>
            <form method="POST" action="/index.php?route=participant/events/register" class="space-y-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">

                <?php if (!$soloOnly): ?>
                <div>
                    <label class="field-label">Team Name (optional)</label>
                    <input type="text" name="team_name" class="field-input" placeholder="e.g. The Amplifiers">
                </div>

                <div>
                    <label class="field-label">Teammates (you are the leader &mdash; add the rest of your team)</label>
                    <p class="field-hint mb-2">This event needs <?= (int)$event['team_size_min'] ?>&ndash;<?= (int)$event['team_size_max'] ?> members total, including you. Add <?= max(0, (int)$event['team_size_min'] - 1) ?> or more teammates below.</p>
                    <div id="team-members" class="space-y-2">
                        <?php for ($i = 0; $i < max(0, (int)$event['team_size_min'] - 1); $i++): ?>
                            <div class="flex gap-2 items-start member-row">
                                <input type="text" name="member_name[]" placeholder="Teammate name" class="field-input flex-1">
                                <input type="email" name="member_email[]" placeholder="Email (optional)" class="field-input flex-1">
                                <?php if ($i > 0): ?><button type="button" class="btn-secondary btn-sm remove-member-btn">Remove</button><?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <button type="button" id="add-member-btn" class="btn-secondary btn-sm mt-2">+ Add Teammate</button>
                </div>
                <?php else: ?>
                    <p class="field-hint">This is a solo-entry event &mdash; no teammates needed.</p>
                <?php endif; ?>

                <button type="submit" class="btn-primary w-full" <?= $seatsRemaining <= 0 ? 'disabled' : '' ?>>Register &amp; Get E-Pass</button>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
