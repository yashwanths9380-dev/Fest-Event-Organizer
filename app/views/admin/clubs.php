<?php $pageTitle = 'Manage Clubs'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-6">Clubs</h1>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <?php if (empty($clubs)): ?>
                <p class="empty-state">No clubs yet.</p>
            <?php endif; ?>
            <?php foreach ($clubs as $c): ?>
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2><?= e($c['name']) ?></h2>
                            <p class="text-xs text-slateink mt-1">Organizer: <?= e($c['organizer_name']) ?> (<?= e($c['organizer_email']) ?>) &middot; <?= (int)$c['event_count'] ?> event(s)</p>
                        </div>
                        <span class="badge <?= $c['status'] === 'active' ? 'badge-mint' : 'badge-slate' ?>"><?= e($c['status']) ?></span>
                    </div>
                    <p class="text-sm text-slateink mb-4"><?= e($c['description']) ?></p>
                    <form method="POST" action="/index.php?route=admin/clubs/status">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <input type="hidden" name="status" value="<?= $c['status'] === 'active' ? 'inactive' : 'active' ?>">
                        <button type="submit" class="btn-secondary btn-sm"><?= $c['status'] === 'active' ? 'Deactivate' : 'Reactivate' ?></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card h-fit">
            <div class="card-head"><h2>Create Club</h2></div>
            <?php if (empty($availableOrganizers)): ?>
                <p class="field-hint mb-3">Every organizer account already manages a club. Ask an admin to seed another organizer account first.</p>
            <?php endif; ?>
            <form method="POST" action="/index.php?route=admin/clubs/store" class="space-y-3">
                <?= Csrf::field() ?>
                <div>
                    <label class="field-label">Club Name</label>
                    <input type="text" name="name" class="field-input" required>
                </div>
                <div>
                    <label class="field-label">Description</label>
                    <textarea name="description" rows="3" class="field-textarea" required></textarea>
                </div>
                <div>
                    <label class="field-label">Organizer Account</label>
                    <select name="organizer_id" class="field-select" required <?= empty($availableOrganizers) ? 'disabled' : '' ?>>
                        <option value="">Select organizer&hellip;</option>
                        <?php foreach ($availableOrganizers as $o): ?>
                            <option value="<?= (int)$o['id'] ?>"><?= e($o['name']) ?> (<?= e($o['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary w-full" <?= empty($availableOrganizers) ? 'disabled' : '' ?>>Create Club</button>
            </form>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
