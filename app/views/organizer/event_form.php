<?php
$pageTitle = isset($old['id']) ? 'Edit Event' : 'Propose Event';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
require __DIR__ . '/../partials/flash.php';

$old = $old ?? [];
$isEdit = isset($old['id']);

/** Normalizes either a DB "Y-m-d H:i:s" value or a resubmitted "Y-m-dTH:i" value into datetime-local format. */
$dtLocal = function (?string $value): string {
    if (empty($value)) {
        return '';
    }
    $ts = strtotime($value);
    return $ts === false ? '' : date('Y-m-d\TH:i', $ts);
};
?>
<main class="max-w-3xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-6"><?= $isEdit ? 'Edit Event' : 'Propose New Event' ?></h1>

    <div class="card">
        <form method="POST" action="/index.php?route=<?= $isEdit ? 'organizer/events/update' : 'organizer/events/store' ?>" class="space-y-4">
            <?= Csrf::field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$old['id'] ?>"><?php endif; ?>

            <div>
                <label class="field-label">Fest</label>
                <select name="fest_id" class="field-select" required <?= $isEdit ? 'disabled' : '' ?>>
                    <option value="">Select fest&hellip;</option>
                    <?php foreach ($fests as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= (int)($old['fest_id'] ?? 0) === (int)$f['id'] ? 'selected' : '' ?>>
                            <?= e($f['name']) ?> (<?= e($f['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="fest_id" value="<?= (int)$old['fest_id'] ?>">
                    <p class="field-hint">Fest can't be changed after creation.</p>
                <?php endif; ?>
                <?php if (!empty($errors['fest_id'])): ?><p class="field-error"><?= e($errors['fest_id']) ?></p><?php endif; ?>
            </div>

            <div>
                <label class="field-label">Title</label>
                <input type="text" name="title" value="<?= old($old, 'title') ?>" class="field-input" required>
                <?php if (!empty($errors['title'])): ?><p class="field-error"><?= e($errors['title']) ?></p><?php endif; ?>
            </div>

            <div>
                <label class="field-label">Description</label>
                <textarea name="description" rows="3" class="field-textarea" required><?= old($old, 'description') ?></textarea>
                <?php if (!empty($errors['description'])): ?><p class="field-error"><?= e($errors['description']) ?></p><?php endif; ?>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Category</label>
                    <select name="category" class="field-select" required>
                        <?php foreach (['competition' => 'Competition', 'workshop' => 'Workshop', 'show' => 'Show', 'seminar' => 'Seminar'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($old['category'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label">Venue</label>
                    <input type="text" name="venue" value="<?= old($old, 'venue') ?>" class="field-input" required>
                    <?php if (!empty($errors['venue'])): ?><p class="field-error"><?= e($errors['venue']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Start Time</label>
                    <input type="datetime-local" name="start_time" value="<?= e($dtLocal($old['start_time'] ?? '')) ?>" class="field-input" required>
                    <?php if (!empty($errors['start_time'])): ?><p class="field-error"><?= e($errors['start_time']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">End Time</label>
                    <input type="datetime-local" name="end_time" value="<?= e($dtLocal($old['end_time'] ?? '')) ?>" class="field-input" required>
                    <?php if (!empty($errors['end_time'])): ?><p class="field-error"><?= e($errors['end_time']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="field-label">Capacity (total seats)</label>
                    <input type="number" min="1" name="capacity" value="<?= old($old, 'capacity', '30') ?>" class="field-input" required>
                    <?php if (!empty($errors['capacity'])): ?><p class="field-error"><?= e($errors['capacity']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Min Team Size</label>
                    <input type="number" min="1" name="team_size_min" value="<?= old($old, 'team_size_min', '1') ?>" class="field-input" required>
                    <?php if (!empty($errors['team_size_min'])): ?><p class="field-error"><?= e($errors['team_size_min']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Max Team Size</label>
                    <input type="number" min="1" name="team_size_max" value="<?= old($old, 'team_size_max', '1') ?>" class="field-input" required>
                    <?php if (!empty($errors['team_size_max'])): ?><p class="field-error"><?= e($errors['team_size_max']) ?></p><?php endif; ?>
                </div>
            </div>

            <div>
                <label class="field-label">Entry Fee (&#8377;, 0 for free)</label>
                <input type="text" name="entry_fee" value="<?= old($old, 'entry_fee', '0') ?>" class="field-input" required>
                <?php if (!empty($errors['entry_fee'])): ?><p class="field-error"><?= e($errors['entry_fee']) ?></p><?php endif; ?>
            </div>

            <?php if ($isEdit): ?>
                <p class="field-hint">Editing a previously approved/rejected event sends it back to "pending" for Committee re-review.</p>
            <?php endif; ?>

            <button type="submit" class="btn-primary w-full"><?= $isEdit ? 'Save Changes' : 'Submit for Approval' ?></button>
        </form>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
