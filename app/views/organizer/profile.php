<?php $pageTitle = 'Club Profile'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; require __DIR__ . '/../partials/flash.php'; ?>
<main class="max-w-2xl mx-auto px-4 py-8 flex-1 w-full">
    <h1 class="font-display text-2xl font-bold mb-6">Club Profile</h1>
    <div class="card">
        <div class="card-head">
            <h2><?= e($club['name']) ?></h2>
            <span class="badge <?= $club['status'] === 'active' ? 'badge-mint' : 'badge-slate' ?>"><?= e($club['status']) ?></span>
        </div>
        <form method="POST" action="/index.php?route=organizer/club/update" class="space-y-3">
            <?= Csrf::field() ?>
            <div>
                <label class="field-label">Description</label>
                <textarea name="description" rows="4" class="field-textarea" required><?= e($club['description']) ?></textarea>
                <p class="field-hint">Club name and organizer assignment are managed by the Fest Committee.</p>
            </div>
            <button type="submit" class="btn-primary">Save Changes</button>
        </form>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
