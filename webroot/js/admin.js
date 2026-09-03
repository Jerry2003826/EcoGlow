
(function () {
    'use strict';

    var BREAKPOINT = 1199.98;

    var sidebar = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('admin-overlay');
    var toggle = document.getElementById('admin-menu-toggle');
    var page = document.getElementById('admin-page');

    if (!sidebar || !overlay || !toggle) {
        return;
    }

    function openDrawer() {
        sidebar.classList.add('is-open');

        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');

        toggle.setAttribute('aria-expanded', 'true');

        if (page) {
            page.inert = true;
            page.setAttribute('aria-hidden', 'true');
        }
    }

    function closeDrawer() {
        sidebar.classList.remove('is-open');

        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');

        toggle.setAttribute('aria-expanded', 'false');

        if (page) {
            page.inert = false;
            page.removeAttribute('aria-hidden');
        }
    }

    function handleResize() {
        if (window.innerWidth > BREAKPOINT) {
            sidebar.classList.remove('is-open');

            overlay.hidden = true;
            overlay.setAttribute('aria-hidden', 'true');

            toggle.setAttribute('aria-expanded', 'false');

            if (page) {
                page.inert = false;
                page.removeAttribute('aria-hidden');
            }
        } else {
            if (!sidebar.classList.contains('is-open')) {
                overlay.hidden = true;
                overlay.setAttribute('aria-hidden', 'true');
            }
        }
    }

    toggle.addEventListener('click', function () {
        if (sidebar.classList.contains('is-open')) {
            closeDrawer();
            return;
        }

        openDrawer();
    });

    overlay.addEventListener('click', function () {
        closeDrawer();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });

    window.addEventListener('resize', handleResize);

    handleResize();
})();
