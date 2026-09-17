(function () {
    'use strict';

    var navigation = document.querySelector('.navbar');
    var menuButton = document.querySelector('.navbar-toggle');
    var navigationLinks = document.querySelector('.navbar-links');

    function closeMenu() {
        if (!navigation || !menuButton || !navigationLinks) {
            return;
        }

        navigation.classList.remove('menu-is-open');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.setAttribute('aria-label', 'Open navigation');
    }

    if (navigation && menuButton && navigationLinks) {
        menuButton.addEventListener('click', function () {
            var isOpen = navigation.classList.toggle('menu-is-open');
            menuButton.setAttribute('aria-expanded', String(isOpen));
            menuButton.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
        });

        navigationLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMenu);
        });

        document.addEventListener('click', function (event) {
            if (!navigation.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
                closeAccountMenu();
            }
        });
    }

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
    }

    if (navigation) {
        window.addEventListener('scroll', function () {
            navigation.classList.toggle('is-scrolled', window.scrollY > 12);
        }, { passive: true });
    }

    var carousel = document.querySelector('[data-carousel]');

    if (!carousel) {
        return;
    }

    var slides = Array.from(carousel.querySelectorAll('[data-slide]'));
    var dots = Array.from(carousel.querySelectorAll('[data-carousel-dot]'));
    var position = carousel.querySelector('[data-carousel-position]');
    var currentSlide = 0;
    var autoplay;

    function showSlide(nextSlide) {
        currentSlide = (nextSlide + slides.length) % slides.length;

        slides.forEach(function (slide, index) {
            var isActive = index === currentSlide;
            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', String(!isActive));
        });

        dots.forEach(function (dot, index) {
            var isActive = index === currentSlide;
            dot.classList.toggle('is-active', isActive);
            dot.setAttribute('aria-selected', String(isActive));
        });

        if (position) {
            position.textContent = String(currentSlide + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
        }
    }

    function startAutoplay() {
        window.clearInterval(autoplay);
        autoplay = window.setInterval(function () {
            showSlide(currentSlide + 1);
        }, 3500);
    }

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            showSlide(Number(dot.dataset.carouselDot));
            startAutoplay();
        });
    });

    carousel.addEventListener('mouseenter', function () {
        window.clearInterval(autoplay);
    });
    carousel.addEventListener('mouseleave', startAutoplay);
    startAutoplay();
})();