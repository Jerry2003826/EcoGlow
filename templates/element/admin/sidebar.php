<?php

$currentController = (string)$this->request->getParam('controller');
$currentModule = (string)($this->request->getParam('pass')[0] ?? '');

$unreadCount = (int)($unreadCount ?? 0);
$dashboardActive = $currentController === 'Dashboard';
$ordersActive = $currentController === 'Orders';

$linkOptions = static function (bool $active): array {
    $options = [
        'class' => 'sidebar-link' . ($active ? ' active' : ''),
        'escape' => false,
    ];

    if ($active) {
        $options['aria-current'] = 'page';
    }

    return $options;
};

$comingSoonActive = static function (string $module) use ($currentController, $currentModule): bool {
    return $currentController === 'ComingSoon' && $currentModule === $module;
};

$messagesLabel = 'Messages';
if ($unreadCount > 0) {
    $messagesLabel .= ' <span class="sidebar-badge">' . h((string)$unreadCount) . '</span>';
}
?>

<aside
    id="admin-sidebar"
    class="admin-sidebar"
    aria-label="Primary navigation">

    <div class="admin-brand">
        Eco Glow Lighting
    </div>

    <div class="admin-sidebar-scroll">
        <div class="sidebar-group">
            <div class="sidebar-group-title">
                Operations
            </div>

            <?= $this->Html->link(
                'Dashboard',
                '/admin',
                $linkOptions($dashboardActive)
            ) ?>

            <?= $this->Html->link(
                'Orders',
                '/admin/orders',
                $linkOptions($ordersActive)
            ) ?>

            <?= $this->Html->link(
                'Shipments',
                '/admin/coming-soon/shipments',
                $linkOptions($comingSoonActive('shipments'))
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">
                Catalogue
            </div>

            <?= $this->Html->link(
                'Products',
                '/admin/coming-soon/products',
                $linkOptions($comingSoonActive('products'))
            ) ?>

            <?= $this->Html->link(
                'Inventory',
                '/admin/coming-soon/inventory',
                $linkOptions($comingSoonActive('inventory'))
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">
                Customers
            </div>

            <?= $this->Html->link(
                'Customers',
                '/admin/coming-soon/customers',
                $linkOptions($comingSoonActive('customers'))
            ) ?>

            <?= $this->Html->link(
                $messagesLabel,
                '/admin/coming-soon/messages',
                $linkOptions($comingSoonActive('messages'))
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">
                Finance
            </div>

            <?= $this->Html->link(
                'Invoices',
                '/admin/coming-soon/invoices',
                $linkOptions($comingSoonActive('invoices'))
            ) ?>

            <?= $this->Html->link(
                'Payments',
                '/admin/coming-soon/payments',
                $linkOptions($comingSoonActive('payments'))
            ) ?>

            <?= $this->Html->link(
                'Refunds',
                '/admin/coming-soon/refunds',
                $linkOptions($comingSoonActive('refunds'))
            ) ?>

            <?= $this->Html->link(
                'Reports',
                '/admin/coming-soon/reports',
                $linkOptions($comingSoonActive('reports'))
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">
                Settings
            </div>

            <?= $this->Html->link(
                'Settings',
                '/admin/coming-soon/settings',
                $linkOptions($comingSoonActive('settings'))
            ) ?>
        </div>
    </div>

    <div class="sidebar-footer">
        <?= $this->Html->link(
            'Back to Storefront',
            '/',
            ['class' => 'sidebar-storefront']
        ) ?>
    </div>

</aside>
