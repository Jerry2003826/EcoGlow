<?php
// templates/Products/index.php
?>

<h1>Products</h1>

<?= $this->Form->create(null, ['type' => 'get']) ?>
<?= $this->Form->control('q', ['value' => $q ?? '']) ?>
<?= $this->Form->control('category', ['value' => $category ?? '']) ?>
<?= $this->Form->button('Search') ?>
<?= $this->Form->end() ?>

<p>
    <?= $this->Html->link('View Cart', [
        'controller' => 'Cart',
        'action' => 'index',
    ]) ?>
</p>

<?php foreach ($products as $product): ?>
    <div>
        <h3><?= h($product->ProductName) ?></h3>

        <p><?= h($product->Description) ?></p>

        <p>$<?= number_format((float)$product->Price, 2) ?></p>

        <?=
        $this->Form->create(null)
        ?>

        <input
            type="hidden"
            name="product_id"
            value="<?= (int)$product->ProductId ?>"
        >

        <?= $this->Form->button('Add To Cart') ?>

        <?= $this->Form->end() ?>
    </div>
<?php endforeach; ?>
