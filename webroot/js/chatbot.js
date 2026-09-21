// webroot/js/chatbot.js
(function () {
    'use strict';

    var root = document.querySelector('[data-chatbot]');

    if (!root) {
        return;
    }

    var endpoint = root.getAttribute('data-chatbot-endpoint') || '';
    var resetEndpoint = root.getAttribute('data-chatbot-reset-endpoint') || '';

    var csrfMeta = document.querySelector('meta[name="csrfToken"]');
    var token = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var toggleButton = root.querySelector('[data-chatbot-toggle]');
    var panel = root.querySelector('[data-chatbot-panel]');
    var closeButton = root.querySelector('[data-chatbot-close]');
    var resetButton = root.querySelector('[data-chatbot-reset]');
    var messages = root.querySelector('[data-chatbot-messages]');
    var status = root.querySelector('[data-chatbot-status]');
    var form = root.querySelector('[data-chatbot-form]');
    var input = root.querySelector('[data-chatbot-input]');
    var sendButton = root.querySelector('[data-chatbot-send]');

    if (
        !toggleButton ||
        !panel ||
        !closeButton ||
        !resetButton ||
        !messages ||
        !status ||
        !form ||
        !input ||
        !sendButton
    ) {
        return;
    }

    function scrollMessages() {
        messages.scrollTop = messages.scrollHeight;
    }

    function updateTextareaHeight() {
        input.style.height = 'auto';

        if (input.scrollHeight === 0) {
            return;
        }

        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    function openPanel() {
        panel.hidden = false;
        toggleButton.setAttribute('aria-expanded', 'true');
        toggleButton.setAttribute('aria-label', 'Close chat assistant');
        updateTextareaHeight();
        input.focus();
        scrollMessages();
    }

    function closePanel() {
        panel.hidden = true;
        toggleButton.setAttribute('aria-expanded', 'false');
        toggleButton.setAttribute('aria-label', 'Open chat assistant');
        toggleButton.focus();
    }

    function setStatus(text) {
        status.textContent = text;
        status.hidden = text === '';
    }

    function appendMessage(role, text) {
        var node = document.createElement('div');

        if (role === 'user') {
            node.className = 'chatbot-message chatbot-message-user';
            node.textContent = text;
        } else if (role === 'error') {
            node.className = 'chatbot-message chatbot-message-error';
            node.textContent = text;
        } else {
            node.className = 'chatbot-message chatbot-message-assistant';

            var pattern = /(\/(?:products|cart|checkout|services|about|enquiries\/add|login|users\/add|forgot-password)(?:\?[A-Za-z0-9_=&%\-\.]+)?)/g;
            var parts = String(text).split(pattern);

            parts.forEach(function (part) {
                if (!part) {
                    return;
                }

                if (pattern.test(part)) {
                    var link = document.createElement('a');

                    link.href = part;
                    link.textContent = part;

                    node.appendChild(link);

                    return;
                }

                node.appendChild(document.createTextNode(part));
            });
        }

        messages.appendChild(node);
        scrollMessages();
    }

    function resetConversationUi() {
        messages.textContent = '';

        appendMessage(
            'assistant',
            'Hi! I\'m the Eco Glow assistant. How can I help you today?'
        );
    }

    toggleButton.addEventListener('click', function () {
        if (panel.hidden) {
            openPanel();
        } else {
            closePanel();
        }
    });

    closeButton.addEventListener('click', function () {
        closePanel();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            closePanel();
        }
    });

    input.addEventListener('input', function () {
        updateTextareaHeight();
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    resetButton.addEventListener('click', function () {
        fetch(resetEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        })
            .then(function () {
                resetConversationUi();
                input.focus();
            })
            .catch(function () {
                resetConversationUi();
            });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var text = input.value.trim();

        if (!text) {
            return;
        }

        appendMessage('user', text);

        input.value = '';
        updateTextareaHeight();

        sendButton.disabled = true;
        setStatus('Thinking...');

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                message: text
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data && typeof data.reply === 'string') {
                    appendMessage('assistant', data.reply);
                    return;
                }

                if (data && typeof data.error === 'string') {
                    appendMessage('error', data.error);
                    return;
                }

                appendMessage(
                    'error',
                    'Something went wrong. Please try again.'
                );
            })
            .catch(function () {
                appendMessage(
                    'error',
                    'Something went wrong. Please try again.'
                );
            })
            .finally(function () {
                sendButton.disabled = false;
                setStatus('');
                scrollMessages();
                input.focus();
            });
    });

    updateTextareaHeight();
})();
