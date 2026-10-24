<?php
$pageTitle = 'Certificate';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
require __DIR__ . '/../partials/flash.php';
$positionLabel = [
    'winner' => 'Winner',
    'runner_up' => 'Runner-up',
    'second_runner_up' => 'Second Runner-up',
    'participant' => 'Participant',
][$certificate['position']];
?>
<main class="max-w-3xl mx-auto px-4 py-8 flex-1 w-full">
    <a href="/index.php?route=participant/certificates" class="text-cyan text-sm font-semibold hover:underline">&larr; Back to Certificates</a>

    <div class="certificate-card mt-4">
        <p class="text-xs uppercase tracking-widest text-slateink font-semibold mb-2">Certificate of <?= $certificate['position'] === 'participant' ? 'Participation' : 'Achievement' ?></p>
        <h1 class="font-display text-3xl font-extrabold text-ink mb-4">Fest Event Organizer</h1>
        <p class="text-sm text-slateink mb-1">This certifies that</p>
        <p class="font-display text-2xl font-bold text-cyan mb-1"><?= e($certificate['team_name'] ?: $certificate['leader_name']) ?></p>
        <?php if ($certificate['team_name']): ?><p class="text-xs text-slateink mb-3">Team led by <?= e($certificate['leader_name']) ?></p><?php else: ?><div class="mb-3"></div><?php endif; ?>
        <p class="text-sm text-slateink mb-1">achieved</p>
        <p class="font-display text-xl font-bold text-gold mb-3"><?= e($positionLabel) ?></p>
        <p class="text-sm text-slateink mb-1">in</p>
        <p class="font-display text-xl font-bold text-ink mb-1"><?= e($certificate['event_title']) ?></p>
        <p class="text-xs text-slateink mb-4">at <?= e($certificate['fest_name']) ?></p>
        <?php if (!empty($certificate['remarks'])): ?>
            <p class="text-xs italic text-slateink mb-4">"<?= e($certificate['remarks']) ?>"</p>
        <?php endif; ?>
        <p class="text-xs text-slateink mt-6">Recorded on <?= format_date($certificate['recorded_at']) ?> by the Fest Committee</p>
    </div>

    <div class="mt-4 text-center">
        <button onclick="window.print()" class="btn-secondary btn-sm">Print Certificate</button>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
