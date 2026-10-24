<?php $pageTitle = 'Results Oversight'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-6">Results Across All Fests</h1>

    <?php if (empty($results)): ?>
        <div class="card"><p class="empty-state">No results recorded yet.</p></div>
    <?php else: ?>
        <div class="card">
            <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Fest</th><th>Event</th><th>Team / Leader</th><th>Position</th><th>Remarks</th><th>Recorded</th></tr></thead>
                <tbody>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td><?= e($r['fest_name']) ?></td>
                        <td class="font-semibold"><?= e($r['event_title']) ?></td>
                        <td><?= e($r['team_name'] ?: $r['leader_name']) ?></td>
                        <td>
                            <?php $posBadge = ['winner' => 'badge-gold', 'runner_up' => 'badge-teal', 'second_runner_up' => 'badge-cyan', 'participant' => 'badge-slate'][$r['position']]; ?>
                            <span class="badge <?= $posBadge ?>"><?= e(str_replace('_', ' ', $r['position'])) ?></span>
                        </td>
                        <td class="text-xs"><?= e($r['remarks'] ?: '-') ?></td>
                        <td class="text-xs text-slateink"><?= format_datetime($r['recorded_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
