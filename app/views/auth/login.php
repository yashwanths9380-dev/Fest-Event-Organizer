<?php $pageTitle = 'Login'; require __DIR__ . '/../partials/header.php'; ?>
<div class="auth-hero flex-1 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <span class="auth-logo-badge font-display font-extrabold text-xl mb-3">FE</span>
            <h1 class="font-display text-3xl font-extrabold text-ink">Fest Event Organizer</h1>
            <p class="text-slateink text-sm mt-1">Sign in to plan, run, or join the fest.</p>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-7 border border-line">
            <?php if ($flashError = flash_get('error')): ?>
                <div class="alert alert-error"><?= e($flashError) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['login'])): ?>
                <div class="alert alert-error"><?= e($errors['login']) ?></div>
            <?php endif; ?>
            <?php $success = flash_get('success'); if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            <form method="POST" action="/index.php?route=login" class="space-y-4">
                <?= Csrf::field() ?>
                <div>
                    <label class="field-label">Email</label>
                    <input type="email" name="email" value="<?= old($old ?? [], 'email') ?>" class="field-input" required>
                    <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Password</label>
                    <input type="password" name="password" class="field-input" required>
                    <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
                </div>
                <button type="submit" class="btn-primary w-full">Sign In</button>
            </form>
            <p class="text-center text-sm text-slateink mt-5">
                New participant? <a href="/index.php?route=register" class="text-cyan font-semibold hover:underline">Create an account</a>
            </p>

            <details class="demo-creds">
                <summary>Demo credentials</summary>
                <table>
                    <tr><th>Role</th><th>Email</th><th>Password</th></tr>
                    <tr><td>Fest Committee</td><td><code>committee@fest.test</code></td><td><code>Password123!</code></td></tr>
                    <tr><td>Club Organizer</td><td><code>organizer.coding@fest.test</code></td><td><code>Password123!</code></td></tr>
                    <tr><td>Participant</td><td><code>aarav@student.test</code></td><td><code>Password123!</code></td></tr>
                </table>
            </details>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
