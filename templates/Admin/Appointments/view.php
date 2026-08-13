<?php
/**
 * Staff scheduling, work logs and parts for one service request.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ServiceRequest $request
 * @var iterable<\App\Model\Entity\User> $staff
 * @var iterable<\App\Model\Entity\ProductVariant> $variants
 */

use App\Model\Entity\ServiceRequest;

$labels = ServiceRequest::statusLabels();
$appointment = $request->service_appointments[0] ?? null;
$this->assign('title', $request->request_number);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Appointments', 'url' => ['action' => 'index']],
        ['label' => $request->request_number],
    ],
]));
?>
<div class="admin-page-head d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
        <span class="eg-eyebrow">Service request</span>
        <h1><?= h($request->request_number) ?></h1>
    </div>
    <?= $this->element('admin/status_pill', ['status' => $request->status]) ?>
</div>

<div class="admin-detail">
    <div>
        <section class="admin-section" aria-labelledby="request-heading">
            <h2 id="request-heading">Request</h2>
            <div class="admin-panel">
                <p class="mb-2"><strong><?= h($request->service_type->name ?? '') ?></strong></p>
                <p><?= nl2br(h($request->issue_description)) ?></p>
                <p class="mb-0">
                    <?= h($request->contact_name) ?><br>
                    <?= h($request->address_line1) ?><br>
                    <?= h($request->suburb) ?> <?= h($request->state) ?> <?= h($request->postcode) ?>
                </p>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="schedule-heading">
            <h2 id="schedule-heading">Schedule</h2>
            <div class="admin-panel">
                <?php if ($appointment) : ?>
                    <p>
                        <?= h($appointment->staff->first_name ?? '') ?>
                        <?= h($appointment->staff->last_name ?? '') ?>
                        ·
                        <?= h($appointment->starts_at?->setTimezone('Australia/Melbourne')->format('d M Y, H:i')) ?>
                        –
                        <?= h($appointment->ends_at?->setTimezone('Australia/Melbourne')->format('H:i')) ?>
                    </p>
                <?php else : ?>
                    <p class="admin-note">No visit scheduled yet. Lock a technician before confirming.</p>
                <?php endif; ?>
                <?= $this->Form->create(null, ['url' => ['action' => 'schedule', $request->id]]) ?>
                <div class="admin-filter-row">
                    <div class="admin-field">
                        <label for="assigned-staff-user-id">Technician</label>
                        <select class="form-select" name="assigned_staff_user_id" id="assigned-staff-user-id" required>
                            <option value="">Select</option>
                            <?php foreach ($staff as $user) : ?>
                                <option value="<?= (int)$user->id ?>">
                                    <?= h(trim($user->first_name . ' ' . $user->last_name)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="starts-at">Starts (Melbourne)</label>
                        <input class="form-control" type="datetime-local" name="starts_at" id="starts-at" required>
                    </div>
                    <div class="admin-field">
                        <label for="ends-at">Ends (Melbourne)</label>
                        <input class="form-control" type="datetime-local" name="ends_at" id="ends-at" required>
                    </div>
                </div>
                <div class="admin-field mb-3">
                    <label for="customer-instructions">Customer instructions</label>
                    <textarea class="form-control" name="customer_instructions" id="customer-instructions" rows="2"></textarea>
                </div>
                <?= $this->Form->button('Confirm and schedule', ['class' => 'btn btn-eg-primary']) ?>
                <?= $this->Form->end() ?>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="logs-heading">
            <h2 id="logs-heading">Work logs</h2>
            <div class="admin-panel">
                <?php if (empty($request->service_work_logs)) : ?>
                    <p class="admin-note">No work recorded yet.</p>
                <?php else : ?>
                    <ul class="admin-timeline">
                        <?php foreach ($request->service_work_logs as $log) : ?>
                            <li>
                                <strong><?= (int)$log->duration_minutes ?> min</strong>
                                · <?= h($log->user->first_name ?? '') ?>
                                <div><?= h($log->work_summary) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?= $this->Form->create(null, ['url' => ['action' => 'addWorkLog', $request->id], 'class' => 'mt-3']) ?>
                <input type="hidden" name="staff_user_id" value="">
                <div class="admin-field mb-3">
                    <label for="work-summary">Work summary</label>
                    <textarea class="form-control" name="work_summary" id="work-summary" required rows="2"></textarea>
                </div>
                <div class="admin-field mb-3">
                    <label for="duration-minutes">Duration (minutes)</label>
                    <input class="form-control" type="number" name="duration_minutes" id="duration-minutes"
                           min="1" step="1" required>
                </div>
                <?= $this->Form->button('Add work log', ['class' => 'btn btn-eg-ghost']) ?>
                <?= $this->Form->end() ?>
            </div>
        </section>
    </div>

    <div>
        <section class="admin-section" aria-labelledby="status-heading">
            <h2 id="status-heading">Status</h2>
            <div class="admin-panel">
                <?= $this->Form->create(null, ['url' => ['action' => 'updateStatus', $request->id]]) ?>
                <div class="admin-field mb-3">
                    <label for="status">Request status</label>
                    <select class="form-select" name="status" id="status">
                        <?php foreach ($labels as $key => $label) : ?>
                            <option value="<?= h($key) ?>"<?= $request->status === $key ? ' selected' : '' ?>>
                                <?= h($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?= $this->Form->button('Update status', ['class' => 'btn btn-eg-ghost']) ?>
                <?= $this->Form->end() ?>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="parts-heading">
            <h2 id="parts-heading">Parts used</h2>
            <div class="admin-panel">
                <?php if (empty($request->service_parts_used)) : ?>
                    <p class="admin-note">No parts recorded.</p>
                <?php else : ?>
                    <ul class="mb-3">
                        <?php foreach ($request->service_parts_used as $part) : ?>
                            <li>
                                <?= h($part->product_variant->sku ?? '') ?>
                                × <?= (int)$part->quantity ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?= $this->Form->create(null, ['url' => ['action' => 'addPart', $request->id]]) ?>
                <div class="admin-field mb-3">
                    <label for="product-variant-id">Part</label>
                    <select class="form-select" name="product_variant_id" id="product-variant-id" required>
                        <option value="">Select</option>
                        <?php foreach ($variants as $variant) : ?>
                            <option value="<?= (int)$variant->id ?>">
                                <?= h($variant->product->name ?? $variant->name) ?>
                                (<?= h($variant->sku) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-field mb-3">
                    <label for="quantity">Quantity</label>
                    <input class="form-control" type="number" name="quantity" id="quantity" min="1" step="1" required>
                </div>
                <?= $this->Form->button('Record part', ['class' => 'btn btn-eg-ghost']) ?>
                <?= $this->Form->end() ?>
            </div>
        </section>
    </div>
</div>
