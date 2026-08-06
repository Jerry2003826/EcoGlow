/**
 * Eco Glow Lighting — night-glow interactions.
 *
 * - Cursor glow spot that follows the pointer
 * - One-time "power on" intro per session
 * - Staggered scroll-in reveal via IntersectionObserver
 * - Draggable before/after comparison divider
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
        var setSplit = function (clientX) {
            var rect = band.getBoundingClientRect();
            var pct = ((clientX - rect.left) / rect.width) * 100;
            pct = Math.max(2, Math.min(98, pct));
            band.style.setProperty('--split', pct + '%');
        };

        var dragging = false;

        band.addEventListener('pointerdown', function (event) {
            dragging = true;
            band.setPointerCapture(event.pointerId);
            setSplit(event.clientX);
        });

        band.addEventListener('pointermove', function (event) {
            if (dragging) {
                setSplit(event.clientX);
            }
        });

        band.addEventListener('pointerup', function () {
            dragging = false;
        });

        band.addEventListener('pointercancel', function () {
            dragging = false;
        });
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
