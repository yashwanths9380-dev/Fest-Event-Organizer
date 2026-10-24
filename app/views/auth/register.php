<?php $pageTitle = 'Create Account'; require __DIR__ . '/../partials/header.php'; ?>
<div class="auth-hero flex-1 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <span class="auth-logo-badge font-display font-extrabold text-xl mb-3">FE</span>
            <h1 class="font-display text-3xl font-extrabold text-ink">Join as a Participant</h1>
            <p class="text-slateink text-sm mt-1">Register events, get your e-pass, track results.</p>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-7 border border-line">
            <form method="POST" action="/index.php?route=register" class="space-y-4">
                <?= Csrf::field() ?>
                <div>
                    <label class="field-label">Full Name</label>
                    <input type="text" name="name" value="<?= old($old ?? [], 'name') ?>" class="field-input" required>
                    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Email</label>
                    <input type="email" name="email" value="<?= old($old ?? [], 'email') ?>" class="field-input" required>
                    <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Password</label>
                    <input type="password" name="password" class="field-input" required>
                    <p class="field-hint">At least 8 characters.</p>
                    <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">College</label>
                    <input type="text" name="college" value="<?= old($old ?? [], 'college') ?>" class="field-input" required>
                    <?php if (!empty($errors['college'])): ?><p class="field-error"><?= e($errors['college']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Phone (optional)</label>
                    <input type="text" name="phone" value="<?= old($old ?? [], 'phone') ?>" class="field-input">
                </div>
                <button type="submit" class="btn-primary w-full">Create Account</button>
            </form>
            <p class="text-center text-sm text-slateink mt-5">
                Already have an account? <a href="/index.php?route=login" class="text-cyan font-semibold hover:underline">Sign in</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
