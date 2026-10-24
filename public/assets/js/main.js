// Fest Event Organizer — small progressive-enhancement touches, no framework.
document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss flash banners after a few seconds.
    document.querySelectorAll('.alert-success').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });

    // Mobile nav: toggle the collapsible panel, keep aria-expanded in sync.
    var navToggle = document.getElementById('nav-toggle');
    var navPanel = document.getElementById('mobile-nav-panel');
    if (navToggle && navPanel) {
        navToggle.addEventListener('click', function () {
            var open = !navPanel.hidden;
            navPanel.hidden = open;
            navToggle.setAttribute('aria-expanded', String(!open));
        });
    }

    // Confirm before any destructive/state-changing action marked data-confirm.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Team registration form: add/remove teammate rows, client-side only
    // (the server re-derives team size from however many named rows survive
    // and re-checks capacity/conflict regardless of what the client sends).
    var teamWrap = document.getElementById('team-members');
    var addBtn = document.getElementById('add-member-btn');
    if (teamWrap && addBtn) {
        addBtn.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'flex gap-2 items-start member-row';
            row.innerHTML =
                '<input type="text" name="member_name[]" placeholder="Teammate name" class="field-input flex-1">' +
                '<input type="email" name="member_email[]" placeholder="Email (optional)" class="field-input flex-1">' +
                '<button type="button" class="btn-secondary btn-sm remove-member-btn">Remove</button>';
            teamWrap.appendChild(row);
        });
        teamWrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-member-btn')) {
                e.target.closest('.member-row').remove();
            }
        });
    }
});
