/**
 * Eco Glow Lighting — storefront interactions.
 *
 * - Staggered scroll-in reveal via IntersectionObserver
 * - Before/after comparison divider, draggable by pointer or arrow keys
 * - Header shadow on scroll
 * - Quantity steppers
 * - Basket line totals, removal and order summary
 * - Catalogue filtering, sorting and paging
 * - Product finish preview
 *
 * The cursor-following light spot and the one-shot "power on" intro that used
 * to live here were removed with the night-glow theme: both worked against the
 * client's brief for a calm, low-chroma storefront.
 *
 * The last four blocks exist because /shop, /shop/product and /cart are static
 * templates over placeholder arrays: with no products table there is nothing to
 * query, so filtering, paging and the basket arithmetic run over the rendered
 * DOM instead. That keeps the templates free of selection logic a controller
 * will later have to take back, and it keeps every control on those pages
 * honest — each one does what its label says. All of this is deleted when the
 * catalogue lands and CakePHP's Paginator does the work server-side.
 *
 * Everything degrades gracefully when prefers-reduced-motion is set.
 */
(function () {
    'use strict';

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /** Australian dollars, two decimals, thousands separated. */
    var money = function (amount) {
        return '$' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    var toArray = function (nodes) {
        return Array.prototype.slice.call(nodes);
    };

    /* ---------- Staggered scroll reveal ---------- */
    var revealItems = document.querySelectorAll('.reveal');
    if (revealItems.length > 0) {
        if (reducedMotion || !('IntersectionObserver' in window)) {
            revealItems.forEach(function (item) {
                item.classList.add('is-visible');
            });
        } else {
            revealItems.forEach(function (item, index) {
                var step = item.getAttribute('data-reveal-step');
                if (step !== null) {
                    item.style.setProperty('--reveal-delay', (parseFloat(step) * 0.1) + 's');
                } else {
                    item.style.setProperty('--reveal-delay', ((index % 4) * 0.1) + 's');
                }
            });

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });

            revealItems.forEach(function (item) {
                observer.observe(item);
            });
        }
    }

    /* ---------- Before / After compare slider ---------- */
    document.querySelectorAll('[data-compare]').forEach(function (band) {
        var handle = band.querySelector('[role="slider"]');
        var split = handle ? parseFloat(handle.getAttribute('aria-valuenow')) : 50;
        if (isNaN(split)) {
            split = 50;
        }

        // Stops short of the edges so the round grab handle stays on screen.
        var applySplit = function (pct) {
            split = Math.max(2, Math.min(98, pct));
            band.style.setProperty('--split', split + '%');
            if (handle) {
                handle.setAttribute('aria-valuenow', String(Math.round(split)));
            }
        };

        var setSplitFromPointer = function (clientX) {
            var rect = band.getBoundingClientRect();
            applySplit(((clientX - rect.left) / rect.width) * 100);
        };

        var dragging = false;

        band.addEventListener('pointerdown', function (event) {
            dragging = true;
            band.setPointerCapture(event.pointerId);
            setSplitFromPointer(event.clientX);
        });

        band.addEventListener('pointermove', function (event) {
            if (dragging) {
                setSplitFromPointer(event.clientX);
            }
        });

        band.addEventListener('pointerup', function () {
            dragging = false;
        });

        band.addEventListener('pointercancel', function () {
            dragging = false;
        });

        if (handle) {
            handle.addEventListener('keydown', function (event) {
                var next;

                switch (event.key) {
                    case 'ArrowLeft':
                    case 'ArrowDown':
                        next = split - (event.shiftKey ? 10 : 2);
                        break;
                    case 'ArrowRight':
                    case 'ArrowUp':
                        next = split + (event.shiftKey ? 10 : 2);
                        break;
                    case 'PageDown':
                        next = split - 10;
                        break;
                    case 'PageUp':
                        next = split + 10;
                        break;
                    case 'Home':
                        next = 0;
                        break;
                    case 'End':
                        next = 100;
                        break;
                    default:
                        return;
                }

                event.preventDefault();
                applySplit(next);
            });
        }

        applySplit(split);
    });

    /* ---------- Header shadow on scroll ---------- */
    var nav = document.querySelector('.navbar-eg');
    if (nav) {
        var onScroll = function () {
            nav.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* ---------- Quantity steppers ----------
       The number input is the control; the two buttons are an enhancement on
       top of it, so with scripting off the field still types and validates. */
    document.querySelectorAll('[data-qty]').forEach(function (widget) {
        var input = widget.querySelector('[data-qty-input]');
        if (!input) {
            return;
        }

        var floor = parseInt(input.getAttribute('min'), 10) || 1;
        var ceiling = parseInt(input.getAttribute('max'), 10) || 99;
        var minus = widget.querySelector('[data-qty-step="-1"]');

        // Clamps the field and greys the minus button out at the floor. Never
        // fires an event of its own — the callers below decide when to announce
        // a change, which is what keeps this out of a feedback loop with the
        // basket's own `change` listener.
        var sync = function (value) {
            var next = parseInt(value, 10);
            if (isNaN(next)) {
                next = floor;
            }
            next = Math.min(ceiling, Math.max(floor, next));
            input.value = String(next);
            if (minus) {
                minus.disabled = next <= floor;
            }
        };

        widget.querySelectorAll('[data-qty-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                var step = parseInt(button.getAttribute('data-qty-step'), 10);
                sync(parseInt(input.value, 10) + step);
                widget.dispatchEvent(new CustomEvent('eg:qty', { bubbles: true }));
            });
        });

        input.addEventListener('change', function () {
            sync(input.value);
        });

        sync(input.value);
    });

    /* ---------- Basket ---------- */
    var cart = document.querySelector('[data-cart]');
    if (cart) {
        var freeFrom = parseFloat(cart.getAttribute('data-free-from')) || 0;
        var deliveryFlat = parseFloat(cart.getAttribute('data-delivery')) || 0;

        var setText = function (selector, text) {
            var node = cart.querySelector(selector);
            if (node) {
                node.textContent = text;
            }
        };

        // Mirrors the arithmetic the template does in PHP, including the
        // Australian convention that GST is already inside every price rather
        // than added at the end: total = subtotal + delivery, GST = total / 11.
        var recalculate = function () {
            var lines = toArray(cart.querySelectorAll('[data-cart-line]'));
            var subtotal = 0;

            lines.forEach(function (line) {
                var price = parseFloat(line.getAttribute('data-price')) || 0;
                var field = line.querySelector('[data-qty-input]');
                var quantity = field ? parseInt(field.value, 10) || 0 : 0;
                var lineTotal = price * quantity;
                subtotal += lineTotal;

                var lineOut = line.querySelector('[data-cart-line-total]');
                if (lineOut) {
                    lineOut.textContent = money(lineTotal);
                }
            });

            var isEmpty = lines.length === 0;
            var delivery = (isEmpty || subtotal >= freeFrom) ? 0 : deliveryFlat;
            var total = subtotal + delivery;

            setText('[data-cart-subtotal]', money(subtotal));
            setText('[data-cart-delivery]', delivery > 0 ? money(delivery) : 'Free');
            setText('[data-cart-grand]', money(total));
            setText('[data-cart-gst]', 'Includes GST of ' + money(total / 11) + '.');

            var hint = cart.querySelector('[data-cart-hint]');
            if (hint) {
                var shortfall = freeFrom - subtotal;
                hint.hidden = isEmpty || shortfall <= 0;
                var shortfallOut = hint.querySelector('[data-cart-away]');
                if (shortfallOut) {
                    shortfallOut.textContent = Math.max(0, shortfall).toFixed(2);
                }
            }

            var list = cart.querySelector('[data-cart-list]');
            if (list) {
                list.hidden = isEmpty;
            }
            var emptyPanel = cart.querySelector('[data-cart-empty]');
            if (emptyPanel) {
                emptyPanel.hidden = !isEmpty;
            }
        };

        cart.addEventListener('eg:qty', recalculate);

        cart.addEventListener('change', function (event) {
            if (event.target.matches('[data-qty-input]')) {
                recalculate();
            }
        });

        cart.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-cart-remove]');
            if (!trigger) {
                return;
            }

            var line = trigger.closest('[data-cart-line]');
            if (!line) {
                return;
            }

            // Removing the line destroys the button that had focus, so hand it
            // on deliberately rather than letting it fall back to the document.
            var next = line.nextElementSibling || line.previousElementSibling;
            var landing = next ? next.querySelector('[data-cart-remove]') : null;

            line.remove();
            recalculate();
            (landing || cart.querySelector('.eg-cart-continue') || document.body).focus();
        });

        recalculate();
    }

    /* ---------- Catalogue filter, sort and paging ---------- */
    var shop = document.querySelector('[data-shop]');
    if (shop) {
        var perPage = 8;
        var grid = shop.querySelector('[data-shop-grid]');
        var pager = shop.querySelector('[data-shop-pages]');
        var cards = toArray(shop.querySelectorAll('[data-product]'));
        var facets = ['category', 'style'];
        var state = { category: '', style: '', sort: 'featured', page: 1 };

        // The filter panel is a <details>: it opens itself, and the only thing
        // touched here is the initial state, once, below.
        var disclosure = shop.querySelector('[data-shop-filters]');
        var tally = shop.querySelector('[data-shop-tally]');
        var appliedBar = shop.querySelector('[data-shop-active]');
        var tagList = shop.querySelector('[data-shop-tags]');

        var facetOf = function (card, facet) {
            return card.getAttribute('data-' + facet) || '';
        };

        var priceOf = function (card) {
            return parseFloat(card.getAttribute('data-price')) || 0;
        };

        var byState = function (card) {
            return (state.category === '' || facetOf(card, 'category') === state.category)
                && (state.style === '' || facetOf(card, 'style') === state.style);
        };

        var inSortOrder = function (a, b) {
            if (state.sort === 'price-asc') {
                return priceOf(a) - priceOf(b);
            }
            if (state.sort === 'price-desc') {
                return priceOf(b) - priceOf(a);
            }
            if (state.sort === 'name-asc') {
                return facetOf(a, 'name').localeCompare(facetOf(b, 'name'));
            }

            return parseInt(a.getAttribute('data-order'), 10) - parseInt(b.getAttribute('data-order'), 10);
        };

        // One way in for every filter change — a chip, a tag's remove button,
        // clear-all, or a value read out of the address bar — so the chips can
        // never disagree with the state they are meant to be showing.
        var applyFacet = function (facet, value) {
            state[facet] = value;
            state.page = 1;

            shop.querySelectorAll('[data-shop-filter="' + facet + '"]').forEach(function (chip) {
                var isOn = chip.getAttribute('data-value') === value;
                chip.classList.toggle('is-active', isOn);
                chip.setAttribute('aria-pressed', isOn ? 'true' : 'false');
            });
        };

        var appliedFacets = function () {
            return facets.filter(function (facet) {
                return state[facet] !== '';
            });
        };

        // Rebuilt whole on every render. Two tags at most, so there is nothing
        // to win by diffing, and it keeps this the only place a tag is made.
        var renderTags = function () {
            var applied = appliedFacets();

            if (tally) {
                tally.textContent = '(' + applied.length + ')';
                tally.hidden = applied.length === 0;
            }
            if (appliedBar) {
                appliedBar.hidden = applied.length === 0;
            }
            if (!tagList) {
                return;
            }

            tagList.textContent = '';
            applied.forEach(function (facet) {
                var value = state[facet];

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'eg-tag-remove';
                remove.setAttribute('data-shop-remove', facet);
                // The × is decorative; the name has to say which filter goes.
                remove.setAttribute('aria-label', 'Remove filter: ' + value);

                var cross = document.createElement('span');
                cross.setAttribute('aria-hidden', 'true');
                cross.textContent = '\u00D7';
                remove.appendChild(cross);

                var tag = document.createElement('li');
                tag.className = 'eg-tag';
                tag.appendChild(document.createTextNode(value));
                tag.appendChild(remove);
                tagList.appendChild(tag);
            });
        };

        // `name` is the accessible name and has to contain the visible label,
        // which is what WCAG 2.5.3 (Label in Name) asks for so that a speech-input
        // user can say what they can see. Deriving it from the page number instead
        // gave the Previous and Next buttons the names "Go to page 1" and
        // "Go to page 3" — neither contains the word on the button, and both
        // collided with the numbered button that already carried that name.
        var addPageButton = function (label, page, isDisabled, isCurrent, name) {
            var item = document.createElement('li');
            item.className = 'page-item' + (isDisabled ? ' disabled' : '') + (isCurrent ? ' active' : '');

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'page-link';
            button.textContent = label;
            button.disabled = isDisabled;
            button.setAttribute('aria-label', name);
            if (isCurrent) {
                button.setAttribute('aria-current', 'page');
            }
            button.addEventListener('click', function () {
                state.page = page;
                render(true);
            });

            item.appendChild(button);
            pager.appendChild(item);
        };

        var render = function (returnFocusToPager) {
            var matching = cards.filter(byState).sort(inSortOrder);
            var pageCount = Math.max(1, Math.ceil(matching.length / perPage));
            state.page = Math.min(state.page, pageCount);

            var start = (state.page - 1) * perPage;
            var onThisPage = matching.slice(start, start + perPage);

            cards.forEach(function (card) {
                card.hidden = true;
            });
            onThisPage.forEach(function (card) {
                card.hidden = false;
                // Re-append so the DOM order matches the chosen sort; hidden
                // cards drift to the front of the grid, where they cost nothing.
                grid.appendChild(card);
            });

            var shownOut = shop.querySelector('[data-shop-shown]');
            if (shownOut) {
                shownOut.textContent = String(onThisPage.length);
            }
            var totalOut = shop.querySelector('[data-shop-total]');
            if (totalOut) {
                totalOut.textContent = String(matching.length);
            }

            var emptyNote = shop.querySelector('[data-shop-empty]');
            if (emptyNote) {
                emptyNote.hidden = matching.length > 0;
            }

            renderTags();

            pager.textContent = '';
            if (pageCount > 1) {
                addPageButton('Previous', state.page - 1, state.page === 1, false, 'Previous page');
                for (var page = 1; page <= pageCount; page++) {
                    addPageButton(String(page), page, false, page === state.page, 'Page ' + page);
                }
                addPageButton('Next', state.page + 1, state.page === pageCount, false, 'Next page');
            }

            if (returnFocusToPager) {
                var current = pager.querySelector('.page-item.active .page-link');
                if (current) {
                    current.focus();
                }
            }
        };

        // Where focus lands once a control has removed itself from the page.
        var handOffFocus = function () {
            var nextTag = tagList ? tagList.querySelector('[data-shop-remove]') : null;
            var trigger = disclosure ? disclosure.querySelector('summary') : null;
            (nextTag || trigger || document.body).focus();
        };

        shop.querySelectorAll('[data-shop-filter]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                applyFacet(chip.getAttribute('data-shop-filter'), chip.getAttribute('data-value'));
                render(false);
            });
        });

        // Delegated, because renderTags replaces these buttons on every pass.
        if (tagList) {
            tagList.addEventListener('click', function (event) {
                var trigger = event.target.closest('[data-shop-remove]');
                if (!trigger) {
                    return;
                }

                applyFacet(trigger.getAttribute('data-shop-remove'), '');
                render(false);
                handOffFocus();
            });
        }

        var clearAll = shop.querySelector('[data-shop-clear]');
        if (clearAll) {
            clearAll.addEventListener('click', function () {
                facets.forEach(function (facet) {
                    applyFacet(facet, '');
                });
                render(false);
                // This button is inside the row that has just been hidden.
                handOffFocus();
            });
        }

        var sortControl = shop.querySelector('[data-shop-sort]');
        if (sortControl) {
            sortControl.addEventListener('change', function () {
                state.sort = sortControl.value;
                state.page = 1;
                render(false);
            });
        }

        // A filter can also arrive in the address bar — /shop?category=Smart+Bulbs
        // — which is how a link from another page hands one over. A value that
        // matches no product is ignored rather than trusted.
        var query = new URLSearchParams(window.location.search);
        facets.forEach(function (facet) {
            var wanted = query.get(facet);
            var isKnown = wanted !== null && cards.some(function (card) {
                return facetOf(card, facet) === wanted;
            });

            if (isKnown) {
                applyFacet(facet, wanted);
            }
        });

        // Arriving with a filter already applied and its panel shut reads as a
        // catalogue with products missing, so open the panel for that case.
        if (disclosure && appliedFacets().length > 0) {
            disclosure.open = true;
        }

        render(false);
    }

    /* ---------- Product finish readout ----------
       Writes the chosen colourway into the picker's own legend. This used to
       tint the image well towards the selected hex with color-mix(), which was
       worth having while the well held a line drawing on a flat greige ground;
       over the product photograph that arrived with it, the same wash only made
       the photograph look like a bad print. A word is legible, survives
       greyscale, and stays true whatever the image is.

       The radios work on their own — with scripting off the readout simply keeps
       naming the finish that is checked by default. */
    document.querySelectorAll('[data-product-detail]').forEach(function (root) {
        var readout = root.querySelector('[data-finish-name]');
        if (!readout) {
            return;
        }

        var applyFinish = function () {
            var chosen = root.querySelector('input[name="finish"]:checked');
            if (chosen) {
                readout.textContent = chosen.value;
            }
        };

        root.addEventListener('change', function (event) {
            if (event.target.name === 'finish') {
                applyFinish();
            }
        });

        applyFinish();
    });
})();
