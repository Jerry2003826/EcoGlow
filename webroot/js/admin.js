(function () {
    'use strict';

    var app = document.querySelector('.admin-app');
    var backdrop = document.querySelector('[data-admin-nav-backdrop]');
    var sidebar = document.getElementById('admin-sidebar');
    var navigating = false;

    function isModifiedClick(event) {
        return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
    }

    function isAdminUrl(href) {
        try {
            var url = new URL(href, window.location.href);
            return url.origin === window.location.origin
                && (url.pathname === '/admin' || url.pathname.indexOf('/admin/') === 0);
        } catch (ignore) {
            return false;
        }
    }

    function currentToggle() {
        return document.querySelector('[data-admin-nav-toggle]');
    }

    function setNav(open) {
        if (!app) {
            return;
        }
        var toggle = currentToggle();
        app.classList.toggle('is-nav-open', open);
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        }
        if (backdrop) {
            backdrop.hidden = !open;
        }
        if (sidebar) {
            var mobile = window.matchMedia('(max-width: 1199.98px)').matches;
            if (mobile && !open) {
                sidebar.setAttribute('aria-hidden', 'true');
                sidebar.setAttribute('inert', '');
            } else {
                sidebar.removeAttribute('aria-hidden');
                sidebar.removeAttribute('inert');
            }
        }
    }

    function syncSidebar(doc) {
        if (!sidebar) {
            return;
        }
        var nextByHref = {};
        doc.querySelectorAll('#admin-sidebar a.admin-nav-link').forEach(function (link) {
            nextByHref[link.getAttribute('href') || ''] = link;
        });
        sidebar.querySelectorAll('a.admin-nav-link').forEach(function (link) {
            var next = nextByHref[link.getAttribute('href') || ''];
            if (!next) {
                return;
            }
            link.className = next.className;
            if (next.getAttribute('aria-current')) {
                link.setAttribute('aria-current', next.getAttribute('aria-current'));
            } else {
                link.removeAttribute('aria-current');
            }
            var nextBadge = next.querySelector('.badge-count');
            var curBadge = link.querySelector('.badge-count');
            if (nextBadge && curBadge) {
                curBadge.textContent = nextBadge.textContent;
            } else if (nextBadge && !curBadge) {
                link.insertAdjacentHTML('beforeend', nextBadge.outerHTML);
            } else if (!nextBadge && curBadge) {
                curBadge.remove();
            }
        });
    }

    function navigateAdmin(href, push) {
        if (navigating) {
            return;
        }
        navigating = true;
        fetch(href, {
            credentials: 'same-origin',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (response) {
            if (response.redirected && !isAdminUrl(response.url)) {
                window.location.href = response.url;
                return null;
            }
            return response.text().then(function (html) {
                return { html: html, url: response.url };
            });
        }).then(function (payload) {
            if (!payload) {
                return;
            }
            var doc = new DOMParser().parseFromString(payload.html, 'text/html');
            var nextStage = doc.querySelector('.admin-stage');
            var curStage = document.querySelector('.admin-stage');
            if (!doc.querySelector('.admin-app') || !nextStage || !curStage) {
                window.location.href = href;
                return;
            }
            curStage.replaceWith(nextStage);
            document.title = doc.title;
            syncSidebar(doc);
            bindStage(nextStage);
            setNav(false);
            window.scrollTo(0, 0);
            if (push) {
                history.pushState({ adminShell: true }, '', payload.url || href);
            }
            var main = document.getElementById('main-content');
            if (main && typeof main.focus === 'function') {
                main.focus({ preventScroll: true });
            }
        }).catch(function () {
            window.location.href = href;
        }).then(function () {
            navigating = false;
        });
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-admin-nav-toggle]');
        if (toggle) {
            setNav(!app.classList.contains('is-nav-open'));
            return;
        }
        if (event.target.closest('[data-admin-nav-backdrop]')) {
            setNav(false);
            return;
        }
        var link = event.target.closest('a[href]');
        if (!link || isModifiedClick(event) || link.getAttribute('download') !== null) {
            return;
        }
        var rawHref = link.getAttribute('href') || '';
        if (rawHref.charAt(0) === '#') {
            return;
        }
        var target = link.getAttribute('target');
        if (target && target !== '_self') {
            return;
        }
        if (!isAdminUrl(link.href)) {
            return;
        }
        try {
            var next = new URL(link.href, window.location.href);
            if (next.hash && next.pathname === window.location.pathname && next.search === window.location.search) {
                return;
            }
        } catch (ignore) {
            return;
        }
        event.preventDefault();
        if (sidebar && sidebar.contains(link) && link.getAttribute('aria-current') === 'page') {
            setNav(false);
            return;
        }
        navigateAdmin(link.href, true);
    });

    if (sidebar) {
        sidebar.addEventListener('mousedown', function (event) {
            var link = event.target.closest('a[href]');
            if (!link || !sidebar.contains(link) || event.button !== 0 || !isAdminUrl(link.href)) {
                return;
            }
            event.preventDefault();
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setNav(false);
        }
    });

    if (window.matchMedia('(max-width: 1199.98px)').matches) {
        setNav(false);
    }

    history.replaceState({ adminShell: true }, '', window.location.href);
    window.addEventListener('popstate', function () {
        if (isAdminUrl(window.location.href)) {
            navigateAdmin(window.location.href, false);
        }
    });

    function filterSelect(select, query) {
        var needle = (query || '').toLowerCase();
        Array.prototype.forEach.call(select.options, function (option, index) {
            if (index === 0 && option.value === '') {
                option.hidden = false;
                return;
            }
            option.hidden = needle !== '' && option.text.toLowerCase().indexOf(needle) === -1;
        });
    }

    function bindLine(row) {
        var select = row.querySelector('[data-variant-select]');
        var qty = row.querySelector('[data-line-qty]');
        var warn = row.querySelector('[data-stock-warning]');
        var search = row.querySelector('[data-line-search]');
        if (!select || !qty || !warn) {
            return;
        }
        function check() {
            var option = select.options[select.selectedIndex];
            var available = option ? parseInt(option.getAttribute('data-available') || '0', 10) : 0;
            var wanted = parseInt(qty.value || '0', 10);
            if (select.value && wanted > available) {
                warn.hidden = false;
                warn.textContent = 'Only ' + available + ' available — the order can still be saved as a shortfall.';
            } else {
                warn.hidden = true;
                warn.textContent = '';
            }
        }
        if (search) {
            search.addEventListener('input', function () {
                filterSelect(select, search.value);
            });
        }
        select.addEventListener('change', check);
        qty.addEventListener('input', check);
        check();
    }

    function bindStage(root) {
        if (!root) {
            return;
        }
        root.querySelectorAll('[data-filter-select]').forEach(function (input) {
            var select = document.getElementById(input.getAttribute('data-filter-select'));
            if (!select) {
                return;
            }
            input.addEventListener('input', function () {
                filterSelect(select, input.value);
            });
        });

        var addLine = root.querySelector('[data-add-order-line]');
        var lineList = root.querySelector('[data-order-lines]');
        if (addLine && lineList) {
            addLine.addEventListener('click', function () {
                var rows = lineList.querySelectorAll('[data-order-line]');
                var template = rows[0];
                if (!template) {
                    return;
                }
                var index = rows.length;
                var clone = template.cloneNode(true);
                clone.querySelectorAll('[id]').forEach(function (el) {
                    el.id = el.id.replace(/-\d+$/, '-' + index);
                });
                clone.querySelectorAll('label[for]').forEach(function (el) {
                    el.setAttribute('for', el.getAttribute('for').replace(/-\d+$/, '-' + index));
                });
                clone.querySelectorAll('[name]').forEach(function (el) {
                    el.name = el.name.replace(/lines\[\d+\]/, 'lines[' + index + ']');
                });
                clone.querySelectorAll('select, input').forEach(function (field) {
                    if (field.name && field.name.indexOf('[quantity]') !== -1) {
                        field.value = '1';
                    } else if (field.tagName === 'SELECT') {
                        field.selectedIndex = 0;
                    } else if (field.getAttribute('data-line-search') !== null) {
                        field.value = '';
                    }
                });
                var warn = clone.querySelector('[data-stock-warning]');
                if (warn) {
                    warn.hidden = true;
                    warn.textContent = '';
                }
                Array.prototype.forEach.call(clone.querySelectorAll('option'), function (option) {
                    option.hidden = false;
                });
                lineList.appendChild(clone);
                bindLine(clone);
            });
            lineList.querySelectorAll('[data-order-line]').forEach(bindLine);
        }

        root.querySelectorAll('[data-admin-fold]').forEach(function (panel) {
            var toggle = panel.querySelector('[data-admin-fold-toggle]');
            var body = panel.querySelector('[data-admin-fold-body]');
            var search = panel.querySelector('[data-admin-fold-search]');
            var empty = panel.querySelector('[data-admin-fold-empty]');
            var storageKey = toggle ? 'admin-fold:' + toggle.getAttribute('aria-controls') : '';

            function setOpen(open) {
                if (!toggle || !body) {
                    return;
                }
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                body.hidden = !open;
                if (!storageKey) {
                    return;
                }
                try {
                    window.localStorage.setItem(storageKey, open ? '1' : '0');
                } catch (ignore) {
                }
            }

            if (toggle && body) {
                var stored = null;
                try {
                    stored = window.localStorage.getItem(storageKey);
                } catch (ignore) {
                }
                if (stored === '0') {
                    setOpen(false);
                }
                toggle.addEventListener('click', function () {
                    setOpen(toggle.getAttribute('aria-expanded') !== 'true');
                });
            }

            if (!search) {
                return;
            }
            search.addEventListener('input', function () {
                var needle = search.value.trim().toLowerCase();
                if (body && body.hidden) {
                    setOpen(true);
                }
                var rows = panel.querySelectorAll('[data-admin-fold-row]');
                var shown = 0;
                Array.prototype.forEach.call(rows, function (row) {
                var hay = (row.getAttribute('data-search') || row.textContent || '').toLowerCase();
                var match = needle === '' || hay.indexOf(needle) !== -1;
                row.hidden = !match;
                    if (match) {
                        shown += 1;
                    }
                });
                if (empty) {
                    empty.hidden = rows.length === 0 || shown !== 0;
                }
            });
        });
    }

    bindStage(document.querySelector('.admin-stage') || document);
})();
