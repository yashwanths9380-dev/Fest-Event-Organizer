<?php $pageTitle = 'Venue & Time Clashes'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-1">Venue &amp; Time Clash Report</h1>
    <p class="text-slateink text-sm mb-6">Every pair of <em>approved</em> events sharing the same venue whose time ranges overlap, so you can resolve scheduling before the fest. This report is informational only &mdash; it doesn't block approval automatically, since two clubs might genuinely want a shared venue split by time (a false positive here just needs a human look).</p>

    <?php if (empty($clashes)): ?>
        <div class="card"><p class="empty-state">No venue/time clashes among currently approved events. Nice.</p></div>
    <?php else: ?>
        <div class="card">
            <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Fest</th><th>Venue</th><th>Event A</th><th>Event B</th></tr></thead>
                <tbody>
                <?php foreach ($clashes as $c): ?>
                    <tr>
                        <td><?= e($c['fest_name']) ?></td>
                        <td class="font-semibold"><?= e($c['venue']) ?></td>
                        <td>
                            <?= e($c['event_a_title']) ?><br>
                            <span class="text-xs text-slateink"><?= format_datetime($c['a_start']) ?> &ndash; <?= format_datetime($c['a_end']) ?></span>
                        </td>
                        <td>
                            <?= e($c['event_b_title']) ?><br>
                            <span class="text-xs text-slateink"><?= format_datetime($c['b_start']) ?> &ndash; <?= format_datetime($c['b_end']) ?></span>
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
