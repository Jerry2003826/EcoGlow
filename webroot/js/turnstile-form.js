(function () {
    'use strict';

    var widget = document.querySelector('.cf-turnstile');
    var message = document.getElementById('turnstile-message');
    var form = widget && widget.closest('form');
    var submitButton = form && form.querySelector('button[type="submit"]');

    if (!widget || !message || !submitButton) {
        return;
    }

    // Flexible widgets still require 300px; compact fits narrow phone forms.
    // This script runs before the API script performs its implicit rendering.
    widget.dataset.size = widget.getBoundingClientRect().width < 300 ? 'compact' : 'flexible';

    function requireVerification(text) {
        submitButton.disabled = true;
        message.textContent = text;
        message.hidden = false;
    }

    window.turnstileOnSuccess = function () {
        message.hidden = true;
        message.textContent = '';
        submitButton.disabled = false;
    };

    window.turnstileOnError = function () {
        requireVerification('Verification could not load. Please refresh the page and try again.');
    };

    window.turnstileOnExpired = function () {
        requireVerification('Verification expired. Please complete the challenge again.');
    };

    window.turnstileOnTimeout = function () {
        requireVerification('Verification timed out. Please complete the challenge again.');
    };
})();
