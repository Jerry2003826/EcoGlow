<?php
/**
 * Customer addresses.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var iterable<\App\Model\Entity\Address> $addresses
 * @var \App\Model\Entity\Address $address
 */
$this->assign('title', 'Addresses');
$this->Html->css('account', ['block' => true]);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Account', 'url' => '/account'],
        ['label' => 'Addresses'],
    ],
]));
?>
<div class="eg-page-head eg-page-head-start">
    <span class="eg-eyebrow">Your account</span>
    <h1 class="section-title">Addresses</h1>
</div>

<?php if (count($addresses) === 0) : ?>
    <p class="text-muted">No addresses saved yet. Add one below for delivery.</p>
<?php endif; ?>

<?php foreach ($addresses as $row) : ?>
    <div class="eg-card p-4 account-address">
            <p class="mb-1"><strong><?= h($row->recipient_name) ?></strong>
                <?php if ($row->label) : ?>
                    <span class="text-muted"> · <?= h($row->label) ?></span>
                <?php endif; ?>
            </p>
            <p class="mb-2 product-meta">
                <?= h($row->line1) ?><?= $row->line2 ? ', ' . h($row->line2) : '' ?><br>
                <?= h($row->suburb) ?> <?= h($row->state) ?> <?= h($row->postcode) ?>
            </p>
            <?= $this->Form->postLink(
                'Remove',
                '/account/addresses/delete/' . (int)$row->id,
                [
                    'class' => 'eg-cart-remove',
                    'confirm' => 'Remove this address?',
                ],
            ) ?>
    </div>
<?php endforeach; ?>

<div class="eg-card p-4 p-md-5 mt-4" style="max-width: 36rem;">
        <h2 class="h5 mb-3">Add an address</h2>
        <?= $this->Form->create($address, ['url' => '/account/addresses/add']) ?>
        <div class="mb-3">
            <?= $this->Form->control('label', [
                'label' => 'Label (home, work)',
                'class' => 'form-control',
            ]) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('recipient_name', [
                'label' => 'Recipient name',
                'class' => 'form-control',
                'required' => true,
                'autocomplete' => 'name',
            ]) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('line1', [
                'label' => 'Address line 1',
                'class' => 'form-control',
                'required' => true,
                'autocomplete' => 'address-line1',
            ]) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('line2', [
                'label' => 'Address line 2',
                'class' => 'form-control',
                'autocomplete' => 'address-line2',
            ]) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('suburb', [
                'label' => 'Suburb',
                'class' => 'form-control',
                'required' => true,
                'autocomplete' => 'address-level2',
            ]) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('state', [
                'label' => 'State',
                'class' => 'form-control',
                'required' => true,
                'autocomplete' => 'address-level1',
            ]) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('postcode', [
                'label' => 'Postcode',
                'class' => 'form-control',
                'required' => true,
                'autocomplete' => 'postal-code',
            ]) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('phone', [
                'type' => 'tel',
                'label' => 'Phone',
                'class' => 'form-control',
                'autocomplete' => 'tel',
            ]) ?>
        </div>
        <div class="form-check mb-4">
            <?= $this->Form->control('is_default_shipping', [
                'type' => 'checkbox',
                'label' => 'Default delivery address',
            ]) ?>
        </div>
        <div class="d-grid">
            <?= $this->Form->button(__('Save address'), ['class' => 'btn btn-eg-primary']) ?>
        </div>
        <?= $this->Form->end() ?>
</div>
