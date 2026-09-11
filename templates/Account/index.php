<?php
// templates/Account/index.php
?>

<div class="checkout-card">
    <h1>My account</h1>
    <p><strong>Name:</strong> <?= h($customer->CustomerName) ?></p>
    <p><strong>Email:</strong> <?= h($customer->Email) ?></p>
    <p><strong>Phone:</strong> <?= h((string)$customer->Phone) ?></p>
    <p><strong>Address:</strong> <?= h((string)$customer->Address) ?></p>

    <p>
        <?= $this->Html->link('View my orders', '/account/orders') ?>
    </p>
    <p>
        <?= $this->Html->link(
            'Edit login details',
            '/users/edit/' . (int)$this->request->getAttribute('identity')->get('UserId')
        ) ?>
    </p>
</div>
