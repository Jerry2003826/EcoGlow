
(function () {
    'use strict';

    var BREAKPOINT = 1199.98;

    var sidebar = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('admin-overlay');
    var toggle = document.getElementById('admin-menu-toggle');
    var closeButton = document.getElementById('admin-menu-close');
    var page = document.getElementById('admin-page');
    var profileMenu = document.querySelector('.admin-profile-menu');
    var profileToggle = document.querySelector('.admin-profile-toggle');
    var profileDropdown = document.querySelector('.admin-profile-dropdown');

    function closeProfileMenu() {
        if (!profileToggle || !profileDropdown) {
            return;
        }

        profileDropdown.hidden = true;
        profileToggle.setAttribute('aria-expanded', 'false');
    }

    document.querySelectorAll('[data-row-link]').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (!event.target.closest('a, button, input, select, textarea')) {
                window.location.href = row.dataset.rowLink;
            }
        });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                window.location.href = row.dataset.rowLink;
            }
        });
    });

    if (profileMenu && profileToggle && profileDropdown) {
        profileToggle.addEventListener('click', function () {
            var isOpen = profileDropdown.hidden;
            profileDropdown.hidden = !isOpen;
            profileToggle.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', function (event) {
            if (!profileMenu.contains(event.target)) {
                closeProfileMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeProfileMenu();
            }
        });
    }

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

    var accountMenu = document.querySelector('.nav-account');
    var accountToggle = document.querySelector('.nav-account-toggle');
    var accountPanel = document.querySelector('.nav-account-menu');

    function closeAccountMenu() {
        if (!accountMenu || !accountToggle || !accountPanel) {
            return;
        }

        accountMenu.classList.remove('is-open');
        accountToggle.setAttribute('aria-expanded', 'false');
        accountToggle.setAttribute('aria-label', 'Open account menu');
        accountPanel.hidden = true;
    }

    if (accountMenu && accountToggle && accountPanel) {
        accountToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = accountMenu.classList.toggle('is-open');
            accountToggle.setAttribute('aria-expanded', String(isOpen));
            accountToggle.setAttribute('aria-label', isOpen ? 'Close account menu' : 'Open account menu');
            accountPanel.hidden = !isOpen;
        });

        document.addEventListener('click', function (event) {
            if (!accountMenu.contains(event.target)) {
                closeAccountMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAccountMenu();
            }
        });
    }

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
