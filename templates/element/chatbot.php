<?php
// templates/element/chatbot.php
?>
<div
    class="chatbot"
    data-chatbot
    data-chatbot-endpoint="<?= h($this->Url->build(['controller' => 'Chat', 'action' => 'ask'])) ?>"
    data-chatbot-reset-endpoint="<?= h($this->Url->build(['controller' => 'Chat', 'action' => 'reset'])) ?>"
>
    <button
        type="button"
        class="chatbot-toggle"
        data-chatbot-toggle
        aria-expanded="false"
        aria-controls="chatbot-panel"
        aria-label="Open chat assistant"
    >
        <svg
            class="chatbot-toggle-icon"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
            focusable="false"
        >
            <path
                d="M4 5C4 3.895 4.895 3 6 3H18C19.105 3 20 3.895 20 5V13C20 14.105 19.105 15 18 15H10L6 19V15H6C4.895 15 4 14.105 4 13V5Z"
                fill="currentColor"
            ></path>
        </svg>
        <span class="chatbot-toggle-label">Chat</span>
    </button>

    <section
        id="chatbot-panel"
        class="chatbot-panel"
        data-chatbot-panel
        hidden
        aria-label="Eco Glow chat assistant"
    >
        <div class="chatbot-header">
            <div class="chatbot-header-content">
                <h2 class="chatbot-title">Eco Glow Assistant</h2>
                <p class="chatbot-subtitle">
                    Ask about products, delivery, installation or how to use the site.
                </p>
            </div>

            <div class="chatbot-header-actions">
                <button
                    type="button"
                    class="chatbot-reset"
                    data-chatbot-reset
                    aria-label="Start a new conversation"
                >
                    New chat
                </button>

                <button
                    type="button"
                    class="chatbot-close"
                    data-chatbot-close
                    aria-label="Close chat"
                >
                    ×
                </button>
            </div>
        </div>

        <div
            class="chatbot-messages"
            data-chatbot-messages
            role="log"
            aria-live="polite"
        >
            <div class="chatbot-message chatbot-message-assistant">Hi! I'm the Eco Glow assistant. How can I help you today?</div>
        </div>

        <p
            class="chatbot-status"
            data-chatbot-status
            role="status"
            aria-live="polite"
            hidden
        ></p>

        <form class="chatbot-form" data-chatbot-form>
            <label
                class="chatbot-visually-hidden"
                for="chatbot-input"
            >
                Your message
            </label>

            <textarea
                id="chatbot-input"
                class="chatbot-input"
                data-chatbot-input
                rows="1"
                maxlength="500"
                placeholder="Type your question..."
                required
            ></textarea>

            <button
                type="submit"
                class="chatbot-send"
                data-chatbot-send
            >
                Send
            </button>
        </form>
    </section>
</div>
