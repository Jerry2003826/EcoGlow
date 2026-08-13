<?php
/**
 * Staff order recording form.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ProductVariant> $variants
 * @var iterable<\App\Model\Entity\Customer> $customers
 * @var array<int, int> $availability
 */

use App\Model\Entity\SalesOrder;

$this->assign('title', 'Record order');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Orders', 'url' => ['action' => 'index']],
        ['label' => 'Record order'],
    ],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Sales</span>
    <h1>Record an order</h1>
    <p class="text-muted mb-0">Use this for phone, email, SMS and walk-in sales so they share a promised date and a channel.</p>
</div>

<?= $this->Form->create(null, ['class' => 'admin-section']) ?>
    <section class="admin-section" aria-labelledby="customer-form-heading">
        <h2 id="customer-form-heading">Customer</h2>
        <div class="admin-panel">
            <div class="admin-field mb-3">
                <label for="customer-filter">Search existing customers</label>
                <input type="search" id="customer-filter" class="form-control" data-filter-select="customer-id"
                       placeholder="Name, email or phone">
            </div>
            <div class="admin-field mb-3">
                <label for="customer-id">Existing customer</label>
                <select name="customer_id" id="customer-id" class="form-select">
                    <option value="">Create a new customer below</option>
                    <?php foreach ($customers as $customer) : ?>
                        <option value="<?= (int)$customer->id ?>">
                            <?= h($customer->label) ?>
                            <?= $customer->email ? ' · ' . h($customer->email) : '' ?>
                            <?= $customer->phone ? ' · ' . h($customer->phone) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p class="eg-note">Leave the list on “create new” and fill the fields. A name, email or phone is enough to identify them.</p>
            <div class="admin-filter-row mt-3">
                <div class="admin-field">
                    <?= $this->Form->control('customer_first_name', [
                        'label' => 'First name',
                        'class' => 'form-control',
                        'templates' => ['inputContainer' => '{{content}}'],
                    ]) ?>
                </div>
                <div class="admin-field">
                    <?= $this->Form->control('customer_last_name', [
                        'label' => 'Last name',
                        'class' => 'form-control',
                        'templates' => ['inputContainer' => '{{content}}'],
                    ]) ?>
                </div>
                <div class="admin-field">
                    <?= $this->Form->control('customer_email', [
                        'type' => 'email',
                        'label' => 'Email',
                        'class' => 'form-control',
                        'templates' => ['inputContainer' => '{{content}}'],
                    ]) ?>
                </div>
                <div class="admin-field">
                    <?= $this->Form->control('customer_phone', [
                        'label' => 'Phone',
                        'class' => 'form-control',
                        'templates' => ['inputContainer' => '{{content}}'],
                    ]) ?>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-section" aria-labelledby="channel-form-heading">
        <h2 id="channel-form-heading">Channel and delivery</h2>
        <div class="admin-panel">
            <div class="admin-filter-row">
                <div class="admin-field">
                    <?= $this->Form->control('source_channel', [
                        'type' => 'select',
                        'label' => 'Source channel',
                        'class' => 'form-select',
                        'required' => true,
                        'empty' => 'Select how this order arrived',
                        'options' => SalesOrder::channelLabels(),
                        'templates' => ['inputContainer' => '{{content}}'],
                    ]) ?>
                </div>
                <div class="admin-field">
                    <?= $this->Form->control('external_source_reference', [
                        'label' => 'External reference',
                        'class' => 'form-control',
                        'placeholder' => 'Email thread or ticket number',
                        'templates' => ['inputContainer' => '{{content}}'],
                    ]) ?>
                </div>
                <div class="admin-field">
                    <?= $this->Form->control('promised_delivery_date', [
                        'type' => 'date',
                        'label' => 'Promised delivery date',
                        'class' => 'form-control',
                        'templates' => ['inputContainer' => '{{content}}'],
                        'lang' => 'en',
                    ]) ?>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-section" aria-labelledby="lines-form-heading">
        <h2 id="lines-form-heading">Products</h2>
        <div class="admin-panel">
            <div data-order-lines>
                <div class="admin-line-row" data-order-line>
                    <div class="admin-field">
                        <label for="line-search-0">Search SKU or name</label>
                        <input type="search" id="line-search-0" class="form-control" data-line-search
                               placeholder="Type to filter the list">
                    </div>
                    <div class="admin-field">
                        <label for="line-variant-0">Product / SKU</label>
                        <select name="lines[0][product_variant_id]" id="line-variant-0" class="form-select" data-variant-select>
                            <option value="">Select a product</option>
                            <?php foreach ($variants as $variant) : ?>
                                <?php $available = (int)($availability[(int)$variant->id] ?? 0); ?>
                                <option value="<?= (int)$variant->id ?>" data-available="<?= $available ?>">
                                    <?= h(($variant->product->name ?? $variant->name) . ' · ' . $variant->sku) ?>
                                    (<?= $available ?> available)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="line-qty-0">Quantity</label>
                        <input type="number" min="1" step="1" value="1" class="form-control"
                               name="lines[0][quantity]" id="line-qty-0" data-line-qty>
                    </div>
                    <p class="admin-stock-warn mb-0" data-stock-warning hidden></p>
                </div>
            </div>
            <button type="button" class="btn btn-eg-ghost" data-add-order-line>Add another line</button>
        </div>
    </section>

    <section class="admin-section" aria-labelledby="notes-form-heading">
        <h2 id="notes-form-heading">Internal note</h2>
        <div class="admin-panel">
            <?= $this->Form->control('internal_notes', [
                'type' => 'textarea',
                'label' => 'Note (optional)',
                'class' => 'form-control',
                'rows' => 3,
            ]) ?>
        </div>
    </section>

    <?= $this->Form->button('Save order', ['class' => 'btn btn-eg-primary']) ?>
<?= $this->Form->end() ?>
