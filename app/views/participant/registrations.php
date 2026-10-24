<?php $pageTitle = 'My Registrations'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-5xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-6">My Registrations</h1>

    <?php if (empty($registrations)): ?>
        <div class="card"><p class="empty-state">No registrations yet. <a href="/index.php?route=participant/fests" class="text-cyan font-semibold hover:underline">Browse fests</a> to get started.</p></div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($registrations as $r): ?>
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2><?= e($r['event_title']) ?></h2>
                            <p class="text-xs text-slateink mt-1"><?= e($r['fest_name']) ?> &middot; <?= format_datetime($r['start_time']) ?> &middot; <?= e($r['venue']) ?></p>
                        </div>
                        <span class="badge <?= $r['status'] === 'confirmed' ? 'badge-mint' : 'badge-slate' ?>"><?= e($r['status']) ?></span>
                    </div>
                    <p class="text-xs text-slateink mb-3">E-Pass: <span class="font-mono font-semibold text-ink"><?= e($r['epass_code']) ?></span><?= $r['team_name'] ? ' &middot; Team: ' . e($r['team_name']) : '' ?> &middot; <?= (int)$r['team_size'] ?> member(s)</p>
                    <div class="flex gap-2 flex-wrap">
                        <a href="/index.php?route=participant/registrations/epass&id=<?= (int)$r['id'] ?>" class="btn-secondary btn-sm">View E-Pass</a>
                        <?php if (Registration::isCancellable($r)): ?>
                        <form method="POST" action="/index.php?route=participant/registrations/cancel" data-confirm="Cancel this registration? This frees your seats for others.">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn-danger btn-sm">Cancel</button>
                        </form>
                        <?php elseif ($r['status'] === 'confirmed'): ?>
                            <span class="text-xs text-slateink self-center">
                                <?= !empty($r['has_result']) ? 'Result recorded — no longer cancellable.' : 'This event has started — no longer cancellable.' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
