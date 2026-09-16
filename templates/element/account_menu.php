<?php
// templates/element/account_menu.php

$identity = $this->request->getAttribute('identity');

if (!$identity) {
    return;
}

$username = (string)($identity->get('Username') ?? 'there');
$role = (string)($identity->get('UserRole') ?? '');
$profileUrl = in_array($role, ['admin', 'staff'], true) ? '/admin' : '/account';
?>
<div class="nav-account">
    <button
        class="nav-account-toggle"
        type="button"
        aria-expanded="false"
        aria-controls="nav-account-menu"
        aria-label="Open account menu"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"></circle>
            <path d="M5.2 18.6c1.4-3 3.8-4.6 6.8-4.6s5.4 1.6 6.8 4.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
        </svg>
    </button>
    <div id="nav-account-menu" class="nav-account-menu" hidden>
        <span class="nav-account-hi">Hi <?= h($username) ?></span>
        
        <?php if (in_array($role, ['admin'], true)): ?>
            <?= $this->Html->link(
                'Admin Portal',
                '/admin',
                ['class' => 'nav-account-profile']
            ) ?>
        <?php endif; ?>

        <?php if (in_array($role, ['staff'], true)): ?>
            <?= $this->Html->link(
                'Admin Portal',
                '/admin',
                ['class' => 'nav-account-profile']
            ) ?>
        <?php endif; ?>

        <?= $this->Html->link(
            'Sign out',
            '/logout',
            ['class' => 'nav-account-signout']
        ) ?>
    </div>
</div>
