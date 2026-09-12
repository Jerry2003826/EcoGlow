<?php
// templates/element/admin/sidebar.php

$identity = $this->request->getAttribute('identity');

$userRole = '';

if ($identity) {
    $userRole = (string)($identity->get('UserRole') ?? '');
}

$currentController = (string)$this->request->getParam('controller');
$pass = (array)$this->request->getParam('pass');

$comingSoonModule = $currentController === 'ComingSoon'
    ? (string)($pass[0] ?? '')
    : '';

$active = static function (bool $isActive): array {
    return [
        'class' => $isActive ? 'sidebar-link active' : 'sidebar-link',
    ];
};

$staffCan = $staffCan ?? [];
$can = static function (string $key) use ($userRole, $staffCan): bool {
    if ($userRole === 'admin') {
        return true;
    }

    return !empty($staffCan[$key]);
};

$inventoryLabel = 'Inventory';

$totalStockAlert =
    (int)($lowStockCount ?? 0) +
    (int)($outOfStockCount ?? 0);

if ($can('inventory.view') && $totalStockAlert > 0) {
    $inventoryLabel .= ' <span class="sidebar-badge">' .
        $totalStockAlert .
        '</span>';
}
?>

<aside id="admin-sidebar" class="admin-sidebar">
    <div class="admin-brand">
        <?= $this->Html->link(
            'Eco Glow Lighting',
            '/'
        ) ?>
        <button id="admin-menu-close" class="admin-menu-close" type="button" aria-label="Close navigation">×</button>
    </div>

    <div class="admin-sidebar-scroll">
        <div class="sidebar-group">
            <div class="sidebar-group-title">Main</div>

            <?php if ($can('dashboard.view')): ?>
                <?= $this->Html->link(
                    'Dashboard',
                    '/admin',
                    $active($currentController === 'Dashboard')
                ) ?>
            <?php endif; ?>

            <?php if ($can('orders.view')): ?>
                <?= $this->Html->link(
                    'Orders',
                    '/admin/orders',
                    $active($currentController === 'Orders')
                ) ?>
            <?php endif; ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Operations</div>

            <?php if ($can('inventory.view')): ?>
                <?= $this->Html->link(
                    $inventoryLabel,
                    '/admin/inventory',
                    array_merge(
                        $active($currentController === 'Inventory'),
                        ['escape' => false]
                    )
                ) ?>
            <?php endif; ?>

            <?php if ($can('suppliers.view')): ?>
                <?= $this->Html->link(
                    'Suppliers',
                    '/admin/suppliers',
                    $active($currentController === 'Suppliers')
                ) ?>
            <?php endif; ?>

            <?php if ($can('customers.view')): ?>
                <?= $this->Html->link(
                    'Customers',
                    '/admin/customers',
                    $active($currentController === 'Customers')
                ) ?>
            <?php endif; ?>

            <?php if ($can('products.view')): ?>
                <?= $this->Html->link(
                    'Products',
                    '/admin/products',
                    $active($currentController === 'Products')
                ) ?>
            <?php endif; ?>

            <?php if ($can('shipments.view')): ?>
                <?= $this->Html->link(
                    'Shipments',
                    '/admin/shipments',
                    $active($currentController === 'Shipments')
                ) ?>
            <?php endif; ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Sales</div>

            <?php if ($can('quotations.manage')): ?>
                <?= $this->Html->link(
                    'Quotations',
                    '/admin/quotations',
                    $active($currentController === 'Quotations')
                ) ?>
            <?php endif; ?>

            <?php if ($can('invoices.issue')): ?>
                <?= $this->Html->link(
                    'Invoices',
                    '/admin/invoices',
                    $active($currentController === 'Invoices')
                ) ?>
            <?php endif; ?>

            <?php if ($can('payments.view')): ?>
                <?= $this->Html->link(
                    'Payments',
                    '/admin/payments',
                    $active($currentController === 'Payments')
                ) ?>
            <?php endif; ?>

            <?php if ($can('refunds.process')): ?>
                <?= $this->Html->link(
                    'Refunds',
                    '/admin/coming-soon/refunds',
                    $active($comingSoonModule === 'refunds')
                ) ?>
            <?php endif; ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Communication</div>

            <?php if ($can('messages.manage')): ?>
                <?= $this->Html->link(
                    'Messages',
                    '/admin/messages',
                    $active($currentController === 'Messages')
                ) ?>
            <?php endif; ?>
        </div>

        <?php if ($can('reports.view')): ?>
            <div class="sidebar-group">
                <div class="sidebar-group-title">Reporting</div>

                <?= $this->Html->link(
                    'Reports',
                    '/admin/coming-soon/reports',
                    $active($comingSoonModule === 'reports')
                ) ?>
            </div>
        <?php endif; ?>

        <?php if ($can('access.manage')): ?>
            <div class="sidebar-group">
                <div class="sidebar-group-title">Administration</div>

                <?= $this->Html->link(
                    'Access',
                    '/admin/access',
                    $active($currentController === 'Access')
                ) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <?= $this->Html->link(
            'View storefront',
            '/',
            ['class' => 'sidebar-storefront']
        ) ?>
    </div>
</aside>
