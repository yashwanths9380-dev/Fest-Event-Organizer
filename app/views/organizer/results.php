<?php $pageTitle = 'Record Results'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-5xl mx-auto px-4 py-8 flex-1 w-full">
    <a href="/index.php?route=organizer/events" class="text-cyan text-sm font-semibold hover:underline">&larr; Back to My Events</a>
    <h1 class="font-display text-2xl font-bold mt-2 mb-1"><?= e($event['title']) ?> &mdash; Results</h1>
    <p class="text-slateink text-sm mb-6">Record a position for each registration. Recording again for the same registration updates the existing entry.</p>

    <?php if (empty($registrations)): ?>
        <div class="card"><p class="empty-state">No registrations to record results for yet.</p></div>
    <?php else: ?>
        <div class="card">
            <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Team / Leader</th><th>Current Result</th><th>Set Result</th></tr></thead>
                <tbody>
                <?php foreach ($registrations as $r):
                    $existing = $resultsByRegistration[(int)$r['id']] ?? null; ?>
                    <tr>
                        <td class="font-semibold"><?= e($r['team_name'] ?: $r['leader_name']) ?></td>
                        <td>
                            <?php if ($existing): ?>
                                <?php $posBadge = ['winner' => 'badge-gold', 'runner_up' => 'badge-teal', 'second_runner_up' => 'badge-cyan', 'participant' => 'badge-slate'][$existing['position']]; ?>
                                <span class="badge <?= $posBadge ?>"><?= e(str_replace('_', ' ', $existing['position'])) ?></span>
                            <?php else: ?>
                                <span class="text-xs text-slateink">Not recorded</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="/index.php?route=organizer/results/store" class="flex gap-2 flex-wrap items-center">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                                <input type="hidden" name="registration_id" value="<?= (int)$r['id'] ?>">
                                <select name="position" class="field-select" style="width:auto">
                                    <?php foreach (['winner' => 'Winner', 'runner_up' => 'Runner-up', 'second_runner_up' => '2nd Runner-up', 'participant' => 'Participant'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= ($existing['position'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="remarks" placeholder="Remarks (optional)" value="<?= e($existing['remarks'] ?? '') ?>" class="field-input" style="width:180px">
                                <button type="submit" class="btn-primary btn-sm">Save</button>
                                <?php if ($existing): ?>
                                <button type="submit" formaction="/index.php?route=organizer/results/delete" name="id" value="<?= (int)$existing['id'] ?>" class="btn-danger btn-sm">Remove</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
