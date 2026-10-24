<?php $pageTitle = 'E-Pass'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-2xl mx-auto px-4 py-8 flex-1 w-full">
    <a href="/index.php?route=participant/registrations" class="text-cyan text-sm font-semibold hover:underline">&larr; Back to My Registrations</a>

    <div class="epass-card mt-4">
        <div class="relative">
            <p class="text-xs uppercase tracking-wider text-white/80 font-semibold mb-1"><?= e($registration['fest_name']) ?></p>
            <h1 class="font-display text-2xl font-extrabold mb-3"><?= e($registration['event_title']) ?></h1>
            <div class="grid sm:grid-cols-2 gap-3 text-sm mb-4">
                <div><span class="text-white/70">Venue</span><br><?= e($registration['venue']) ?></div>
                <div><span class="text-white/70">When</span><br><?= format_datetime($registration['start_time']) ?></div>
                <div><span class="text-white/70">Leader</span><br><?= e($registration['leader_name']) ?></div>
                <div><span class="text-white/70">Team</span><br><?= e($registration['team_name'] ?: 'Solo Entry') ?> (<?= (int)$registration['team_size'] ?> member<?= (int)$registration['team_size'] > 1 ? 's' : '' ?>)</div>
            </div>
            <div class="epass-stub text-center">
                <p class="text-xs uppercase tracking-wider text-white/70 mb-1">E-Pass Code</p>
                <p class="epass-code"><?= e($registration['epass_code']) ?></p>
                <p class="text-xs text-white/65 mt-3">Show this code at event check-in &middot; <?= e(ucfirst($registration['status'])) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($members)): ?>
        <div class="card mt-6">
            <div class="card-head"><h2>Team Members</h2></div>
            <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th></tr></thead>
                <tbody>
                <?php foreach ($members as $m): ?>
                    <tr><td><?= e($m['member_name']) ?></td><td><?= e($m['member_email'] ?: '-') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <button onclick="window.print()" class="btn-secondary btn-sm">Print E-Pass</button>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
