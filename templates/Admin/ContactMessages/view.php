<?php
/**
 * Admin contact message detail view — night-glow brand theme.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContactMessage $contactMessage
 */
$this->assign('title', 'Message: ' . $contactMessage->subject);
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 reveal">
        <div>
            <div class="section-eyebrow">Message</div>
            <h1 class="h2 mb-0">Message Details</h1>
        </div>
        <div class="d-flex gap-2">
            <?= $this->Html->link('Back to Messages', ['action' => 'index'], ['class' => 'btn btn-ghost-glow']) ?>
            <?= $this->Form->postLink(
                'Delete',
                ['action' => 'delete', $contactMessage->id],
                [
                    'confirm' => __('Are you sure you want to delete this message?'),
                    'class' => 'btn btn-outline-danger',
                ]
            ) ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4 reveal" data-reveal-step="1">
            <div class="glass-card p-4">
                <h2 class="h5 mb-3">Sender</h2>
                <div class="kv">
                    <span class="kv-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <span>
                        <span class="kv-label d-block">From</span>
                        <span class="kv-value"><?= h($contactMessage->name) ?></span>
                    </span>
                </div>
                <div class="kv">
                    <span class="kv-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                    </span>
                    <span>
                        <span class="kv-label d-block">Email</span>
                        <span class="kv-value"><a href="mailto:<?= h($contactMessage->email) ?>"><?= h($contactMessage->email) ?></a></span>
                    </span>
                </div>
                <div class="kv">
                    <span class="kv-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.13.96.36 1.9.7 2.8a2 2 0 0 1-.45 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.45c.9.34 1.84.57 2.8.7A2 2 0 0 1 22 16.9z"/></svg>
                    </span>
                    <span>
                        <span class="kv-label d-block">Phone</span>
                        <span class="kv-value"><?= $contactMessage->phone ? h($contactMessage->phone) : '<span class="text-muted">Not provided</span>' ?></span>
                    </span>
                </div>
                <div class="kv">
                    <span class="kv-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    </span>
                    <span>
                        <span class="kv-label d-block">Received</span>
                        <span class="kv-value"><?= h($contactMessage->created->format('d M Y, H:i')) ?></span>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-8 reveal" data-reveal-step="2">
            <div class="glass-card p-4 h-100 d-flex flex-column">
                <span class="pill pill-new align-self-start mb-3">Subject</span>
                <h2 class="h4"><?= h($contactMessage->subject) ?></h2>
                <div class="message-bubble mt-3 flex-grow-1">
                    <?= nl2br(h($contactMessage->message)) ?>
                </div>
                <div class="mt-4">
                    <a class="btn btn-glow" href="mailto:<?= h($contactMessage->email) ?>?subject=<?= rawurlencode('Re: ' . $contactMessage->subject) ?>">
                        Reply via Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
