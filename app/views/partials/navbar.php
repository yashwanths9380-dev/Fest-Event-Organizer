<?php
/** @var string|null $currentNav */
$role = Auth::role();
$navItems = [];
if ($role === 'super_admin') {
    $navItems = [
        'admin/dashboard' => 'Dashboard',
        'admin/fests'     => 'Fests',
        'admin/clubs'     => 'Clubs',
        'admin/events'    => 'Event Approvals',
        'admin/clashes'   => 'Venue Clashes',
        'admin/results'   => 'Results',
    ];
} elseif ($role === 'organizer') {
    $navItems = [
        'organizer/dashboard' => 'Dashboard',
        'organizer/events'    => 'My Events',
        'organizer/fests'     => 'Browse Fests',
        'organizer/club'      => 'Club Profile',
    ];
} elseif ($role === 'participant') {
    $navItems = [
        'participant/dashboard'     => 'Dashboard',
        'participant/fests'         => 'Fests',
        'participant/registrations' => 'My Registrations',
        'participant/certificates'  => 'Certificates',
    ];
}
$currentRoute = $_GET['route'] ?? '';
?>
<nav class="bg-teal-dark sticky top-0 z-30" id="site-nav">
    <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-16">
        <a href="/index.php?route=<?= e(Auth::dashboardRoute($role ?? '')) ?>" class="flex items-center gap-2.5 shrink-0">
            <span class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center font-display font-extrabold text-white text-base">FE</span>
            <span class="font-display font-bold text-white text-lg tracking-tight hidden sm:inline">Fest&nbsp;Organizer</span>
        </a>
        <div class="hidden md:flex flex-1 items-center gap-1 text-sm font-medium px-3">
            <?php foreach ($navItems as $route => $label): ?>
                <a href="/index.php?route=<?= e($route) ?>"
                   class="nav-link px-3 py-2 rounded-lg whitespace-nowrap <?= $currentRoute === $route ? 'nav-link-active' : '' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="hidden md:flex items-center gap-3 shrink-0">
            <span class="flex flex-col items-end leading-tight">
                <span class="text-white text-sm font-semibold"><?= e(Auth::name()) ?></span>
                <span class="text-white/70 text-xs capitalize"><?= e(str_replace('_', ' ', $role ?? '')) ?></span>
            </span>
            <form method="POST" action="/index.php?route=logout">
                <?= Csrf::field() ?>
                <button type="submit" class="btn-ghost-dark btn-sm">Logout</button>
            </form>
        </div>
        <button type="button" id="nav-toggle" class="md:hidden nav-toggle-btn" aria-label="Toggle menu" aria-expanded="false" aria-controls="mobile-nav-panel">
            <span></span><span></span><span></span>
        </button>
    </div>
    <div id="mobile-nav-panel" class="md:hidden nav-panel" hidden>
        <div class="flex flex-col gap-1 px-4 py-3">
            <?php foreach ($navItems as $route => $label): ?>
                <a href="/index.php?route=<?= e($route) ?>"
                   class="nav-link px-3 py-2.5 rounded-lg <?= $currentRoute === $route ? 'nav-link-active' : '' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-t border-white/15">
            <span class="leading-tight">
                <span class="block text-white text-sm font-semibold"><?= e(Auth::name()) ?></span>
                <span class="block text-white/70 text-xs capitalize"><?= e(str_replace('_', ' ', $role ?? '')) ?></span>
            </span>
            <form method="POST" action="/index.php?route=logout">
                <?= Csrf::field() ?>
                <button type="submit" class="btn-ghost-dark btn-sm">Logout</button>
            </form>
        </div>
    </div>
</nav>
