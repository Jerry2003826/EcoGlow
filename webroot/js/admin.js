(function () {
    'use strict';

    var app = document.querySelector('.admin-app');
    var toggle = document.querySelector('[data-admin-nav-toggle]');
    var backdrop = document.querySelector('[data-admin-nav-backdrop]');
    var sidebar = document.getElementById('admin-sidebar');

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

    if (toggle) {
        toggle.addEventListener('click', function () {
            setNav(!app.classList.contains('is-nav-open'));
        });
        if (window.matchMedia('(max-width: 1199.98px)').matches) {
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

    document.querySelectorAll('[data-filter-select]').forEach(function (input) {
        var select = document.getElementById(input.getAttribute('data-filter-select'));
        if (!select) {
            return;
        }
        input.addEventListener('input', function () {
            filterSelect(select, input.value);
        });
    });

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

    var addLine = document.querySelector('[data-add-order-line]');
    var lineList = document.querySelector('[data-order-lines]');
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
})();
