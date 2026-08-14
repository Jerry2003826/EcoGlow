/*
 * Customer account shell — off-canvas rail toggle.
 * Mirrors the staff console drawer (admin.js), scoped to the account hooks.
 */
(function () {
    'use strict';

    var app = document.querySelector('.account-app');
    var toggle = document.querySelector('[data-account-nav-toggle]');
    var backdrop = document.querySelector('[data-account-nav-backdrop]');
    var sidebar = document.getElementById('account-sidebar');

    function setNav(open) {
        if (!app || !toggle) {
            return;
        }
        app.classList.toggle('is-nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        if (backdrop) {
            backdrop.hidden = !open;
        }
        if (sidebar) {
            var mobile = window.matchMedia('(max-width: 991.98px)').matches;
            if (mobile && !open) {
                sidebar.setAttribute('aria-hidden', 'true');
                sidebar.setAttribute('inert', '');
            } else {
                sidebar.removeAttribute('aria-hidden');
                sidebar.removeAttribute('inert');
            }
        }
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            setNav(!app.classList.contains('is-nav-open'));
        });
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            setNav(false);
        }
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setNav(false);
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setNav(false);
        }
    });
})();
