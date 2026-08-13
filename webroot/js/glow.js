/**
 * Eco Glow Lighting — night-glow interactions.
 *
 * - Cursor glow spot that follows the pointer
 * - One-time "power on" intro per session
 * - Staggered scroll-in reveal via IntersectionObserver
 * - Before/after comparison divider, draggable by pointer or arrow keys
 * - Navbar deepening on scroll
 *
 * Everything degrades gracefully when prefers-reduced-motion is set.
 */
(function () {
    'use strict';

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Power-on intro (once per session) ---------- */
    var powerOn = document.getElementById('power-on');
    if (powerOn) {
        if (reducedMotion || window.sessionStorage.getItem('eg-power-on') === 'done') {
            powerOn.parentNode.removeChild(powerOn);
        } else {
            window.sessionStorage.setItem('eg-power-on', 'done');
            powerOn.addEventListener('animationend', function (event) {
                if (event.animationName === 'power-on-fade' && powerOn.parentNode) {
                    powerOn.parentNode.removeChild(powerOn);
                }
            });
        }
    }

    /* ---------- Cursor glow spot ---------- */
    var spot = document.getElementById('glow-spot');
    if (spot && !reducedMotion && window.matchMedia('(pointer: fine)').matches) {
        var targetX = window.innerWidth / 2;
        var targetY = window.innerHeight / 2;
        var currentX = targetX;
        var currentY = targetY;
        var rafId = null;

        var render = function () {
            // Ease towards the pointer for a soft, lantern-like trail.
            currentX += (targetX - currentX) * 0.08;
            currentY += (targetY - currentY) * 0.08;
            spot.style.transform = 'translate(' + currentX + 'px, ' + currentY + 'px)';
            rafId = window.requestAnimationFrame(render);
        };

        window.addEventListener('pointermove', function (event) {
            targetX = event.clientX;
            targetY = event.clientY;
            document.body.classList.add('glow-active');
            if (rafId === null) {
                rafId = window.requestAnimationFrame(render);
            }
        }, { passive: true });

        document.addEventListener('pointerleave', function () {
            document.body.classList.remove('glow-active');
        });
    }

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
                    item.style.setProperty('--reveal-delay', (parseFloat(step) * 0.12) + 's');
                } else {
                    item.style.setProperty('--reveal-delay', ((index % 4) * 0.12) + 's');
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

    /* ---------- Navbar deepening on scroll ---------- */
    var nav = document.querySelector('.navbar-eg');
    if (nav) {
        var onScroll = function () {
            nav.classList.toggle('is-scrolled', window.scrollY > 24);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }
})();
