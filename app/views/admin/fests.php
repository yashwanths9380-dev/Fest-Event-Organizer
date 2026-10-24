<?php $pageTitle = 'Manage Fests'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h1 class="font-display text-2xl font-bold">Fests</h1>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <?php if (empty($fests)): ?>
                <p class="empty-state">No fests created yet — use the form to create your first one.</p>
            <?php endif; ?>
            <?php foreach ($fests as $f): ?>
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2><?= e($f['name']) ?> <span class="text-slateink font-normal text-sm">&middot; "<?= e($f['theme']) ?>"</span></h2>
                            <p class="text-xs text-slateink mt-1"><?= format_date($f['start_date']) ?> &ndash; <?= format_date($f['end_date']) ?> &middot; <?= e($f['venue']) ?> &middot; <?= (int)$f['event_count'] ?> event(s)</p>
                        </div>
                        <?php $badgeClass = $f['status'] === 'published' ? 'badge-mint' : ($f['status'] === 'completed' ? 'badge-teal' : 'badge-slate'); ?>
                        <span class="badge <?= $badgeClass ?>"><?= e($f['status']) ?></span>
                    </div>
                    <p class="text-sm text-slateink mb-4"><?= e($f['description']) ?></p>
                    <div class="flex gap-2 flex-wrap">
                        <?php if ($f['status'] === 'draft'): ?>
                            <form method="POST" action="/index.php?route=admin/fests/status">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                <input type="hidden" name="status" value="published">
                                <button type="submit" class="btn-primary btn-sm">Publish</button>
                            </form>
                        <?php elseif ($f['status'] === 'published'): ?>
                            <form method="POST" action="/index.php?route=admin/fests/status">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn-secondary btn-sm">Mark Completed</button>
                            </form>
                            <?php if ((int)($f['confirmed_registrations'] ?? 0) === 0): ?>
                                <form method="POST" action="/index.php?route=admin/fests/status">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                    <input type="hidden" name="status" value="draft">
                                    <button type="submit" class="btn-secondary btn-sm">Revert to Draft</button>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-slate" title="Un-publishing would hide this fest from participants who already hold e-passes for its events.">
                                    Locked &mdash; <?= (int)$f['confirmed_registrations'] ?> confirmed
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card h-fit">
            <div class="card-head"><h2>Create Fest</h2></div>
            <form method="POST" action="/index.php?route=admin/fests/store" class="space-y-3">
                <?= Csrf::field() ?>
                <div>
                    <label class="field-label">Name</label>
                    <input type="text" name="name" value="<?= old($old ?? [], 'name') ?>" class="field-input" required>
                    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Theme</label>
                    <input type="text" name="theme" value="<?= old($old ?? [], 'theme') ?>" class="field-input" placeholder="e.g. Neon Odyssey" required>
                    <?php if (!empty($errors['theme'])): ?><p class="field-error"><?= e($errors['theme']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Description</label>
                    <textarea name="description" rows="3" class="field-textarea" required><?= old($old ?? [], 'description') ?></textarea>
                    <?php if (!empty($errors['description'])): ?><p class="field-error"><?= e($errors['description']) ?></p><?php endif; ?>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="field-label">Start Date</label>
                        <input type="date" name="start_date" value="<?= old($old ?? [], 'start_date') ?>" class="field-input" required>
                        <?php if (!empty($errors['start_date'])): ?><p class="field-error"><?= e($errors['start_date']) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label class="field-label">End Date</label>
                        <input type="date" name="end_date" value="<?= old($old ?? [], 'end_date') ?>" class="field-input" required>
                        <?php if (!empty($errors['end_date'])): ?><p class="field-error"><?= e($errors['end_date']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div>
                    <label class="field-label">Venue</label>
                    <input type="text" name="venue" value="<?= old($old ?? [], 'venue') ?>" class="field-input" required>
                    <?php if (!empty($errors['venue'])): ?><p class="field-error"><?= e($errors['venue']) ?></p><?php endif; ?>
                </div>
                <button type="submit" class="btn-primary w-full">Create Fest (Draft)</button>
            </form>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
