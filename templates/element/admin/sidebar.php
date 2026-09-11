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

$inventoryLabel = 'Inventory';

if (
    ($userRole === 'admin' || $userRole === 'staff') &&
    (int)($lowStockCount ?? 0) > 0
) {
    $inventoryLabel .= ' <span class="sidebar-badge">' .
        (int)$lowStockCount .
        '</span>';
}
?>

<aside id="admin-sidebar" class="admin-sidebar">
    <div class="admin-brand">
        <?= $this->Html->link(
            'Eco Glow Lighting',
            '/'
        ) ?>
    </div>

    <div class="admin-sidebar-scroll">
        <div class="sidebar-group">
            <div class="sidebar-group-title">Main</div>

            <?= $this->Html->link(
                'Dashboard',
                '/admin',
                $active($currentController === 'Dashboard')
            ) ?>

            <?= $this->Html->link(
                'Orders',
                '/admin/orders',
                $active($currentController === 'Orders')
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Operations</div>

            <?= $this->Html->link(
                $inventoryLabel,
                '/admin/inventory',
                array_merge(
                    $active($currentController === 'Inventory'),
                    ['escape' => false]
                )
            ) ?>

            <?php if($userRole === 'admin'): ?>
                <?= $this->Html->link(
                    'Suppliers',
                    '/admin/suppliers',
                    $active($currentController === 'Suppliers')
                ) ?>
            <?php endif; ?>

            <?= $this->Html->link(
                'Customers',
                '/admin/customers',
                $active($currentController === 'Customers')
            ) ?>

            <?= $this->Html->link(
                'Products',
                '/admin/products',
                $active($currentController === 'Products')
            ) ?>

            <?= $this->Html->link(
                'Shipments',
                '/admin/shipments',
                $active($currentController === 'Shipments')
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Sales</div>

            <?= $this->Html->link(
                'Quotations',
                '/admin/quotations',
                $active($currentController === 'Quotations')
            ) ?>

            <?= $this->Html->link(
                'Invoices',
                '/admin/invoices',
                $active($currentController === 'Invoices')
            ) ?>

            <?= $this->Html->link(
                'Payments',
                '/admin/payments',
                $active($currentController === 'Payments')
            ) ?>

            <?= $this->Html->link(
                'Refunds',
                '/admin/coming-soon/refunds',
                $active($comingSoonModule === 'refunds')
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Communication</div>

            <?= $this->Html->link(
                'Messages',
                '/admin/messages',
                $active($currentController === 'Messages')
            ) ?>
        </div>
        
        <?php if($userRole === 'admin'): ?>
            <div class="sidebar-group">
                <div class="sidebar-group-title">Reporting</div>

                <?= $this->Html->link(
                    'Reports',
                    '/admin/coming-soon/reports',
                    $active($comingSoonModule === 'reports')
                ) ?>
            </div>
        <?php endif; ?>
        
        <?php if($userRole === 'admin'): ?>
            <div class="sidebar-group">
                <div class="sidebar-group-title">Administration</div>

                <?= $this->Html->link(
                    'Access',
                    '/admin/coming-soon/access',
                    $active($comingSoonModule === 'access')
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
