<?php
// templates/Account/index.php
$this->assign('title', 'My account');
?>

<div class="account-page">
    <header class="account-heading">
        <p class="account-eyebrow">Eco Glow / Your space</p>
        <h1>My account</h1>
        <p>Your contact details, purchases and account access in one place.</p>
    </header>

    <section class="account-card">
        <h2>Contact details</h2>
        <dl class="account-details">
            <div><dt>Name</dt><dd><?= h($customer->CustomerName) ?></dd></div>
            <div><dt>Email</dt><dd><?= h($customer->Email) ?></dd></div>
            <div><dt>Phone</dt><dd><?= h((string)$customer->Phone ?: 'Not provided') ?></dd></div>
            <div><dt>Address</dt><dd><?= h((string)$customer->Address ?: 'Not provided') ?></dd></div>
        </dl>
    </section>

    <div class="account-actions">
        <?= $this->Html->link('View my orders', '/account/orders', ['class' => 'account-button']) ?>
        <?= $this->Html->link(
            'Edit login details',
            '/users/edit/' . (int)$this->request->getAttribute('identity')->get('UserId'),
            ['class' => 'account-button account-button-secondary']
        ) ?>
    </div>
</div>
