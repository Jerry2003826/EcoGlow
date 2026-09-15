
(function () {
    'use strict';

    var BREAKPOINT = 1199.98;

    var sidebar = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('admin-overlay');
    var toggle = document.getElementById('admin-menu-toggle');
    var closeButton = document.getElementById('admin-menu-close');
    var page = document.getElementById('admin-page');

    if (!sidebar || !overlay || !toggle) {
        return;
    }

    function openDrawer() {
        sidebar.inert = false;
        sidebar.classList.add('is-open');

        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');

        toggle.setAttribute('aria-expanded', 'true');

        if (page) {
            page.inert = true;
            page.setAttribute('aria-hidden', 'true');
        }

        if (closeButton) {
            closeButton.focus();
        }
    }

    function closeDrawer() {
        var wasOpen = sidebar.classList.contains('is-open');
        sidebar.classList.remove('is-open');
        sidebar.inert = window.innerWidth <= BREAKPOINT;

        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');

        toggle.setAttribute('aria-expanded', 'false');

        if (page) {
            page.inert = false;
            page.removeAttribute('aria-hidden');
        }

        if (wasOpen && window.innerWidth <= BREAKPOINT) {
            toggle.focus();
        }
    }

    function handleResize() {
        if (window.innerWidth > BREAKPOINT) {
            sidebar.inert = false;
            sidebar.classList.remove('is-open');

            overlay.hidden = true;
            overlay.setAttribute('aria-hidden', 'true');

            toggle.setAttribute('aria-expanded', 'false');

            if (page) {
                page.inert = false;
                page.removeAttribute('aria-hidden');
            }
        } else {
            sidebar.inert = !sidebar.classList.contains('is-open');
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

    if (closeButton) {
        closeButton.addEventListener('click', closeDrawer);
    }

    document.addEventListener('keydown', function (event) {
        if (!sidebar.classList.contains('is-open')) {
            return;
        }

        if (event.key === 'Escape') {
            closeDrawer();
        }

        if (event.key === 'Tab') {
            var links = sidebar.querySelectorAll('a[href], button:not([disabled])');
            var first = links[0];
            var last = links[links.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    window.addEventListener('resize', handleResize);

    handleResize();
})();

(function () {
    'use strict';

    document.querySelectorAll('tr[data-href]').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, form, input, label')) {
                return;
            }

            var href = row.getAttribute('data-href');

            if (href) {
                window.location.href = href;
            }
        });
    });
})();
