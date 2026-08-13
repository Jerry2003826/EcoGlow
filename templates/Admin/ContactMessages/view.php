<?php
/**
 * Admin contact message detail with timeline and reply.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContactMessage $contactMessage
 * @var iterable<\App\Model\Entity\User> $staff
 * @var array<int, string> $nextStatuses
 */

use App\Model\Entity\ContactMessage;

$status = (string)($contactMessage->status ?: ContactMessage::STATUS_NEW);
$labels = ContactMessage::statusLabels();
$this->assign('title', 'Message: ' . $contactMessage->subject);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Messages', 'url' => ['action' => 'index']],
        ['label' => $contactMessage->subject],
    ],
]));
?>
<div class="admin-page-head d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
        <span class="eg-eyebrow">Message</span>
        <h1>Message Details</h1>
    </div>
    <?= $this->element('admin/status_pill', [
        'status' => $status,
        'label' => $labels[$status] ?? $status,
        'toneOverride' => ContactMessage::statusTone($status),
    ]) ?>
</div>

<div class="admin-detail">
    <div>
        <section class="admin-section" aria-labelledby="enquiry-heading">
            <h2 id="enquiry-heading">Enquiry</h2>
            <div class="admin-panel">
                <span class="eg-eyebrow">Subject</span>
                <p class="mb-3"><strong><?= h($contactMessage->subject) ?></strong></p>
                <div class="message-bubble">
                    <?= nl2br(h($contactMessage->message)) ?>
                </div>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="timeline-heading">
            <h2 id="timeline-heading">Timeline</h2>
            <div class="admin-panel">
                <?php if (empty($contactMessage->contact_message_events)) : ?>
                    <?= $this->element('admin/empty', [
                        'title' => 'No replies yet',
                        'body' => 'Replies you queue and status changes will collect here in order.',
                    ]) ?>
                <?php else : ?>
                    <ol class="admin-timeline">
                        <?php foreach ($contactMessage->contact_message_events as $event) : ?>
                            <li>
                                <strong><?= h(ucfirst($event->direction)) ?> · <?= h($event->channel) ?></strong>
                                <div class="small text-muted">
                                    <?= h($event->created->format('d M Y, H:i')) ?>
                                    <?php if ($event->user) : ?>
                                        · <?= h($event->user->email) ?>
                                    <?php endif; ?>
                                </div>
                                <div><?= nl2br(h($event->body)) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <div class="admin-reply">
                    <?= $this->Form->create(null, ['url' => ['action' => 'reply', $contactMessage->id]]) ?>
                    <div class="admin-field">
                        <?= $this->Form->control('body', [
                            'type' => 'textarea',
                            'label' => 'Reply to customer',
                            'class' => 'form-control',
                            'rows' => 5,
                            'required' => true,
                            'templates' => ['inputContainer' => '{{content}}'],
                        ]) ?>
                    </div>
                    <p class="admin-note mt-2">The reply is queued in outbound_messages. It is not sent immediately, so a failed delivery can be retried.</p>
                    <?= $this->Form->button('Queue reply', ['class' => 'btn btn-eg-primary mt-2']) ?>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </section>
    </div>

    <aside>
        <section class="admin-section" aria-labelledby="sender-heading">
            <h2 id="sender-heading">Sender</h2>
            <div class="admin-panel">
                <dl class="eg-kv-list">
                    <div class="eg-kv-row">
                        <dt>From</dt>
                        <dd><?= h($contactMessage->name) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Email</dt>
                        <dd><a href="mailto:<?= h($contactMessage->email) ?>"><?= h($contactMessage->email) ?></a></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Phone</dt>
                        <dd><?= $contactMessage->phone ? h($contactMessage->phone) : 'Not provided' ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Received</dt>
                        <dd><?= h($contactMessage->created->format('d M Y, H:i')) ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="assign-heading">
            <h2 id="assign-heading">Assign</h2>
            <div class="admin-panel">
                <?= $this->Form->create(null, ['url' => ['action' => 'assign', $contactMessage->id]]) ?>
                <div class="admin-field">
                    <label for="assigned-to">Assigned to</label>
                    <select name="assigned_to_user_id" id="assigned-to" class="form-select">
                        <option value="">Unassigned</option>
                        <?php foreach ($staff as $user) : ?>
                            <option value="<?= (int)$user->id ?>"<?= (int)$contactMessage->assigned_to_user_id === (int)$user->id ? ' selected' : '' ?>>
                                <?= h($user->email) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?= $this->Form->button('Save assignment', ['class' => 'btn btn-eg-ghost mt-2']) ?>
                <?= $this->Form->end() ?>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="advance-heading">
            <h2 id="advance-heading">Advance status</h2>
            <div class="admin-panel">
                <p class="admin-note mb-3">new → in progress → resolved / closed / spam. Reading a new message marks it in progress; that is not the same as resolving it.</p>
                <?php if ($nextStatuses === []) : ?>
                    <?= $this->element('admin/empty', [
                        'title' => 'No further status moves',
                        'body' => 'Closed and spam enquiries stay in that state.',
                    ]) ?>
                <?php else : ?>
                    <div class="admin-actions">
                        <?php foreach ($nextStatuses as $next) : ?>
                            <?= $this->Form->postButton(
                                $labels[$next] ?? $next,
                                ['action' => 'updateStatus', $contactMessage->id],
                                [
                                    'class' => $next === ContactMessage::STATUS_SPAM
                                        ? 'btn btn-eg-danger'
                                        : 'btn btn-eg-ghost',
                                    'data' => ['status' => $next],
                                ],
                            ) ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
